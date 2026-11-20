<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Bell, CalendarDays, LogOut, MessageCircle, UserRound, ChevronLeft, Dog, Plus } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import type { Animal } from '@/types';
import { useNotifications } from '@/composables/useNotifications';
import ModalNotifications from '@/pages/Modals/ModalNotifications.vue';
import ModalBookingPageAddPet from '@/pages/Modals/ModalBookingPageAddPet.vue';

// O id do petsitter vem do URL: /booking?petsitter=ID
const petsitterId = new URLSearchParams(window.location.search).get('petsitter');

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

// Estado do formulário
const startDate = ref('');
const endDate = ref('');
const selectedAnimals = ref<number[]>([]);
const notes = ref('');
const feedback = ref('');
const submitting = ref(false);

// Dados carregados
const animals = ref<Animal[]>([]);
const loadError = ref('');

type PetsitterData = {
    id: number;
    user?: { name: string };
    price_per_day: number | null;
    service_location: string | null;
    city: string | null;
    short_bio: string | null;
    avatar_url: string | null;
    average_rating: number | null;
    reviews_count: number;
};

const petsitter = ref<PetsitterData | null>(null);

// Computed
const displayName = computed(() => (petsitter.value ? (petsitter.value.user?.name ?? 'Petsitter') : 'Petsitter'));
const displayLocation = computed(() => (petsitter.value ? (petsitter.value.service_location ?? petsitter.value.city ?? '--') : '--'));
const displayPrice = computed(() => (petsitter.value ? (petsitter.value.price_per_day ?? 0) : 0));
const numberOfDays = computed(() => {
    if (!startDate.value || !endDate.value) return 0;
    const start = new Date(startDate.value);
    const end = new Date(endDate.value);
    const diff = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1;
    return diff > 0 ? diff : 0;
});

const totalPrice = computed(() => numberOfDays.value * displayPrice.value);

const isFormValid = computed(() =>
    Boolean(startDate.value && endDate.value && selectedAnimals.value.length > 0),
);

// Data mínima para seleção (hoje)
const today = new Date().toISOString().split('T')[0];

// Carregar dados
const loadPetsitter = async () => {
    if (!petsitterId) return;
    const res = await apiFetch(`/api/petsitters/${petsitterId}`);
    if (res.ok) petsitter.value = (await res.json()).data;
    else feedback.value = 'Não foi possível carregar o petsitter.';
};

const loadAnimals = async () => {
    loadError.value = '';
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
            loadError.value = 'Erro ao carregar os animais.';
        }
    } catch {
        loadError.value = 'Erro de conexão. Tente novamente.';
    }
};

// Toggle seleção de animal
const toggleAnimal = (id: number) => {
    const index = selectedAnimals.value.indexOf(id);
    if (index > -1) {
        selectedAnimals.value.splice(index, 1);
    } else {
        selectedAnimals.value.push(id);
    }
};

// Quando um novo animal é criado no modal, adiciona à lista e seleciona-o
const handleAnimalCreated = (animal: Animal) => {
    if (!animal?.id) return;

    const alreadyInList = animals.value.some((item) => item.id === animal.id);
    if (!alreadyInList) {
        animals.value.push(animal);
    }

    if (!selectedAnimals.value.includes(animal.id)) {
        selectedAnimals.value.push(animal.id);
    }

    loadError.value = '';
};

// Submeter reserva
const submitBooking = async () => {
    if (!isFormValid.value || !petsitterId) return;
    submitting.value = true;
    feedback.value = '';

    try {
        await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });
        const res = await apiFetch('/api/bookings', {
            method: 'POST',
            body: JSON.stringify({
                petsitter_id: Number(petsitterId),
                start_date: startDate.value,
                end_date: endDate.value,
                animal_ids: selectedAnimals.value,
                notes: notes.value.trim() || null,
            }),
        });

        if (res.ok) {
            feedback.value = 'Reserva criada com sucesso! Aguardando confirmação do petsitter.';
            startDate.value = '';
            endDate.value = '';
            selectedAnimals.value = [];
            notes.value = '';
        } else {
            const err = await res.json().catch(() => ({}));
            if (err.errors) {
                feedback.value = Object.values(err.errors).flat().join(' ');
            } else {
                feedback.value = err.message ?? 'Não foi possível criar a reserva.';
            }
        }
    } catch {
        feedback.value = 'Erro de conexão. Tente novamente.';
    } finally {
        submitting.value = false;
    }
};

const showNotifications = ref(false);
const { unreadCount } = useNotifications();
const showAddPetModal = ref(false);

onMounted(() => {
    void loadPetsitter();
    void loadAnimals();
});
</script>

