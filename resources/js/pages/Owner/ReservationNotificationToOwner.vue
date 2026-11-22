<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Bell,
    LogOut,
    MessageCircle,
    UserRound,
    ChevronLeft,
    CalendarDays,
    CheckCircle,
    XCircle,
    Plus,
    MapPin
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import type { Animal } from '@/types';
import { useNotifications } from '@/composables/useNotifications';
import ModalNotifications from '@/pages/Modals/ModalNotifications.vue';

// /reservations            -> lista de reservas (cartões)
// /reservations?booking=ID -> detalhe de uma reserva (abaixo)
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

// ---------- Estado ----------
const booking = ref<BookingData | null>(null);
const petsitter = ref<PetsitterInfo | null>(null);
const bookings = ref<BookingData[]>([]);
const petsitters = ref<Record<number, PetsitterInfo>>({});
const loading = ref(false);
const processingId = ref<number | null>(null);
const feedback = ref('');
const feedbackType = ref<'success' | 'error'>('success');

const showNotifications = ref(false);
const { unreadCount } = useNotifications();

function setFeedback(message: string, type: 'success' | 'error') {
    feedback.value = message;
    feedbackType.value = type;
}

// ---------- Helpers ----------
const displayName = computed(() => petsitter.value?.user?.name ?? 'Petsitter');
const displayLocation = computed(() => petsitter.value?.service_location ?? petsitter.value?.city ?? '--');
const displayPrice = computed(() => petsitter.value?.price_per_day ?? 0);

function daysBetween(startDate: string, endDate: string): number {
    const start = new Date(startDate);
    const end = new Date(endDate);
    const diff = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1;
    return diff > 0 ? diff : 0;
}

function periodLabel(b: BookingData): string {
    const days = daysBetween(b.start_date, b.end_date);
    return `${days} ${days === 1 ? 'dia' : 'dias'}`;
}

const numberOfDays = computed(() => {
    if (!booking.value) return 0;
    return daysBetween(booking.value.start_date, booking.value.end_date);
});

function formatPrice(value: number | string): string {
    return `${Number(value).toLocaleString('pt-PT', { maximumFractionDigits: 2 })} €`;
}

function serviceLabel(b: BookingData): string {
    const species = (b.animals?.[0]?.species ?? '').toString().toLowerCase();
    if (species.includes('gat') || species.includes('cat')) return 'Cat Sitting';
    if (species.includes('cão') || species.includes('cao') || species.includes('dog')) return 'Dog Sitting';
    return 'Pet Sitting';
}

function animalNames(b: BookingData): string {
    return (b.animals ?? []).map((a) => a.name).join(', ') || '--';
}

function sitterOf(b: BookingData): PetsitterInfo | undefined {
    return petsitters.value[b.petsitter_id];
}

function sitterName(b: BookingData): string {
    return sitterOf(b)?.user?.name ?? 'Petsitter';
}

function sitterLocation(b: BookingData): string {
    const p = sitterOf(b);
    return p?.service_location ?? p?.city ?? '--';
}

const statusLabel: Record<string, string> = {
    pending: 'Aguardando confirmação',
    approved: 'Confirmada',
    rejected: 'Rejeitada',
    cancelled: 'Cancelada',
    completed: 'Concluída',
};

// Rótulos curtos usados nos cartões da lista
const statusShort: Record<string, string> = {
    pending: 'Pendente',
    approved: 'Aceite',
    rejected: 'Rejeitada',
    cancelled: 'Cancelada',
    completed: 'Concluída',
};

const statusIcon: Record<string, 'CheckCircle' | 'XCircle'> = {
    pending: 'CheckCircle',
    approved: 'CheckCircle',
    rejected: 'XCircle',
    cancelled: 'XCircle',
    completed: 'CheckCircle',
};

// ---------- Lista ----------
const loadBookings = async () => {
    loading.value = true;
    feedback.value = '';
    try {
        const res = await apiFetch('/api/bookings');
        if (!res.ok) {
            setFeedback('Não foi possível carregar as reservas.', 'error');
            return;
        }
        const data: BookingData[] = (await res.json()).data ?? [];

        // Carregar info de cada petsitter (uma vez por id)
        const ids = [...new Set(data.map((b) => b.petsitter_id))];
        await Promise.all(
            ids.map(async (id) => {
                try {
                    const r = await apiFetch(`/api/petsitters/${id}`);
                    if (r.ok) petsitters.value[id] = (await r.json()).data;
                } catch {
                    /* ignora: o cartão mostra "Petsitter" */
                }
            }),
        );

        bookings.value = data;
    } catch {
        setFeedback('Erro de conexão. Tente novamente.', 'error');
    } finally {
        loading.value = false;
    }
};

