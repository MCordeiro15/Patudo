<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { LogOut, UserRound, ChevronLeft, CalendarDays, Dog, CheckCircle, Star } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import AdminNavigationMenu from '@/components/AdminNavigationMenu.vue';
import HeaderNotificationsChat from '@/components/HeaderNotificationsChat.vue';
import type { Animal } from '@/types';

// O id da reserva vem do URL: /booking-confirmed?booking=ID
const bookingId = new URLSearchParams(window.location.search).get('booking');

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

type BookingData = {
    id: number;
    user_id: number;
    petsitter_id: number;
    start_date: string;
    end_date: string;
    status: string;
    total_price: number;
    animals: Animal[];
    created_at: string;
};

type PetsitterInfo = {
    id: number;
    user?: { name: string };
    avatar_url: string | null;
    service_location: string | null;
    city: string | null;
    price_per_day: number | null;
    average_rating: number | null;
    reviews_count: number;
};

const booking = ref<BookingData | null>(null);
const petsitter = ref<PetsitterInfo | null>(null);
const feedback = ref('');
const loading = ref(false);

const displayName = computed(() => (petsitter.value ? (petsitter.value.user?.name ?? 'Petsitter') : 'Petsitter'));
const displayLocation = computed(() => (petsitter.value ? (petsitter.value.service_location ?? petsitter.value.city ?? '--') : '--'));

const numberOfDays = computed(() => {
    if (!booking.value) return 0;
    const start = new Date(booking.value.start_date);
    const end = new Date(booking.value.end_date);
    const diff = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1;
    return diff > 0 ? diff : 0;
});

const isPastBooking = computed(() => {
    if (!booking.value) return false;
    return new Date(booking.value.end_date) < new Date();
});

const loadBooking = async () => {
    if (!bookingId) return;
    loading.value = true;
    try {
        const res = await apiFetch(`/api/bookings/${bookingId}`);
        if (res.ok) {
            const data = (await res.json()).data;
            booking.value = data;
            const petsitterRes = await apiFetch(`/api/petsitters/${data.petsitter_id}`);
            if (petsitterRes.ok) petsitter.value = (await petsitterRes.json()).data;
        } else {
            feedback.value = 'Não foi possível carregar a reserva.';
        }
    } catch {
        feedback.value = 'Erro de conexão. Tente novamente.';
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    void loadBooking();
});
</script>

<template>
    <Head title="Reserva Confirmada" />

    <main class="min-vh-100 py-4 patudo-page patudo-page-shell">
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
                    <AdminNavigationMenu />
                </div>

                <div class="d-flex align-items-center gap-3">
                    <HeaderNotificationsChat />
                    <button
                        type="button"
                        class="text-dark p-0 border-0 bg-none"
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
                    href="/reservations"
                    class="btn btn-outline-secondary rounded-pill d-inline-flex align-items-center gap-2"
                >
                    <ChevronLeft :size="16" />
                    Voltar às reservas
                </a>
            </div>

            <h1 class="text-center fw-bold display-5 mt-3 mb-4">Reserva Confirmada</h1>

            <!-- Feedback -->
            <div v-if="feedback" class="alert py-2 small" :class="feedback.includes('sucesso') ? 'alert-success' : 'alert-danger'" role="alert">
                {{ feedback }}
            </div>

            <!-- Loading -->
            <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">A carregar...</span>
                </div>
            </div>

            <div v-else-if="booking" class="row g-4 justify-content-center">
                <div class="col-12 col-lg-8">
                    <!-- Confirmação -->
                    <section class="card bg-white border border-primary rounded-4 p-3 p-md-4 mb-4">
                        <div class="text-center mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 mb-3" style="width: 72px; height: 72px;">
                                <CheckCircle :size="48" class="text-success" />
                            </div>
                            <h2 class="h4 fw-bold text-success mb-1">Reserva Confirmada!</h2>
                            <p class="text-muted mb-0">O petsitter confirmou a sua reserva.</p>
                        </div>

                        <!-- Info do Petsitter -->
                        <div class="d-flex align-items-center gap-3 mb-3 p-3 rounded-3 bg-light">
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
                                <div v-if="petsitter?.average_rating" class="d-flex align-items-center gap-1 mt-1">
                                    <Star :size="14" class="text-warning fill-warning" />
                                    <small class="fw-semibold">{{ petsitter.average_rating }}</small>
                                    <small class="text-muted">({{ petsitter.reviews_count }} avaliações)</small>
                                </div>
                            </div>
                        </div>

                        <!-- Datas -->
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <div class="d-flex align-items-center gap-2">
                                    <CalendarDays :size="18" class="text-primary" />
                                    <div>
                                        <small class="text-muted d-block">Início</small>
                                        <span class="fw-semibold">{{ new Date(booking.start_date).toLocaleDateString('pt-PT') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center gap-2">
                                    <CalendarDays :size="18" class="text-primary" />
                                    <div>
                                        <small class="text-muted d-block">Fim</small>
                                        <span class="fw-semibold">{{ new Date(booking.end_date).toLocaleDateString('pt-PT') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Animais -->
                        <div v-if="booking.animals && booking.animals.length" class="mb-3">
                            <h3 class="h6 fw-semibold mb-2">Animais</h3>
                            <div class="d-flex flex-wrap gap-2">
                                <div
                                    v-for="animal in booking.animals"
                                    :key="animal.id"
                                    class="d-flex align-items-center gap-2 p-2 rounded-3 border"
                                >
                                    <div class="animal-avatar-sm d-flex align-items-center justify-content-center rounded-circle flex-shrink-0">
                                        <img
                                            v-if="animal.photo"
                                            :src="animal.photo"
                                            :alt="animal.name"
                                            class="w-100 h-100 object-fit-cover"
                                        />
                                        <Dog v-else :size="16" class="text-primary" />
                                    </div>
                                    <span class="fw-semibold small">{{ animal.name }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Preço -->
                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Dias</span>
                                <span class="fw-semibold">{{ numberOfDays }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="fw-bold fs-5">Total</span>
                                <span class="fw-bold fs-5 text-primary">€ {{ booking.total_price.toFixed(2) }}</span>
                            </div>
                        </div>

                        <!-- Avaliação (se reserva já passou) -->
                        <div v-if="isPastBooking && booking.status === 'completed'" class="mt-3">
                            <a
                                :href="`/review?booking=${bookingId}`"
                                class="btn btn-primary w-100 rounded-pill py-2"
                            >
                                Avaliar esta reserva
                            </a>
                        </div>

                        <p class="text-muted small text-center mt-3 mb-0">
                            Reserva #{{ booking.id }} • Criada em {{ new Date(booking.created_at).toLocaleDateString('pt-PT') }}
                        </p>
                    </section>
                </div>
            </div>
        </div>

    </main>
</template>

<style scoped>
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

.animal-avatar-sm {
    width: 32px;
    height: 32px;
    background-color: #fffaf3;
}

.fill-warning {
    fill: currentColor;
}
</style>
