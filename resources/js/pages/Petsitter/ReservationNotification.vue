<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Bell, LogOut, MessageCircle, UserRound, ChevronLeft, CalendarDays, Dog, CheckCircle, XCircle } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import type { Animal } from '@/types';
import { useNotifications } from '@/composables/useNotifications';
import ModalNotifications from '@/pages/Modals/ModalNotifications.vue';
import BookingList from '@/components/BookingList.vue';

// /orders            -> lista dos pedidos pendentes (BookingList)
// /orders?booking=ID -> detalhe de um pedido (abaixo)
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

type OwnerInfo = {
    name: string;
    avatar_url: string | null;
};

const booking = ref<BookingData | null>(null);
const owner = ref<OwnerInfo | null>(null);
const feedback = ref('');
const loading = ref(false);

const numberOfDays = computed(() => {
    if (!booking.value) return 0;
    const start = new Date(booking.value.start_date);
    const end = new Date(booking.value.end_date);
    const diff = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1;
    return diff > 0 ? diff : 0;
});

const loadBooking = async () => {
    if (!bookingId) return;
    loading.value = true;
    try {
        const res = await apiFetch(`/api/bookings/${bookingId}`);
        if (res.ok) {
            const data = (await res.json()).data;
            booking.value = data;
            // Carregar info do dono (user_id)
            const ownerRes = await apiFetch(`/api/users/${data.user_id}`);
            if (ownerRes.ok) {
                const userData = (await ownerRes.json()).data;
                owner.value = {
                    name: userData.name ?? 'Proprietário',
                    avatar_url: userData.avatar_url ?? null,
                };
            }
        } else {
            feedback.value = 'Não foi possível carregar a reserva.';
        }
    } catch {
        feedback.value = 'Erro de conexão. Tente novamente.';
    } finally {
        loading.value = false;
    }
};

const updateStatus = async (newStatus: 'approved' | 'rejected') => {
    if (!bookingId) return;
    const confirmMsg = newStatus === 'approved'
        ? 'Deseja confirmar esta reserva?'
        : 'Deseja rejeitar esta reserva?';
    if (!confirm(confirmMsg)) return;
    loading.value = true;
    feedback.value = '';
    try {
        const res = await apiFetch(`/api/bookings/${bookingId}/status`, {
            method: 'PATCH',
            body: JSON.stringify({ status: newStatus }),
        });
        if (res.ok) {
            booking.value!.status = newStatus;
            feedback.value = newStatus === 'approved'
                ? 'Reserva confirmada com sucesso!'
                : 'Reserva rejeitada.';
        } else {
            feedback.value = 'Não foi possível atualizar o estado da reserva.';
        }
    } catch {
        feedback.value = 'Erro de conexão. Tente novamente.';
    } finally {
        loading.value = false;
    }
};

const showNotifications = ref(false);
const { unreadCount } = useNotifications();

onMounted(() => {
    void loadBooking();
});
</script>

