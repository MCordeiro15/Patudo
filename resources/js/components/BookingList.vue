<script setup lang="ts">
// Lista de reservas do utilizador (como dono ou como petsitter), usada nas páginas Pedidos e Reservas.
// pending = true  -> só reservas pendentes (Pedidos)
// pending = false -> todas menos as pendentes (Reservas)
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import type { Animal } from '@/types';
import ModalReviewPetsitter, {
    type ReviewPayload
} from '@/pages/Modals/ModalReviewPetsitter.vue';

const props = defineProps<{
    pending: boolean;
}>();

type Person = { id: number; name: string | null; location: string | null } | null;

type BookingItem = {
    id: number;
    user_id: number;
    petsitter_id: number;
    service: { id: number; name: string; price: number } | null;
    owner: Person;
    petsitter: Person;
    start_date: string;
    end_date: string;
    status: 'pending' | 'approved' | 'rejected' | 'cancelled' | 'completed';
    total_price: number;
    review: {
        id: number;
        star_rating: number;
        comment: string | null;
    } | null;
    animals: Animal[];
};

type NewStatus = 'approved' | 'rejected' | 'cancelled' | 'completed';

const page = usePage<{ auth: { user: { id: number } | null } }>();
const currentUserId = computed(() => page.props.auth.user?.id ?? null);

const bookings = ref<BookingItem[]>([]);
const loading = ref(false);
const loadingMore = ref(false);
const feedback = ref('');
const feedbackType = ref<'success' | 'danger'>('success');
const currentPage = ref(1);
const lastPage = ref(1);
const updatingId = ref<number | null>(null);
const showReviewModal = ref(false);
const selectedBooking = ref<BookingItem | null>(null);
const reviewErrors = ref<{
    star_rating?: string;
    comment?: string;
    general?: string;
}>({});

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

// Tradução dos valores da API para PT
const statusLabels: Record<BookingItem['status'], string> = {
    pending: 'Pendente',
    approved: 'Aceite',
    rejected: 'Recusado',
    cancelled: 'Cancelado',
    completed: 'Concluído',
};
const statusClasses: Record<BookingItem['status'], string> = {
    pending: 'status-pending',
    approved: 'status-approved',
    rejected: 'status-rejected',
    cancelled: 'status-rejected',
    completed: 'status-completed',
};
const serviceLabels: Record<string, string> = {
    'pet-sitting': 'Pet sitting',
    passeio: 'Passeio',
};
const speciesLabels: Record<string, string> = { dog: 'Cão', cat: 'Gato', other: 'Outro' };
const sizeLabels: Record<string, string> = { small: 'Pequeno', medium: 'Médio', large: 'Grande' };

const isPetsitterOf = (b: BookingItem) => b.petsitter_id === currentUserId.value;
const isOwnerOf = (b: BookingItem) => b.user_id === currentUserId.value;

// Mostra sempre a outra pessoa da reserva
const otherPerson = (b: BookingItem) => (isPetsitterOf(b) ? b.owner : b.petsitter);

const avatarUrl = (name: string | null | undefined) =>
    `https://ui-avatars.com/api/?name=${encodeURIComponent(name ?? 'U')}&background=random`;

const numberOfDays = (b: BookingItem) => {
    const start = new Date(b.start_date);
    const end = new Date(b.end_date);
    const diff = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1;
    return diff > 0 ? diff : 0;
};

const animalText = (a: Animal) =>
    [speciesLabels[a.species] ?? a.species, a.breed, sizeLabels[a.size] ?? a.size].filter(Boolean).join(' · ');

