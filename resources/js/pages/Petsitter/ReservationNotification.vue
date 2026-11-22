<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Bell, LogOut, MessageCircle, UserRound, ChevronRight } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import type { Animal } from '@/types';
import { useNotifications } from '@/composables/useNotifications';
import ModalNotifications from '@/pages/Modals/ModalNotifications.vue';

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

// Campos extra que o cartão mostra (idade e porte). Ajusta aos nomes reais da tua API.
type AnimalCard = Animal & {
    age?: number | string | null;
    size?: string | null;
};

type BookingData = {
    id: number;
    user_id: number;
    petsitter_id: number;
    start_date: string;
    end_date: string;
    status: string;
    total_price: number;
    animals: AnimalCard[];
    created_at: string;
};

type OwnerInfo = {
    name: string;
    location: string;
    avatar_url: string | null;
};

const bookings = ref<BookingData[]>([]);
const owners = ref<Record<number, OwnerInfo>>({});
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

function numberOfDays(b: BookingData): number {
    const start = new Date(b.start_date);
    const end = new Date(b.end_date);
    const diff = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1;
    return diff > 0 ? diff : 0;
}

function periodLabel(b: BookingData): string {
    const days = numberOfDays(b);
    return `${days} ${days === 1 ? 'dia' : 'dias'}`;
}

function formatPrice(value: number): string {
    const formatted = Number.isInteger(value)
        ? String(value)
        : value.toFixed(2).replace('.', ',');
    return `${formatted} €`;
}

function serviceLabel(b: BookingData): string {
    const species = (b.animals?.[0]?.species ?? '').toLowerCase();
    if (species.includes('gat') || species.includes('cat')) return 'Cat Sitting';
    if (species.includes('c') && (species.includes('ão') || species.includes('ao') || species.includes('dog'))) return 'Dog Sitting';
    return 'Pet Sitting';
}

function animalLine(animal: AnimalCard): string {
    const age = animal.age !== null && animal.age !== undefined && animal.age !== ''
        ? `${animal.age} ${Number(animal.age) === 1 ? 'ano' : 'anos'}`
        : '';
    return [animal.species, animal.breed, age, animal.size].filter(Boolean).join(' · ');
}

const loadBookings = async () => {
    loading.value = true;
    try {
        // Pedidos pendentes do petsitter autenticado
        const res = await apiFetch('/api/bookings?status=pending');
        if (!res.ok) {
            setFeedback('Não foi possível carregar os pedidos de reserva.', 'error');
            return;
        }
        const data: BookingData[] = (await res.json()).data ?? [];
        bookings.value = data.filter((b) => b.status === 'pending');

        // Carregar info dos donos (um pedido por user_id distinto)
        const ids = [...new Set(bookings.value.map((b) => b.user_id))];
        await Promise.all(
            ids.map(async (id) => {
                try {
                    const ownerRes = await apiFetch(`/api/users/${id}`);
                    if (!ownerRes.ok) return;
                    const user = (await ownerRes.json()).data;
                    owners.value[id] = {
                        name: user.name ?? 'Proprietário',
                        location: user.location ?? user.city ?? '',
                        avatar_url: user.avatar_url ?? null,
                    };
                } catch {
                    /* o cartão usa os valores por defeito */
                }
            }),
        );
    } catch {
        setFeedback('Erro de conexão. Tente novamente.', 'error');
    } finally {
        loading.value = false;
    }
};

const updateStatus = async (booking: BookingData, newStatus: 'approved' | 'rejected') => {
    const confirmMsg = newStatus === 'approved'
        ? 'Deseja aceitar este pedido de reserva?'
        : 'Deseja recusar este pedido de reserva?';
    if (!confirm(confirmMsg)) return;

    processingId.value = booking.id;
    feedback.value = '';
    try {
        const res = await apiFetch(`/api/bookings/${booking.id}/status`, {
            method: 'PATCH',
            body: JSON.stringify({ status: newStatus }),
        });
        if (res.ok) {
            bookings.value = bookings.value.filter((b) => b.id !== booking.id);
            setFeedback(
                newStatus === 'approved' ? 'Reserva aceite com sucesso!' : 'Reserva recusada.',
                'success',
            );
        } else {
            setFeedback('Não foi possível atualizar o estado da reserva.', 'error');
        }
    } catch {
        setFeedback('Erro de conexão. Tente novamente.', 'error');
    } finally {
        processingId.value = null;
    }
};