<template>
    <Head title="Pedido de Reserva" />

    <main class="min-vh-100 py-4 patudo-page">
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
                    <a class="nav-link active rounded-pill px-4" href="/orders">Pedidos</a>
                    <a class="nav-link rounded-pill px-4" href="/favorites">Favoritos</a>
                    <a class="nav-link rounded-pill px-4" href="/reservations">Reservas</a>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <a href="/messages" class="text-dark"
                        ><MessageCircle :size="25"
                    /></a>
                    <button type="button" class="text-dark bg-transparent border-0 p-0 position-relative" @click="showNotifications = true">
                        <Bell :size="25" />
                        <span v-if="unreadCount > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </span>
                    </button>
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

            <!-- Botão de voltar (só no detalhe de um pedido) -->
            <div v-if="bookingId" class="mt-4">
                <a
                    href="/orders"
                    class="btn btn-outline-secondary rounded-pill d-inline-flex align-items-center gap-2"
                >
                    <ChevronLeft :size="16" />
                    Voltar aos pedidos
                </a>
            </div>

            <h1 class="text-center fw-bold display-5 mt-3 mb-4">{{ bookingId ? 'Pedido de Reserva' : 'Pedidos de reserva' }}</h1>

            <!-- Sem ?booking no URL: lista dos pedidos pendentes -->
            <BookingList v-if="!bookingId" :pending="true" />

            <!-- Feedback -->
            <div v-if="feedback" class="alert py-2 small" :class="feedback.includes('sucesso') || feedback.includes('confirmada') ? 'alert-success' : 'alert-danger'" role="alert">
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
                    <!-- Estado da reserva -->
                    <section class="card bg-white border border-primary rounded-4 p-3 p-md-4 mb-4">
                        <!-- Estado -->
                        <div class="text-center mb-4">
                            <div
                                class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                                :class="{
                                    'bg-warning bg-opacity-10': booking.status === 'pending',
                                    'bg-success bg-opacity-10': booking.status === 'approved',
                                    'bg-danger bg-opacity-10': booking.status === 'rejected',
                                }"
                                style="width: 72px; height: 72px;"
                            >
                                <CheckCircle
                                    v-if="['approved', 'completed'].includes(booking.status)"
                                    :size="48"
                                    class="text-success"
                                />
                                <XCircle
                                    v-else-if="['rejected', 'cancelled'].includes(booking.status)"
                                    :size="48"
                                    class="text-danger"
                                />
                                <CalendarDays
                                    v-else
                                    :size="48"
                                    class="text-warning"
                                />
                            </div>
                            <h2 class="h4 fw-bold mb-1">
                                <span v-if="booking.status === 'pending'">Nova Reserva</span>
                                <span v-else-if="booking.status === 'approved'">Reserva Confirmada</span>
                                <span v-else-if="booking.status === 'rejected'">Reserva Rejeitada</span>
                                <span v-else-if="booking.status === 'cancelled'">Reserva Cancelada</span>
                                <span v-else-if="booking.status === 'completed'">Reserva Concluída</span>
                            </h2>
                            <p class="text-muted mb-0">Reserva #{{ booking.id }}</p>
                        </div>

                        <!-- Info do Dono -->
                        <div class="d-flex align-items-center gap-3 mb-3 p-3 rounded-3 bg-light">
                            <div class="owner-avatar d-flex align-items-center justify-content-center rounded-circle border border-primary flex-shrink-0 overflow-hidden">
                                <img
                                    v-if="owner?.avatar_url"
                                    :src="owner.avatar_url"
                                    alt="Foto de perfil"
                                    class="w-100 h-100 object-fit-cover"
                                />
                                <UserRound v-else :size="32" class="text-primary" />
                            </div>
                            <div>
                                <p class="fw-bold mb-0">{{ owner?.name ?? 'Proprietário' }}</p>
                                <small class="text-muted">Proprietário do animal</small>
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
                            <div class="d-flex flex-column gap-2">
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
                                    <div>
                                        <span class="fw-semibold small">{{ animal.name }}</span>
                                        <small class="text-muted d-block">{{ animal.species }} {{ animal.breed ? '• ' + animal.breed : '' }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Preço -->
                        <div class="border-top pt-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Dias</span>
                                <span class="fw-semibold">{{ numberOfDays }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="fw-bold fs-5">Total</span>
                                <span class="fw-bold fs-5 text-primary">€ {{ booking.total_price.toFixed(2) }}</span>
                            </div>
                        </div>

                        <!-- Ações — apenas se pending -->
                        <div v-if="booking.status === 'pending'" class="d-flex gap-2">
                            <button
                                type="button"
                                class="btn btn-success flex-grow-1 rounded-pill py-2 d-flex align-items-center justify-content-center gap-2"
                                :disabled="loading"
                                @click="updateStatus('approved')"
                            >
                                <CheckCircle :size="18" />
                                {{ loading ? 'A processar...' : 'Confirmar' }}
                            </button>
                            <button
                                type="button"
                                class="btn btn-outline-danger flex-grow-1 rounded-pill py-2 d-flex align-items-center justify-content-center gap-2"
                                :disabled="loading"
                                @click="updateStatus('rejected')"
                            >
                                <XCircle :size="18" />
                                {{ loading ? 'A processar...' : 'Rejeitar' }}
                            </button>
                        </div>

                        <p class="text-muted small text-center mt-3 mb-0">
                            Pedido recebido em {{ new Date(booking.created_at).toLocaleDateString('pt-PT') }}
                        </p>
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
    background-color: #fffaf3;
}

.nav-pills .nav-link.active {
    background-color: var(--patudo-terracota, #c2410c) !important;
    color: #ffffff !important;
}

.nav-pills .nav-link:not(.active):hover {
    color: var(--patudo-terracota, #c2410c) !important;
}

.owner-avatar {
    width: 56px;
    height: 56px;
}

.animal-avatar-sm {
    width: 32px;
    height: 32px;
    background-color: #fffaf3;
}
</style>
