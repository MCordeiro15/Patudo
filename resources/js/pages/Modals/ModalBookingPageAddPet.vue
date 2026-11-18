<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { Dog, Plus } from '@lucide/vue';
import type { Animal } from '@/types';

const props = defineProps<{
    show: boolean;
    bookingId?: number | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'added', animalId: number): void;
}>();

function getXsrf(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

function apiFetch(url: string, options: RequestInit = {}) {
    return fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getXsrf(),
            ...options.headers,
        },
    });
}

const animals = ref<Animal[]>([]);
const existingAnimalIds = ref<number[]>([]);
const loading = ref(false);
const feedback = ref('');
const searchQuery = ref('');

const filteredAnimals = computed(() => {
    if (!searchQuery.value) return animals.value;
    const query = searchQuery.value.toLowerCase();
    return animals.value.filter(
        (a) =>
            a.name.toLowerCase().includes(query) ||
            a.species.toLowerCase().includes(query) ||
            (a.breed && a.breed.toLowerCase().includes(query)),
    );
});

const availableAnimals = computed(() =>
    filteredAnimals.value.filter((a) => !existingAnimalIds.value.includes(a.id)),
);

const loadAnimals = async () => {
    loading.value = true;
    feedback.value = '';
    try {
        await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });
        const response = await fetch('/api/animals', {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (response.ok) {
            const data = await response.json();
            animals.value = data.data ?? [];
        } else {
            feedback.value = 'Erro ao carregar os animais.';
        }
    } catch {
        feedback.value = 'Erro de conexão. Tente novamente.';
    } finally {
        loading.value = false;
    }
};

const loadExistingAnimals = async () => {
    if (!props.bookingId) return;
    try {
        const res = await apiFetch(`/api/bookings/${props.bookingId}`);
        if (res.ok) {
            const data = (await res.json()).data;
            existingAnimalIds.value = (data.animals ?? []).map((a: Animal) => a.id);
        }
    } catch {
        // Ignorar erro ao carregar animais existentes
    }
};

const addAnimal = async (animalId: number) => {
    if (!props.bookingId) return;
    loading.value = true;
    feedback.value = '';
    try {
        const res = await apiFetch(`/api/bookings/${props.bookingId}`, {
            method: 'PATCH',
            body: JSON.stringify({ animal_ids: [animalId] }),
        });
        if (res.ok) {
            existingAnimalIds.value.push(animalId);
            feedback.value = 'Animal adicionado com sucesso!';
            emit('added', animalId);
            setTimeout(() => {
                feedback.value = '';
            }, 3000);
        } else {
            const err = await res.json().catch(() => ({}));
            feedback.value = err.message ?? 'Não foi possível adicionar o animal.';
        }
    } catch {
        feedback.value = 'Erro de conexão. Tente novamente.';
    } finally {
        loading.value = false;
    }
};

const handleClose = () => {
    searchQuery.value = '';
    feedback.value = '';
    emit('close');
};

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            void loadAnimals();
            void loadExistingAnimals();
        }
    },
);

onMounted(() => {
    if (props.show) {
        void loadAnimals();
        void loadExistingAnimals();
    }
});
</script>

<template>
    <div
        v-if="show"
        class="patudo-modal-backdrop"
        @click.self="handleClose"
        @keydown.escape="handleClose"
    >
        <div class="patudo-modal-card" role="dialog" aria-modal="true">
            <!-- Cabeçalho -->
            <div class="patudo-modal-header">
                <div class="patudo-header-left">
                    <h2 class="patudo-modal-title">
                        Adicionar Animal
                    </h2>
                </div>
                <button
                    type="button"
                    class="patudo-close-btn"
                    aria-label="Fechar"
                    @click="handleClose"
                >
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                        <path d="M18 6L6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Mensagem de sucesso -->
            <div v-if="feedback.includes('sucesso')" class="patudo-success-banner">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M20 6L9 17l-5-5" />
                </svg>
                {{ feedback }}
            </div>

            <!-- Mensagem de erro -->
            <div v-if="feedback && !feedback.includes('sucesso')" class="patudo-error-banner">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="12" cy="12" r="10" />
                    <path d="M15 9l-6 6M9 9l6 6" />
                </svg>
                {{ feedback }}
            </div>

            <!-- Corpo -->
            <div class="patudo-modal-body">
                <!-- Pesquisa -->
                <div class="mb-3">
                    <input
                        type="text"
                        class="patudo-search-input"
                        placeholder="Pesquisar animal..."
                        v-model="searchQuery"
                    />
                </div>

                <!-- Loading -->
                <div v-if="loading && animals.length === 0" class="patudo-loading-state">
                    <div class="patudo-spinner"></div>
                    <p class="patudo-empty-text">A carregar animais...</p>
                </div>

                <!-- Sem animais -->
                <div v-else-if="animals.length === 0" class="patudo-empty-state">
                    <Dog :size="48" class="text-muted mb-2" />
                    <p class="patudo-empty-text">Não tens animais registados.</p>
                    <small class="text-muted">Vai ao teu perfil para adicionar.</small>
                </div>

                <!-- Sem resultados -->
                <div v-else-if="availableAnimals.length === 0" class="patudo-empty-state">
                    <p class="patudo-empty-text">
                        {{ existingAnimalIds.length === animals.length
                            ? 'Todos os animais já estão na reserva.'
                            : 'Nenhum animal encontrado.' }}
                    </p>
                </div>

                <!-- Lista de animais disponíveis -->
                <div class="patudo-animal-list">
                    <div
                        v-for="animal in availableAnimals"
                        :key="animal.id"
                        class="patudo-animal-option"
                        @click="addAnimal(animal.id)"
                    >
                        <div class="patudo-animal-avatar">
                            <img
                                v-if="animal.photo"
                                :src="animal.photo"
                                :alt="animal.name"
                                class="w-100 h-100 object-fit-cover"
                            />
                            <Dog v-else :size="24" class="text-primary" />
                        </div>
                        <div class="patudo-animal-info">
                            <p class="fw-semibold mb-0">{{ animal.name }}</p>
                            <small class="text-muted">
                                {{ animal.species }} {{ animal.breed ? '• ' + animal.breed : '' }}
                            </small>
                        </div>
                        <div class="patudo-add-icon">
                            <Plus :size="20" class="text-primary" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rodapé -->
            <div class="patudo-modal-footer">
                <span class="patudo-footer-note">
                    {{ availableAnimals.length }} animais disponíveis
                </span>
                <button
                    type="button"
                    class="btn-patudo-outline-pill"
                    @click="handleClose"
                >
                    Fechar
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.patudo-modal-backdrop {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    z-index: 10000000;
}