onMounted(() => {
    void loadBookings();
});
</script>

<template>
    <Head title="Pedidos de reserva" />

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
                    <a class="nav-link active rounded-pill px-4" href="/reservation-notification" aria-current="page">Pedidos</a>
                    <a class="nav-link text-dark rounded-pill px-4" href="/favorites">Favoritos</a>
                    <a class="nav-link text-dark rounded-pill px-4" href="/reservations">Reservas</a>
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

            <h1 class="text-center fw-bold display-5 mt-5 mb-5">Pedidos de reserva</h1>

            <!-- Feedback -->
            <div
                v-if="feedback"
                class="alert py-2 small"
                :class="feedbackType === 'success' ? 'alert-success' : 'alert-danger'"
                role="alert"
            >
                {{ feedback }}
            </div>

            <!-- Lista de pedidos -->
            <div class="d-flex flex-column gap-4">
                <article
                    v-for="booking in bookings"
                    :key="booking.id"
                    class="card request-card rounded-4 p-4 px-md-5"
                >
                    <!--
                        Grelha com 3 colunas (avatar | dono e animal | serviço e preço) e 3 linhas
                        partilhadas, para que cada linha fique à mesma altura nas duas colunas:
                        linha 1: nome / serviço e período
                        linha 2: Animal / Preço
                        linha 3: Ver ficha do animal / Recusar e Aceitar
                        (no telemóvel passa tudo para uma só coluna, pela ordem em que está escrito)
                    -->
                    <div class="request-grid">
                        <!-- Avatar do dono -->
                        <div class="owner-avatar cell-avatar rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 overflow-hidden">
                            <img
                                v-if="owners[booking.user_id]?.avatar_url"
                                :src="owners[booking.user_id]!.avatar_url!"
                                alt="Foto de perfil"
                                class="w-100 h-100 object-fit-cover"
                            />
                            <UserRound v-else :size="56" class="text-secondary" />
                        </div>

                        <!-- Dono -->
                        <div class="cell-owner">
                            <h2 class="fs-3 fw-bold mb-0">{{ owners[booking.user_id]?.name ?? 'Proprietário' }}</h2>
                            <small v-if="owners[booking.user_id]?.location" class="text-secondary d-block">
                                {{ owners[booking.user_id]?.location }}
                            </small>
                        </div>

                        <!-- Animal -->
                        <div class="cell-animal">
                            <p
                                v-for="animal in booking.animals"
                                :key="animal.id"
                                class="mb-0 fs-5"
                            >
                                <strong>Animal:</strong> {{ animalLine(animal) }}
                            </p>
                        </div>

                        <!-- Ver ficha do animal -->
                        <div class="cell-ficha d-flex flex-column gap-2">
                            <a
                                v-for="animal in booking.animals"
                                :key="animal.id"
                                :href="`/animals/${animal.id}`"
                                class="btn btn-primary d-inline-flex align-items-center justify-content-between"
                            >
                                Ver ficha do animal
                                <ChevronRight :size="14" />
                            </a>
                        </div>

                        <!-- Serviço e período -->
                        <div class="cell-service text-center">
                            <p class="fs-3 fw-medium mb-1">{{ serviceLabel(booking) }}</p>
                            <small><strong>Período:</strong> {{ periodLabel(booking) }}</small>
                        </div>

                        <!-- Preço -->
                        <div class="cell-price text-center">
                            <p class="fw-bold fs-4 mb-0">Preço: {{ formatPrice(booking.total_price) }}</p>
                        </div>

                        <!-- Ações -->
                        <div class="cell-actions d-flex gap-2">
                            <button
                                type="button"
                                class="btn btn-outline-danger rounded-pill flex-fill py-2"
                                :disabled="processingId === booking.id"
                                @click="updateStatus(booking, 'rejected')"
                            >
                                Recusar
                            </button>
                            <button
                                type="button"
                                class="btn btn-aceitar rounded-pill flex-fill py-2"
                                :disabled="processingId === booking.id"
                                @click="updateStatus(booking, 'approved')"
                            >
                                Aceitar
                            </button>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Estado vazio -->
            <p v-if="!loading && bookings.length === 0 && !feedback" class="text-center text-secondary mt-4">
                Não tens pedidos de reserva pendentes.
            </p>

            <!-- Spinner -->
            <div v-if="loading" class="text-center py-4">
                <div class="spinner-border text-secondary" role="status">
                    <span class="visually-hidden">A carregar...</span>
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