const loadBookings = async (pageNumber = 1) => {
    if (pageNumber === 1) loading.value = true;
    else loadingMore.value = true;

    try {
        const res = await apiFetch(`/api/bookings?pending=${props.pending ? 1 : 0}&page=${pageNumber}`);
        if (res.ok) {
            const json = await res.json();
            bookings.value = pageNumber === 1 ? json.data : [...bookings.value, ...json.data];
            currentPage.value = json.meta?.current_page ?? pageNumber;
            lastPage.value = json.meta?.last_page ?? pageNumber;
        } else {
            feedbackType.value = 'danger';
            feedback.value = 'Não foi possível carregar as reservas.';
        }
    } catch {
        feedbackType.value = 'danger';
        feedback.value = 'Erro de conexão. Tente novamente.';
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
};

const successMessages: Record<NewStatus, string> = {
    approved: 'Pedido aceite.',
    rejected: 'Pedido recusado.',
    cancelled: 'Reserva cancelada.',
    completed: 'Reserva concluída.',
};

const updateStatus = async (b: BookingItem, newStatus: NewStatus) => {
    // sem caixa de confirmação do browser: a ação acontece logo ao carregar no botão
    updatingId.value = b.id;
    feedback.value = '';

    try {
        const res = await apiFetch(`/api/bookings/${b.id}/status`, {
            method: 'PATCH',
            body: JSON.stringify({ status: newStatus }),
        });

        if (res.ok) {
            feedbackType.value = 'success';
            feedback.value = successMessages[newStatus];

            if (props.pending) {
                // na página Pedidos, deixa de ser pendente -> sai da lista (passa para Reservas)
                bookings.value = bookings.value.filter((item) => item.id !== b.id);
            } else {
                b.status = newStatus;
            }
        } else {
            const data = await res.json().catch(() => ({}));
            feedbackType.value = 'danger';
            feedback.value = data.message ?? 'Não foi possível atualizar a reserva.';
        }
    } catch {
        feedbackType.value = 'danger';
        feedback.value = 'Erro de conexão. Tente novamente.';
    } finally {
        updatingId.value = null;
    }
};

const openReviewModal = (b: BookingItem) => {
    reviewErrors.value = {};
    selectedBooking.value = b;
    showReviewModal.value = true;
};

const submitBookingReview = async (payload: ReviewPayload) => {
    if (!selectedBooking.value) return;

    try {
        const res = await apiFetch(
            `/api/bookings/${selectedBooking.value.id}/review`,
            {
                method: 'POST',
                body: JSON.stringify({
                    star_rating: payload.rating,
                    comment: payload.comment || null,
                }),
            }
        );

        const data = await res.json();

        if (!res.ok) {
            if (res.status === 422) {
                reviewErrors.value = {
                    star_rating: data.errors?.star_rating?.[0],
                    comment: data.errors?.comment?.[0],
                };
                return;
            }

            // 403 / 409: mostra a mensagem do servidor (ex.: "Esta reserva já foi avaliada.")
            reviewErrors.value = {
                general: data.message ?? 'Não foi possível enviar a avaliação.',
            };

            // se já estava avaliada, atualiza a lista para o botão "Avaliar" desaparecer
            if (res.status === 409) await loadBookings();
            return;
        }

        showReviewModal.value = false;
        selectedBooking.value = null;

        await loadBookings();

    } catch {
        reviewErrors.value = { general: 'Erro de ligação. Tenta novamente.' };
    }
};

onMounted(() => {
    void loadBookings();
});
</script>

<template>
    <div class="booking-list mx-auto">
        <div v-if="feedback" class="alert py-2 small" :class="`alert-${feedbackType}`" role="alert">
            {{ feedback }}
        </div>

        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">A carregar...</span>
            </div>
        </div>

        <p v-else-if="bookings.length === 0" class="text-center text-muted py-5">
            {{ pending ? 'Não tens pedidos de reserva pendentes.' : 'Ainda não tens reservas.' }}
        </p>

        <template v-else>
            <article
                v-for="b in bookings"
                :key="b.id"
                class="card bg-white border border-primary rounded-4 p-3 p-md-4 mb-3"
            >
                <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center">
                    <!-- Foto da outra pessoa -->
                    <img
                        :src="avatarUrl(otherPerson(b)?.name)"
                        :alt="otherPerson(b)?.name ?? 'Utilizador'"
                        class="booking-avatar rounded-4 border flex-shrink-0"
                    />

                    <!-- Pessoa e animais -->
                    <div class="flex-grow-1">
                        <span class="role-tag small text-muted">
                            {{ isPetsitterOf(b) ? 'Pedido recebido' : 'Pedido enviado' }}
                        </span>
                        <h2 class="h5 fw-bold mb-0">{{ otherPerson(b)?.name ?? 'Utilizador' }}</h2>
                        <small class="text-muted d-block mb-2">{{ otherPerson(b)?.location ?? '--' }}</small>

                        <p v-for="animal in b.animals" :key="animal.id" class="mb-0 small">
                            <span class="fw-semibold">Animal:</span> {{ animal.name }}
                            <span v-if="animalText(animal)" class="text-muted">· {{ animalText(animal) }}</span>
                        </p>
                    </div>

                    <!-- Serviço, período, preço e ações -->
                    <div class="text-md-end d-flex flex-column align-items-md-end gap-1">
                        <span v-if="!pending" class="status-badge rounded-pill px-3 py-1 small" :class="statusClasses[b.status]">
                            {{ statusLabels[b.status] }}
                        </span>
                        <span class="fw-bold">{{ b.service ? (serviceLabels[b.service.name] ?? b.service.name) : 'Reserva' }}</span>
                        <small class="text-muted">
                            Período: {{ numberOfDays(b) }} {{ numberOfDays(b) === 1 ? 'dia' : 'dias' }}
                            ({{ new Date(b.start_date).toLocaleDateString('pt-PT') }} – {{ new Date(b.end_date).toLocaleDateString('pt-PT') }})
                        </small>
                        <span class="fw-bold">Preço: {{ Number(b.total_price).toFixed(2) }} €</span>

                        <div class="d-flex gap-2 mt-1">
                            <!-- Petsitter: aceitar / recusar pedidos pendentes -->
                            <template v-if="b.status === 'pending' && isPetsitterOf(b)">
                                <button
                                    type="button"
                                    class="btn btn-outline-danger btn-sm rounded-pill px-3"
                                    :disabled="updatingId === b.id"
                                    @click="updateStatus(b, 'rejected')"
                                >
                                    Recusar
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-success btn-sm rounded-pill px-3"
                                    :disabled="updatingId === b.id"
                                    @click="updateStatus(b, 'approved')"
                                >
                                    Aceitar
                                </button>
                            </template>

                            <!-- Dono: cancelar pedido pendente -->
                            <button
                                v-else-if="b.status === 'pending'"
                                type="button"
                                class="btn btn-danger btn-sm rounded-pill px-3"
                                :disabled="updatingId === b.id"
                                @click="updateStatus(b, 'cancelled')"
                            >
                                Cancelar pedido
                            </button>

                            <!-- Reserva aceite -->
                            <template v-else-if="b.status === 'approved'">
                                <a href="/messages" class="btn btn-success btn-sm rounded-pill px-3">Conversar</a>
                                <button
                                    v-if="isPetsitterOf(b)"
                                    type="button"
                                    class="btn btn-outline-primary btn-sm rounded-pill px-3"
                                    :disabled="updatingId === b.id"
                                    @click="updateStatus(b, 'completed')"
                                >
                                    Concluir
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    class="btn btn-outline-danger btn-sm rounded-pill px-3"
                                    :disabled="updatingId === b.id"
                                    @click="updateStatus(b, 'cancelled')"
                                >
                                    Cancelar
                                </button>
                            </template>
                            <!-- Dono: avaliar uma reserva concluída sem review -->
                            <button
                                v-if="isOwnerOf(b) && b.status === 'completed' && !b.review"
                                type="button"
                                class="btn btn-outline-primary btn-sm rounded-pill px-3"
                                @click="openReviewModal(b)"
                            >
                                Avaliar
                            </button>
                        </div>
                    </div>
                </div>
            </article>

            <div v-if="currentPage < lastPage" class="text-center mt-3">
                <button
                    type="button"
                    class="btn btn-outline-secondary rounded-pill px-4"
                    :disabled="loadingMore"
                    @click="loadBookings(currentPage + 1)"
                >
                    {{ loadingMore ? 'A carregar...' : 'Ver mais' }}
                </button>
            </div>
        </template>

        <ModalReviewPetsitter
            :show="showReviewModal"
            :close-on-submit="false"
            :petsitter="{
                id: selectedBooking?.petsitter_id,
                name: selectedBooking?.petsitter?.name ?? 'Petsitter',
                avatar_url: null,
                service_summary: selectedBooking?.service?.name ?? 'Reserva'
            }"
            @submit="submitBookingReview"
            @close="showReviewModal = false"
            :server-errors="reviewErrors"
        />
    </div>
</template>

<style scoped>
.booking-list {
    max-width: 900px;
}

.booking-avatar {
    width: 96px;
    height: 96px;
    object-fit: cover;
}

.status-badge {
    font-weight: 600;
}

.status-pending {
    background-color: #fed7aa;
    color: #9a3412;
}

.status-approved {
    background-color: #bbf7d0;
    color: #166534;
}

.status-rejected {
    background-color: #fecaca;
    color: #991b1b;
}

.status-completed {
    background-color: #e5e7eb;
    color: #374151;
}
</style>