.patudo-modal-card {
    position: relative;
    width: 100%;
    max-width: 480px;
    background-color: #fff8f1;
    border: 1.5px solid #c4450c;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
    font-family: 'Plus Jakarta Sans', sans-serif;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
}

/* Cabeçalho */
.patudo-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.15rem 1.5rem;
    border-bottom: 1.5px solid #c4450c;
    flex-shrink: 0;
}

.patudo-header-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.patudo-modal-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #231810;
    margin: 0;
}

.patudo-close-btn {
    background: transparent;
    border: none;
    color: #231810;
    cursor: pointer;
    padding: 2px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.15s ease;
}

.patudo-close-btn:hover {
    opacity: 0.65;
}

/* Banners */
.patudo-success-banner {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.55rem 1.5rem;
    background-color: #dcfce7;
    color: #166534;
    font-size: 0.78rem;
    font-weight: 600;
    border-bottom: 1px solid #bbf7d0;
}

.patudo-error-banner {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.55rem 1.5rem;
    background-color: #fee2e2;
    color: #991b1b;
    font-size: 0.78rem;
    font-weight: 600;
    border-bottom: 1px solid #fecaca;
}

/* Corpo */
.patudo-modal-body {
    padding: 1.15rem 1.5rem;
    overflow-y: auto;
    flex: 1;
}

/* Pesquisa */
.patudo-search-input {
    width: 100%;
    padding: 0.6rem 1rem;
    border: 1.2px solid #d4a574;
    border-radius: 9999px;
    background-color: #ffffff;
    color: #231810;
    font-size: 0.85rem;
    font-family: 'Plus Jakarta Sans', sans-serif;
    outline: none;
    transition: border-color 0.15s ease;
}

.patudo-search-input:focus {
    border-color: #c4450c;
}

.patudo-search-input::placeholder {
    color: #a8846a;
}

/* Loading & Empty */
.patudo-loading-state,
.patudo-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem 0;
    gap: 0.5rem;
}

.patudo-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #e5d5c5;
    border-top-color: #c4450c;
    border-radius: 50%;
    animation: patudo-spin 0.6s linear infinite;
}

@keyframes patudo-spin {
    to { transform: rotate(360deg); }
}

.patudo-empty-text {
    font-size: 0.85rem;
    color: #6e5239;
    font-weight: 600;
    margin: 0;
}

/* Lista de animais */
.patudo-animal-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.patudo-animal-option {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background-color: #ffffff;
    border: 1.2px solid #e5d5c5;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.patudo-animal-option:hover {
    border-color: #c4450c;
    box-shadow: 0 2px 8px rgba(196, 69, 12, 0.1);
}

.patudo-animal-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background-color: #fffaf3;
    flex-shrink: 0;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.patudo-animal-info {
    flex: 1;
    min-width: 0;
}

.patudo-add-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* Rodapé */
.patudo-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.85rem 1.5rem;
    border-top: 1.5px solid #c4450c;
    flex-shrink: 0;
}

.patudo-footer-note {
    font-size: 0.72rem;
    color: #6e5239;
    font-weight: 600;
}

.btn-patudo-outline-pill {
    background-color: #ffffff;
    color: var(--patudo-terracota, #c4450c);
    border: 1.3px solid var(--patudo-terracota, #c4450c);
    border-radius: 9999px;
    padding: 0.38rem 1.15rem;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.btn-patudo-outline-pill:hover {
    background-color: #fdf2ea;
}
</style>