const cancelFromList = async (b: BookingData) => {
    if (!confirm('Tem a certeza que deseja cancelar esta reserva?')) return;
    processingId.value = b.id;
    feedback.value = '';
    try {
        const res = await apiFetch(`/api/bookings/${b.id}/status`, {
            method: 'PATCH',
            body: JSON.stringify({ status: 'cancelled' }),
        });
        if (res.ok) {
            b.status = 'cancelled';
            setFeedback('Reserva cancelada com sucesso.', 'success');
        } else {
            setFeedback('Não foi possível cancelar a reserva.', 'error');
        }
    } catch {
        setFeedback('Erro de conexão. Tente novamente.', 'error');
    } finally {
        processingId.value = null;
    }
};

// ---------- Detalhe ----------
const loadBooking = async () => {
    if (!bookingId) return;
    loading.value = true;
    try {
        const res = await apiFetch(`/api/bookings/${bookingId}`);
        if (res.ok) {
            const data = (await res.json()).data;
            booking.value = data;

            // Carregar info do petsitter
            const petsitterRes = await apiFetch(`/api/petsitters/${data.petsitter_id}`);
            if (petsitterRes.ok) petsitter.value = (await petsitterRes.json()).data;
        } else {
            setFeedback('Não foi possível carregar a reserva.', 'error');
        }
    } catch {
        setFeedback('Erro de conexão. Tente novamente.', 'error');
    } finally {
        loading.value = false;
    }
};

const cancelBooking = async () => {
    if (!bookingId) return;
    if (!confirm('Tem a certeza que deseja cancelar a reserva?')) return;
    processingId.value = Number(bookingId);
    feedback.value = '';
    try {
        const res = await apiFetch(`/api/bookings/${bookingId}/status`, {
            method: 'PATCH',
            body: JSON.stringify({ status: 'cancelled' }),
        });
        if (res.ok) {
            booking.value!.status = 'cancelled';
            setFeedback('Reserva cancelada com sucesso.', 'success');
        } else {
            setFeedback('Não foi possível cancelar a reserva.', 'error');
        }
    } catch {
        setFeedback('Erro de conexão. Tente novamente.', 'error');
    } finally {
        processingId.value = null;
    }
};

onMounted(() => {
    if (bookingId) {
        void loadBooking();
    } else {
        void loadBookings();
    }
});
</script>

