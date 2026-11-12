<script setup lang="ts">
import { ref } from 'vue';
import type { AnimalFormErrors } from '@/types';
import InputError from '@/components/InputError.vue';

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'saved'): void;
}>();

defineProps<{
    show: boolean;
}>();

// Form state
const name = ref('');
const species = ref('');
const breed = ref('');
const size = ref('');
const specialNeeds = ref('');

// UI state
const errors = ref<AnimalFormErrors>({});
const isLoading = ref(false);
const generalError = ref('');

const sizeOptions = [
    { value: 'small', label: 'Pequeno' },
    { value: 'medium', label: 'Médio' },
    { value: 'large', label: 'Grande' },
];

const resetForm = () => {
    name.value = '';
    species.value = '';
    breed.value = '';
    size.value = '';
    specialNeeds.value = '';
    errors.value = {};
    generalError.value = '';
};

const close = () => {
    resetForm();
    emit('close');
};

const submit = async () => {
    errors.value = {};
    generalError.value = '';
    isLoading.value = true;

    try {
        await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin',
        });

        const xsrfToken = document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1];

        const body: Record<string, string | null> = {
            name: name.value.trim(),
            species: species.value.trim(),
            breed: breed.value.trim(),
            size: size.value,
            special_needs: specialNeeds.value.trim() || null,
        };

        const response = await fetch('/api/animals', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken ?? '',
            },
            body: JSON.stringify(body),
        });

        if (response.ok) {
            emit('saved');
            close();
        } else {
            const errorData = await response.json().catch(() => ({}));

            if (errorData.errors) {
                errors.value = errorData.errors;
            } else {
                generalError.value = errorData.message ?? 'Erro ao adicionar animal.';
            }
        }
    } catch {
        generalError.value = 'Erro de conexão. Tente novamente.';
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="patudo-modal-backdrop"
            @click.self="close"
            @keydown.escape="close"
        >
            <div class="patudo-modal-card" role="dialog" aria-modal="true">
                <!-- Botão Fechar (X) -->
                <button
                    type="button"
                    class="patudo-close-btn"
                    aria-label="Fechar"
                    @click="close"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                        <path d="M18 6L6 18M6 6l12 12" />
                    </svg>
                </button>

                <!-- Cabeçalho -->
                <div class="mb-3">
                    <h2 class="patudo-modal-title">Adicionar animal</h2>
                    <p class="patudo-modal-subtitle">
                        Preenche os dados do teu patudo para o associares ao teu perfil.
                    </p>
                </div>

                <!-- Formulário -->
                <form @submit.prevent="submit">
                    <!-- Erro geral -->
                    <div
                        v-if="generalError"
                        class="patudo-alert-error mb-3"
                        role="alert"
                    >
                        {{ generalError }}
                    </div>

                    <!-- Nome -->
                    <div class="mb-3">
                        <label for="animal-name" class="patudo-section-label d-block mb-1">
                            Nome *
                        </label>
                        <input
                            id="animal-name"
                            v-model="name"
                            type="text"
                            class="patudo-input"
                            :class="{ 'is-invalid': errors.name }"
                            placeholder="Ex: Rex"
                            required
                        />
                        <InputError :message="errors.name?.[0]" />
                    </div>

                    <!-- Espécie e Raça lado a lado -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="animal-species" class="patudo-section-label d-block mb-1">
                                Espécie *
                            </label>
                            <input
                                id="animal-species"
                                v-model="species"
                                type="text"
                                class="patudo-input"
                                :class="{ 'is-invalid': errors.species }"
                                placeholder="Ex: Cão, Gato"
                                required
                            />
                            <InputError :message="errors.species?.[0]" />
                        </div>

                        <div class="col-6">
                            <label for="animal-breed" class="patudo-section-label d-block mb-1">
                                Raça *
                            </label>
                            <input
                                id="animal-breed"
                                v-model="breed"
                                type="text"
                                class="patudo-input"
                                :class="{ 'is-invalid': errors.breed }"
                                placeholder="Ex: Labrador"
                                required
                            />
                            <InputError :message="errors.breed?.[0]" />
                        </div>
                    </div>

                    <!-- Porte -->
                    <div class="mb-3">
                        <label for="animal-size" class="patudo-section-label d-block mb-1">
                            Porte *
                        </label>
                        <select
                            id="animal-size"
                            v-model="size"
                            class="patudo-input patudo-select"
                            :class="{ 'is-invalid': errors.size }"
                            required
                        >
                            <option value="" disabled>Seleciona o porte</option>
                            <option
                                v-for="option in sizeOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                        <InputError :message="errors.size?.[0]" />
                    </div>

                    <!-- Necessidades especiais -->
                    <div class="mb-3">
                        <label for="animal-special-needs" class="patudo-section-label d-block mb-1">
                            Necessidades especiais <span class="patudo-optional">(opcional)</span>
                        </label>
                        <textarea
                            id="animal-special-needs"
                            v-model="specialNeeds"
                            rows="2"
                            class="patudo-textarea"
                            placeholder="Ex: Medicação diária, dieta especial..."
                        ></textarea>
                        <InputError :message="errors.special_needs?.[0]" />
                    </div>

                    <!-- Botões de Ação -->
                    <div class="patudo-modal-actions">
                        <button
                            type="button"
                            class="btn-patudo-outline"
                            :disabled="isLoading"
                            @click="close"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="btn-patudo-solid"
                            :disabled="isLoading"
                        >
                            {{ isLoading ? 'A guardar...' : 'Adicionar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
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
    padding: 1.5rem 1.75rem;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.patudo-close-btn {
    position: absolute;
    top: 1.25rem;
    right: 1.25rem;
    background: transparent;
    border: none;
    color: #2b211a;
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.15s ease;
}

.patudo-close-btn:hover {
    opacity: 0.65;
}

.patudo-modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #231810;
    margin: 0 0 0.25rem 0;
}

.patudo-modal-subtitle {
    font-size: 0.85rem;
    color: #8c7a6b;
    margin: 0;
}

.patudo-section-label {
    font-size: 0.88rem;
    font-weight: 700;
    color: #231810;
}

.patudo-optional {
    font-weight: 400;
    color: #8c7a6b;
    font-size: 0.82rem;
}

.patudo-alert-error {
    background-color: #fee2e2;
    color: #991b1b;
    border-radius: 12px;
    padding: 0.6rem 0.9rem;
    font-size: 0.84rem;
}

.patudo-input,
.patudo-textarea {
    width: 100%;
    background-color: #ffffff;
    border: 1px solid transparent;
    border-radius: 14px;
    padding: 0.65rem 0.95rem;
    font-size: 0.86rem;
    color: #231810;
    outline: none;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
}

.patudo-textarea {
    resize: none;
}

.patudo-input::placeholder,
.patudo-textarea::placeholder {
    color: #9e8e80;
}

.patudo-input:focus,
.patudo-textarea:focus {
    box-shadow: 0 0 0 2px rgba(196, 69, 12, 0.25);
}

.patudo-input.is-invalid {
    border-color: #dc2626;
}

.patudo-select {
    cursor: pointer;
}

.patudo-modal-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 0.65rem;
    margin-top: 1.25rem;
}

.btn-patudo-outline {
    background-color: #ffffff;
    color: #231810;
    border: 1px solid #c4450c;
    border-radius: 12px;
    padding: 0.55rem 1.75rem;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.btn-patudo-outline:hover:not(:disabled) {
    background-color: #fdf2ea;
}

.btn-patudo-solid {
    background-color: #c4450c;
    color: #ffffff;
    border: 1px solid #c4450c;
    border-radius: 12px;
    padding: 0.55rem 1.5rem;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.btn-patudo-solid:hover:not(:disabled) {
    background-color: #a83908;
}

.btn-patudo-outline:disabled,
.btn-patudo-solid:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
