<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Bell,
    LogOut,
    MessageCircle,
    UserRound,
    ChevronLeft,
    CalendarDays,
    CheckCircle,
    XCircle,
    Plus
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import BookingList from '@/components/BookingList.vue';
import type { Animal } from '@/types';
import { useNotifications } from '@/composables/useNotifications';
import ModalNotifications from '@/pages/Modals/ModalNotifications.vue';

// /reservations            -> lista de reservas (cartões, no BookingList com role="owner")
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
    // nome, localidade e foto do petsitter e serviço já vêm na reserva (BookingResource)
    petsitter?: { id: number; name: string | null; location: string | null; avatar_url: string | null } | null;
    owner?: { id: number; name: string | null; location: string | null; avatar_url: string | null } | null;
    service?: { id: number; name: string; price: number } | null;
};

type PetsitterInfo = {
    id: number;
    user?: { name: string };
    avatar_url: string | null;
    service_location: string | null;
    city: string | null;
    price_per_day: number | null;
};

// ---------- Estado ----------
const booking = ref<BookingData | null>(null);
const petsitter = ref<PetsitterInfo | null>(null);
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

const numberOfDays = computed(() => {
    if (!booking.value) return 0;
    return daysBetween(booking.value.start_date, booking.value.end_date);
});

function formatPrice(value: number | string): string {
    return `${Number(value).toLocaleString('pt-PT', { maximumFractionDigits: 2 })} €`;
}

// Datas "2026-11-23" (sem hora): new Date() lia-as em UTC e, nos Açores, mostrava o dia anterior
function formatDay(day: string): string {
    const match = day?.match(/^(\d{4})-(\d{2})-(\d{2})/);
    return match ? `${match[3]}/${match[2]}/${match[1]}` : new Date(day).toLocaleDateString('pt-PT');
}

// Quem abre o detalhe pode ser o dono ou o petsitter da reserva (bookings.petsitter_id é o id do user)
const page = usePage<{ auth: { user: { id: number; is_petsitter?: boolean } | null } }>();
const viewerIsPetsitter = computed(() => !!booking.value && booking.value.petsitter_id === page.props.auth.user?.id);

// O cartão do detalhe mostra a outra pessoa: o petsitter para o dono, o dono para o petsitter.
// Vem da própria reserva (/api/petsitters/{id} com o id do user dava o petsitter errado)
function petsitterFromBooking(b: BookingData): PetsitterInfo {
    const isPetsitter = b.petsitter_id === page.props.auth.user?.id;
    const other = isPetsitter ? b.owner : b.petsitter;
    return {
        id: isPetsitter ? b.user_id : b.petsitter_id,
        user: { name: other?.name ?? (isPetsitter ? 'Proprietário' : 'Petsitter') },
        avatar_url: other?.avatar_url ?? null,
        service_location: other?.location ?? null,
        city: null,
        // preço do serviço; reservas antigas sem serviço: total a dividir pelos dias
        price_per_day: b.service?.price
            ?? Number(b.total_price) / (daysBetween(b.start_date, b.end_date) || 1),
    };
}

const statusLabel: Record<string, string> = {
    pending: 'Aguardando confirmação',
    approved: 'Confirmada',
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

// ---------- Detalhe ----------
const loadBooking = async () => {
    if (!bookingId) return;
    loading.value = true;
    try {
        const res = await apiFetch(`/api/bookings/${bookingId}`);
        if (res.ok) {
            const data: BookingData = (await res.json()).data;
            booking.value = data;
            petsitter.value = petsitterFromBooking(data);
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
    }
});
</script>

<template>
    <Head :title="bookingId ? 'Notificação de Reserva' : 'As minhas reservas'" />

    <main class="min-vh-100 py-4 patudo-page patudo-page-shell">
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

            <!-- Topo da lista: botão "Pedir reserva" (o petsitter não pode reservar, por isso não o vê) -->
            <div v-if="!bookingId" class="d-flex justify-content-end mt-4">
                <a
                    v-if="!page.props.auth.user?.is_petsitter"
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


            <!-- Lista de reservas -->
            <BookingList v-else-if="!bookingId" role="owner" />

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
                                            {{ formatDay(booking.start_date) }}
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
                                            {{ formatDay(booking.end_date) }}
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
                        <!-- Cancelar é do dono; o petsitter responde nos Pedidos -->
                        <div v-if="booking.status === 'pending' && !viewerIsPetsitter" class="mt-3">
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

.petsitter-avatar {
    width: 56px;
    height: 56px;
}
</style>