<template>
    <Head title="Nova Reserva" />

    <div class="min-vh-100 d-flex flex-column position-relative overflow-hidden patudo-page">
        <div class="patudo-glow patudo-glow-top"></div>
        <div class="patudo-glow patudo-glow-bottom"></div>

        <main class="patudo-content flex-grow-1 py-4">
            <div class="container">
                <nav
                    class="navbar navbar-expand-lg bg-white border border-primary rounded-4 px-3 px-md-4"
                >
                    <a
                        class="navbar-brand fw-bold fs-3 d-flex align-items-center gap-2"
                        href="/"
                    >
                        <img
                            :src="logoPatudo"
                            alt="Patudo"
                            width="70"
                            height="70"
                        />
                        <span>Patudo</span>
                    </a>

                    <div class="nav nav-pills border rounded-pill p-1 mx-auto">
                        <a class="nav-link rounded-pill px-4" href="/">Início</a>
                        <a class="nav-link rounded-pill px-4" href="/orders">Pedidos</a>
                        <a class="nav-link rounded-pill px-4" href="/favorites">Favoritos</a>
                        <a class="nav-link active rounded-pill px-4" href="/reservations">Reservas</a>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <a href="/messages" class="text-dark">
                            <MessageCircle :size="25" />
                        </a>
                        <button
                            type="button"
                            class="text-dark bg-transparent border-0 p-0 position-relative"
                            @click="showNotifications = true"
                        >
                            <Bell :size="25" />
                            <span
                                v-if="unreadCount > 0"
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                style="font-size: 0.6rem;"
                            >
                                {{ unreadCount > 9 ? '9+' : unreadCount }}
                            </span>
                        </button>
                        <button
                            type="button"
                            class="text-dark p-0 border-0 bg-transparent"
                        >
                            <UserRound :size="25" />
                        </button>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="btn btn-outline-danger rounded-pill d-flex align-items-center gap-2 px-3"
                        >
                            <LogOut :size="16" />
                            <span>Sair</span>
                        </Link>
                    </div>
                </nav>

                <!-- Botão de voltar -->
                <div class="mt-4">
                    <a
                        href="/profile-owner-view"
                        class="btn btn-outline-secondary rounded-pill d-inline-flex align-items-center gap-2"
                    >
                        <ChevronLeft :size="16" />
                        Voltar ao perfil
                    </a>
                </div>

                <h1 class="text-center fw-bold display-5 mt-3 mb-4">Nova Reserva</h1>

                <!-- Feedback -->
                <div
                    v-if="feedback"
                    class="alert py-2 small"
                    :class="feedback.includes('sucesso') ? 'alert-success' : 'alert-danger'"
                    role="alert"
                >
                    {{ feedback }}
                </div>

                <div class="row g-4">
                    <!-- Coluna esquerda — Formulário -->
                    <div class="col-12 col-lg-7">
                        <section class="card bg-white border border-primary rounded-4 p-3 p-md-4 mb-4">
                            <h2 class="h5 fw-semibold mb-3">Datas da reserva</h2>

                            <div class="row g-3 mb-3">
                                <div class="col-12 col-sm-6">
                                    <label for="start-date" class="form-label small fw-semibold">Data de início</label>
                                    <input
                                        id="start-date"
                                        type="date"
                                        class="form-control"
                                        :min="today"
                                        v-model="startDate"
                                    />
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label for="end-date" class="form-label small fw-semibold">Data de fim</label>
                                    <input
                                        id="end-date"
                                        type="date"
                                        class="form-control"
                                        :min="startDate || today"
                                        v-model="endDate"
                                    />
                                </div>
                            </div>

                            <div v-if="numberOfDays > 0" class="alert alert-info py-2 small d-flex align-items-center gap-2">
                                <CalendarDays :size="16" />
                                {{ numberOfDays }} {{ numberOfDays === 1 ? 'dia' : 'dias' }} de cuidado
                            </div>
                        </section>

                        <section class="card bg-white border border-primary rounded-4 p-3 p-md-4 mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h5 fw-semibold mb-0">Animais</h2>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary rounded-pill d-inline-flex align-items-center gap-1 px-3"
                                    @click="showAddPetModal = true"
                                >
                                    <Plus :size="15" />
                                    <span>Adicionar animal</span>
                                </button>
                            </div>

                            <div v-if="loadError" class="alert alert-danger py-2 small" role="alert">
                                {{ loadError }}
                            </div>

                            <div v-else-if="animals.length === 0" class="text-center py-4 text-muted">
                                <Dog :size="48" class="mb-2 opacity-50" />
                                <p class="mb-2">Não tens animais registados.</p>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-primary rounded-pill px-4"
                                    @click="showAddPetModal = true"
                                >
                                    Adicionar o meu primeiro animal
                                </button>
                            </div>

                            <div v-else class="d-flex flex-column gap-2">
                                <div
                                    v-for="animal in animals"
                                    :key="animal.id"
                                    class="animal-option p-3 rounded-3 border d-flex align-items-center gap-3"
                                    :class="{ 'animal-selected': selectedAnimals.includes(animal.id) }"
                                    @click="toggleAnimal(animal.id)"
                                >
                                    <div class="animal-avatar d-flex align-items-center justify-content-center rounded-circle flex-shrink-0 overflow-hidden">
                                        <img
                                            v-if="animal.photo"
                                            :src="animal.photo"
                                            :alt="animal.name"
                                            class="w-100 h-100 object-fit-cover"
                                        />
                                        <Dog v-else :size="24" class="text-primary" />
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="fw-semibold mb-0">{{ animal.name }}</p>
                                        <small class="text-muted">{{ animal.species }} {{ animal.breed ? '• ' + animal.breed : '' }}</small>
                                    </div>
                                    <div class="form-check form-check-reverse m-0">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            :checked="selectedAnimals.includes(animal.id)"
                                            @click.stop="toggleAnimal(animal.id)"
                                        />
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="card bg-white border border-primary rounded-4 p-3 p-md-4 mb-4">
                            <h2 class="h5 fw-semibold mb-3">Notas (opcional)</h2>
                            <textarea
                                class="form-control"
                                rows="3"
                                placeholder="Instruções especiais, alergias, rotinas..."
                                v-model="notes"
                            ></textarea>
                        </section>
                    </div>

                    <!-- Coluna direita — Resumo -->
                    <div class="col-12 col-lg-5">
                        <section class="card bg-white border border-primary rounded-4 p-3 p-md-4 mb-4 sticky-top" style="top: 1rem;">
                            <h2 class="h5 fw-semibold mb-3">Resumo</h2>

                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="petsitter-avatar d-flex align-items-center justify-content-center rounded-circle border border-primary flex-shrink-0 overflow-hidden">
                                    <img
                                        v-if="petsitter?.avatar_url"
                                        :src="petsitter.avatar_url"
                                        alt="Foto de perfil"
                                        class="w-100 h-100 object-fit-cover"
                                    />
                                    <UserRound v-else :size="32" class="text-primary" />
                                </div>
                                <div>
                                    <p class="fw-bold mb-0">{{ displayName }}</p>
                                    <small class="text-muted">{{ displayLocation }}</small>
                                </div>
                            </div>

                            <div class="border-bottom py-2">
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Preço por dia</span>
                                    <span class="fw-semibold">€ {{ displayPrice }}</span>
                                </div>
                            </div>

                            <div class="border-bottom py-2">
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Dias</span>
                                    <span class="fw-semibold">{{ numberOfDays || 0 }}</span>
                                </div>
                            </div>

                            <div class="border-bottom py-2">
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Animais</span>
                                    <span class="fw-semibold">{{ selectedAnimals.length }}</span>
                                </div>
                            </div>

                            <div class="py-2 d-flex justify-content-between">
                                <span class="fw-bold">Total</span>
                                <span class="fw-bold fs-5 text-primary">€ {{ totalPrice.toFixed(2) }}</span>
                            </div>

                            <button
                                type="button"
                                class="btn btn-primary w-100 rounded-pill mt-3 py-2"
                                :disabled="!isFormValid || submitting"
                                @click="submitBooking"
                            >
                                <span v-if="submitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                {{ submitting ? 'A criar...' : 'Confirmar Reserva' }}
                            </button>

                            <p class="text-muted small text-center mt-2 mb-0">
                                A reserva será enviada para aprovação do petsitter.
                            </p>
                        </section>
                    </div>
                </div>
            </div>
        </main>

        <ModalNotifications
            :show="showNotifications"
            @close="showNotifications = false"
            @mark-as-read="unreadCount = Math.max(0, unreadCount - 1)"
        />

        <ModalBookingPageAddPet
            :show="showAddPetModal"
            @close="showAddPetModal = false"
            @created="handleAnimalCreated"
        />
    </div>
</template>

<style scoped>
.patudo-page {
    background-color: #fffaf3;
}

.nav-pills .nav-link.active {
    background-color: var(--patudo-terracota, #c2410c) !important;
    color: #ffffff !important;
}

.nav-pills .nav-link:not(.active):hover {
    color: var(--patudo-terracota, #c2410c) !important;
}

.petsitter-avatar {
    width: 56px;
    height: 56px;
}

.animal-option {
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease;
    background-color: #fff;
}

.animal-option:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.05);
}

.animal-selected {
    background-color: rgba(var(--bs-primary-rgb), 0.08) !important;
    border-color: var(--bs-primary) !important;
}

.animal-avatar {
    width: 48px;
    height: 48px;
    background-color: #fffaf3;
}
</style>