<template>
    <Head :title="bookingId ? 'Notificação de Reserva' : 'As minhas reservas'" />

    <main class="min-vh-100 py-4 patudo-page">
        <div class="container">

            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg bg-white border border-primary rounded-4 px-3 px-md-4">
                <a class="navbar-brand fw-bold fs-3 d-flex align-items-center gap-2" href="/">
                    <img :src="logoPatudo" alt="Patudo" width="70" height="70" />
                    <span>Patudo</span>
                </a>

                <div class="nav nav-pills border rounded-pill p-1 mx-auto">
                    <a class="nav-link text-dark rounded-pill px-4" href="/">Início</a>
                    <a class="nav-link text-dark rounded-pill px-4" href="/orders">Pedidos</a>
                    <a class="nav-link text-dark rounded-pill px-4" href="/favorites">Favoritos</a>
                    <a class="nav-link active rounded-pill px-4" href="/reservations" aria-current="page">Reservas</a>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <a href="/messages" class="text-dark" aria-label="Mensagens">
                        <MessageCircle :size="25" />
                    </a>

                    <button
                        type="button"
                        class="text-dark bg-transparent border-0 p-0 position-relative"
                        aria-label="Notificações"
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

                    <a href="/profile" class="text-dark" aria-label="Perfil">
                        <UserRound :size="25" />
                    </a>

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

            <!-- Topo da lista: botão "Pedir reserva" -->
            <div v-if="!bookingId" class="d-flex justify-content-end mt-4">
                <a
                    href="/"
                    class="btn btn-primary rounded-pill d-inline-flex align-items-center gap-2 px-3"
                >
                    <Plus :size="18" />
                    Pedir reserva
                </a>
            </div>

            <!-- Botão de voltar -->
            <div v-else class="mt-4">
                <a
                    href="/reservations"
                    class="btn btn-outline-secondary rounded-pill d-inline-flex align-items-center gap-2"
                >
                    <ChevronLeft :size="16" />
                    Voltar às reservas
                </a>
            </div>

            <h1 class="text-center fw-bold display-5 mt-4 mb-5 page-title">
                {{ bookingId ? 'Notificação de Reserva' : 'As minhas reservas' }}
            </h1>

            <!-- Feedback -->
            <div
                v-if="feedback"
                class="alert py-2 small"
                :class="feedbackType === 'success' ? 'alert-success' : 'alert-danger'"
                role="alert"
            >
                {{ feedback }}
            </div>

            <!-- Loading -->
            <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-secondary" role="status">
                    <span class="visually-hidden">A carregar...</span>
                </div>
            </div>


            <template v-else-if="!bookingId">
                <div v-if="!loading && bookings.length === 0 && !feedback" class="text-center text-secondary py-5">
                    Ainda não tens reservas.
                </div>

                <article
                    v-for="b in bookings"
                    :key="b.id"
                    class="booking-card bg-white mb-3"
                >
                    <!-- Avatar do petsitter -->
                    <div class="booking-avatar rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 overflow-hidden">
                        <img
                            v-if="sitterOf(b)?.avatar_url"
                            :src="sitterOf(b)!.avatar_url!"
                            alt="Foto de perfil"
                            class="w-100 h-100 object-fit-cover"
                        />
                        <UserRound v-else :size="56" class="text-secondary" />
                    </div>

                    <!-- Nome / local / animal -->
                    <div class="booking-info">
                        <div>
                            <h2 class="booking-name mb-0">{{ sitterName(b) }}</h2>
                            <small class="booking-location d-flex align-items-center gap-1">
                                <MapPin :size="17" />
                                {{ sitterLocation(b) }}
                            </small>
                        </div>

                        <p class="booking-animal mb-0">
                            <strong>{{ (b.animals?.length ?? 0) > 1 ? 'Animais' : 'Animal' }}:</strong>
                            {{ animalNames(b) }}
                        </p>
                    </div>

                    <!-- Estado / serviço / preço / ações -->
                    <div class="booking-side">
                        <span class="status-pill" :class="`status-${b.status}`">
                            {{ statusShort[b.status] || b.status }}
                        </span>

                        <h3 class="booking-service mb-0">{{ serviceLabel(b) }}</h3>

                        <small class="booking-period fw-semibold">
                            Período: {{ periodLabel(b) }}
                        </small>

                        <p class="booking-price mb-0">Preço: {{ formatPrice(b.total_price) }}</p>

                        <div class="booking-actions d-flex gap-2 mt-2 w-100">
                            <!-- Reserva aceite: Conversar + Cancelar -->
                            <template v-if="b.status === 'approved'">
                                <a href="/messages" class="btn btn-conversar btn-sm rounded-pill flex-fill">
                                    Conversar
                                </a>

                                <button
                                    type="button"
                                    class="btn btn-outline-danger btn-sm rounded-pill flex-fill"
                                    :disabled="processingId === b.id"
                                    @click="cancelFromList(b)"
                                >
                                    Cancelar
                                </button>
                            </template>

                            <!-- Reserva pendente: Cancelar pedido -->
                            <button
                                v-else-if="b.status === 'pending'"
                                type="button"
                                class="btn btn-outline-danger btn-sm rounded-pill flex-fill"
                                :disabled="processingId === b.id"
                                @click="cancelFromList(b)"
                            >
                                Cancelar pedido
                            </button>

                            <!-- Outros estados: ver detalhe -->
                            <a
                                v-else
                                :href="`/reservations?booking=${b.id}`"
                                class="btn btn-outline-secondary btn-sm rounded-pill flex-fill"
                            >
                                Ver detalhe
                            </a>
                        </div>
                    </div>
                </article>
            </template>

            <div v-else-if="booking" class="row g-4 justify-content-center">
                <div class="col-12 col-lg-8">
                    <!-- Estado da reserva -->
                    <section class="card bg-white border border-primary rounded-4 p-3 p-md-4 mb-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <component
                                :is="statusIcon[booking.status] === 'XCircle' ? XCircle : CheckCircle"
                                :size="32"
                                :class="{
                                    'text-warning': booking.status === 'pending',
                                    'text-success': ['approved', 'completed'].includes(booking.status),
                                    'text-danger': booking.status === 'rejected',
                                    'text-secondary': booking.status === 'cancelled',
                                }"
                            />

                            <div>
                                <h2 class="h5 fw-semibold mb-0">
                                    {{ statusLabel[booking.status] || booking.status }}
                                </h2>
                                <small class="text-secondary">Reserva #{{ booking.id }}</small>
                            </div>
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
                                <UserRound v-else :size="32" class="text-secondary" />
                            </div>

                            <div>
                                <p class="fw-bold mb-0">{{ displayName }}</p>
                                <small class="text-secondary">{{ displayLocation }}</small>
                            </div>
                        </div>

                        <!-- Datas -->
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <div class="d-flex align-items-center gap-2">
                                    <CalendarDays :size="18" class="text-primary" />
                                    <div>
                                        <small class="text-secondary d-block">Início</small>
                                        <span class="fw-semibold">
                                            {{ new Date(booking.start_date).toLocaleDateString('pt-PT') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="d-flex align-items-center gap-2">
                                    <CalendarDays :size="18" class="text-primary" />
                                    <div>
                                        <small class="text-secondary d-block">Fim</small>
                                        <span class="fw-semibold">
                                            {{ new Date(booking.end_date).toLocaleDateString('pt-PT') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Animais: apenas os nomes, sem avatares -->
                        <div v-if="booking.animals && booking.animals.length" class="mb-3">
                            <h3 class="h6 fw-semibold mb-2">Animais</h3>

                            <div class="d-flex flex-wrap gap-2">
                                <span
                                    v-for="animal in booking.animals"
                                    :key="animal.id"
                                    class="fw-semibold small"
                                >
                                    {{ animal.name }}
                                </span>
                            </div>
                        </div>

                        <!-- Preço -->
                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-secondary">Dias</span>
                                <span class="fw-semibold">{{ numberOfDays }}</span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-secondary">Preço por dia</span>
                                <span class="fw-semibold">{{ formatPrice(displayPrice) }}</span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="fw-bold fs-5">Total</span>
                                <span class="fw-bold fs-5 text-primary">
                                    {{ formatPrice(booking.total_price) }}
                                </span>
                            </div>
                        </div>

                        <!-- Ações -->
                        <div v-if="booking.status === 'pending'" class="mt-3">
                            <button
                                type="button"
                                class="btn btn-outline-danger w-100 rounded-pill py-2"
                                :disabled="processingId !== null"
                                @click="cancelBooking"
                            >
                                {{ processingId !== null ? 'A processar...' : 'Cancelar Reserva' }}
                            </button>

                            <p class="text-secondary small text-center mt-2 mb-0">
                                O petsitter ainda não confirmou a reserva.
                            </p>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <ModalNotifications
            :show="showNotifications"
            @close="showNotifications = false"
            @mark-as-read="unreadCount = Math.max(0, unreadCount - 1)"
        />
    </main>
</template>

<style scoped>
.patudo-page {
    background-color: var(--patudo-branco, #fffaf3);
}

.page-title {
    color: var(--patudo-castanhoDark, #2e1d14);
}

/* ---------- Cartão de reserva (lista) ---------- */
.booking-card {
    display: flex;
    align-items: center;
    gap: 2rem;
    padding: 1.25rem 1.5rem;
    border: 1px solid var(--patudo-terracota, #c2410c);
    border-radius: 1.25rem;
    position: relative;
}

.booking-avatar {
    width: 120px;
    height: 120px;
    border: 1px solid var(--patudo-terracota, #2e1d14);
    border-radius: 2rem;
    background-color: #ffffff;
}

.booking-info {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    align-self: stretch;
    justify-content: space-between;
    padding: 0.75rem 0;
}

.booking-name {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--patudo-castanhoDark, #2e1d14);
}

.booking-location {
    font-size: 0.85rem;
    color: var(--patudo-castanhoDark, #2e1d14);
    margin-top: 0.1rem;
}

.booking-animal {
    font-size: 1.05rem;
    color: var(--patudo-castanhoDark, #2e1d14);
}

.booking-side {
    flex: 0 0 240px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.booking-service {
    font-size: 1.5rem;
    font-weight: 500;
    color: var(--patudo-castanhoDark, #2e1d14);
    margin-top: 0.9rem;
}

.booking-period {
    font-size: 0.85rem;
    color: var(--patudo-castanhoDark, #2e1d14);
}

.booking-price {
    align-self: center;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--patudo-castanhoDark, #2e1d14);
    margin-top: 0.25rem;
}

/* Etiqueta de estado no topo */
.status-pill {
    width: 100%;
    padding: 0.2rem 0.75rem;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 500;
    text-align: center;
}

.status-approved,
.status-completed {
    background-color: #a3cf9f;
    color: #4f6f52;
}

.status-pending {
    background-color: #f8cdb0;
    color: #f97316;
}

.status-rejected {
    background-color: #f9c9c9;
    color: #dc2626;
}

.status-cancelled {
    background-color: #e6dfd9;
    color: #7a6657;
}

/* Conversar: o .btn-success do Bootstrap ignora --bs-success, por isso usa-se a cor do tema */
.btn-conversar {
    color: #ffffff;
    background-color: var(--patudo-verde, #4f6f52);
    border: 1px solid var(--patudo-verde, #4f6f52);
}

.btn-conversar:hover,
.btn-conversar:focus {
    color: #ffffff;
    background-color: #3f5a42;
    border-color: #3f5a42;
}

.petsitter-avatar {
    width: 56px;
    height: 56px;
}

/* ---------- Responsivo ---------- */
@media (max-width: 767.98px) {
    .booking-card {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }

    .booking-avatar {
        align-self: center;
    }

    .booking-side {
        flex-basis: auto;
    }

    .booking-price {
        align-self: center;
    }
}
</style>