/* Cartão de pedido */
.request-card {
    border: 1px solid var(--patudo-terracota, #c2410c);
}

/* Grelha do cartão: avatar | dono e animal | serviço e preço */
.request-grid {
    display: grid;
    grid-template-columns: 150px 1fr 20rem;
    grid-template-rows: auto auto auto;
    column-gap: 3rem;
    row-gap: 1rem;
}

.cell-avatar {
    grid-column: 1;
    grid-row: 1 / span 3;
    align-self: center;
}

.cell-owner {
    grid-column: 2;
    grid-row: 1;
}

.cell-animal {
    grid-column: 2;
    grid-row: 2;
    align-self: center;
}

.cell-ficha {
    grid-column: 2;
    grid-row: 3;
    align-self: center;
}

.cell-service {
    grid-column: 3;
    grid-row: 1;
}

.cell-price {
    grid-column: 3;
    grid-row: 2;
    align-self: center;
}

.cell-actions {
    grid-column: 3;
    grid-row: 3;
    align-self: center;
}

.owner-avatar {
    width: 150px;
    height: 150px;
    border: 1px solid var(--patudo-castanhoDark, #2e1d14);
    border-radius: 2.5rem;
    background-color: #ffffff;
}

/* Ver ficha do animal */
.btn-primary {
    width: 240px;
    height: 36px;
    min-height: 0;
    padding: 0 14px;
    font-size: 14px;
    line-height: 1;
}

/* Aceitar: o .btn-success do Bootstrap ignora --bs-success, por isso usa-se a cor do tema */
.btn-aceitar {
    color: #ffffff;
    background-color: var(--patudo-verde, #4f6f52);
    border: 1px solid var(--patudo-verde, #4f6f52);
    --bs-btn-padding-y: 0.45rem;
    --bs-btn-padding-x: 1.6rem;
    --bs-btn-font-size: 0.8rem;
    --bs-btn-font-weight: 600;
    border-radius: 25px;
    line-height: normal;
}

.btn-aceitar:hover,
.btn-aceitar:focus {
    color: #ffffff;
    background-color: #3f5a42;
    border-color: #3f5a42;
}

.btn-aceitar:disabled {
    color: #ffffff;
    background-color: var(--patudo-verde, #4f6f52);
    border-color: var(--patudo-verde, #4f6f52);
    opacity: 0.65;
}

/* Telemóvel: uma só coluna, pela ordem do HTML */
@media (max-width: 767.98px) {
    .request-grid {
        grid-template-columns: 1fr;
        grid-template-rows: none;
        row-gap: 1.25rem;
    }

    .cell-avatar,
    .cell-owner,
    .cell-animal,
    .cell-ficha,
    .cell-service,
    .cell-price,
    .cell-actions {
        grid-column: 1;
        grid-row: auto;
    }

    .cell-avatar {
        justify-self: center;
    }

    .btn-ficha {
        min-width: 0;
        width: 100%;
    }
}
</style>
