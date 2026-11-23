<script setup lang="ts">
// Lista de reservas usada nas páginas Pedidos (petsitter) e Reservas (dono).
// role="petsitter" -> pedidos pendentes recebidos (Ver ficha do animal, Recusar, Aceitar)
// role="owner"     -> página Reservas: todas as reservas onde o user está, como dono OU como petsitter,
//                     em todos os estados. Cada cartão mostra a outra pessoa e os botões do seu lado:
//                     dono      -> Cancelar, Conversar, Avaliar, Ver detalhe
//                     petsitter -> Recusar/Aceitar, Conversar, Concluir, Ver detalhe
// Os cartões são os da Constança: o HTML e o CSS de cada role vieram das respetivas páginas.
import { usePage } from '@inertiajs/vue3';
import { ChevronRight, MapPin, UserRound } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import type { Animal } from '@/types';
import ModalPetDetails, { type PetDetails } from '@/pages/Modals/ModalPetDetails.vue';
import ModalReviewPetsitter, { type ReviewPayload } from '@/pages/Modals/ModalReviewPetsitter.vue';

const props = defineProps<{
    role: 'owner' | 'petsitter';
}>();

// Pessoa da reserva (BookingResource): nome, localidade e foto
type Person = { id: number; name: string | null; location: string | null; avatar_url: string | null } | null;

// Campos extra que o cartão do petsitter mostra (idade e porte)
type AnimalCard = Animal & {
    age?: number | string | null;
    size?: string | null;
};

type BookingItem = {
    id: number;
    user_id: number;
    petsitter_id: number;
    start_date: string;
    end_date: string;
    status: string;
    total_price: number;
    animals: AnimalCard[];
    created_at: string;
    owner?: Person;
    petsitter?: Person;
    service?: { id: number; name: string; price: number } | null;
    review?: { id: number } | null;
    zone?: string | null;
};

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

// bookings.user_id e bookings.petsitter_id guardam ids de users
const page = usePage<{ auth: { user: { id: number } | null } }>();
const currentUserId = computed(() => page.props.auth.user?.id ?? null);

// ---------- Estado ----------
const bookings = ref<BookingItem[]>([]);
const loading = ref(false);
const processingId = ref<number | null>(null);
const feedback = ref('');
const feedbackType = ref<'success' | 'error'>('success');

// Ficha do animal (petsitter)
const showPetDetails = ref(false);
const selectedPet = ref<PetDetails | undefined>(undefined);
const loadingAnimalId = ref<number | null>(null);

// Avaliar (dono)
const showReviewModal = ref(false);
const selectedBooking = ref<BookingItem | null>(null);
const reviewErrors = ref<{ star_rating?: string; comment?: string; general?: string }>({});

function setFeedback(message: string, type: 'success' | 'error') {
    feedback.value = message;
    feedbackType.value = type;
}

// ---------- Helpers ----------
function numberOfDays(b: BookingItem): number {
    const start = new Date(b.start_date);
    const end = new Date(b.end_date);
    const diff = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1;
    return diff > 0 ? diff : 0;
}

function periodLabel(b: BookingItem): string {
    const days = numberOfDays(b);
    return `${days} ${days === 1 ? 'dia' : 'dias'}`;
}

// Cada página formatava o preço à sua maneira; mantém-se o formato de cada uma
function formatPrice(value: number | string): string {
    if (props.role === 'owner') {
        return `${Number(value).toLocaleString('pt-PT', { maximumFractionDigits: 2 })} €`;
    }
    const n = Number(value);
    const formatted = Number.isInteger(n) ? String(n) : n.toFixed(2).replace('.', ',');
    return `${formatted} €`;
}

function serviceLabel(b: BookingItem): string {
    const species = (b.animals?.[0]?.species ?? '').toString().toLowerCase();
    if (species.includes('gat') || species.includes('cat')) return 'Cat Sitting';
    if (species.includes('cão') || species.includes('cao') || species.includes('dog')) return 'Dog Sitting';
    return 'Pet Sitting';
}

// Cartão do petsitter: "Cão · Labrador · 3 anos · Grande"
function animalLine(animal: AnimalCard): string {
    const age = animal.age !== null && animal.age !== undefined && animal.age !== ''
        ? `${animal.age} ${Number(animal.age) === 1 ? 'ano' : 'anos'}`
        : '';
    return [animal.species, animal.breed, age, animal.size].filter(Boolean).join(' · ');
}

// Cartão do dono: "Rex, Mia"
function animalNames(b: BookingItem): string {
    return (b.animals ?? []).map((a) => a.name).join(', ') || '--';
}

// Página Reservas: em cada reserva, o user é o petsitter ou o dono
const isPetsitterOf = (b: BookingItem) => b.petsitter_id === currentUserId.value;

// Mostra sempre a outra pessoa da reserva
const otherPerson = (b: BookingItem): Person | undefined => (isPetsitterOf(b) ? b.owner : b.petsitter);

// Conversar abre o chat com a outra pessoa (ids de users)
const chatWith = (b: BookingItem) => (isPetsitterOf(b) ? b.user_id : b.petsitter_id);

const statusShort: Record<string, string> = {
    pending: 'Pendente',
    approved: 'Aceite',
    rejected: 'Rejeitada',
    cancelled: 'Cancelada',
    completed: 'Concluída',
};

// ---------- Lista ----------
const loadBookings = async () => {
    loading.value = true;
    try {
        // Petsitter: só pendentes (?pending=1). Dono: todos os estados.
        // A API pagina de 15 em 15, por isso lê todas as páginas.
        const filter = props.role === 'petsitter' ? 'pending=1&' : '';
        const all: BookingItem[] = [];
        let pageNumber = 1;
        let lastPage = 1;
        do {
            const res = await apiFetch(`/api/bookings?${filter}page=${pageNumber}`);
            if (!res.ok) {
                setFeedback(
                    props.role === 'petsitter'
                        ? 'Não foi possível carregar os pedidos de reserva.'
                        : 'Não foi possível carregar as reservas.',
                    'error',
                );
                return;
            }
            const json = await res.json();
            all.push(...(json.data ?? []));
            lastPage = json.meta?.last_page ?? pageNumber;
            pageNumber++;
        } while (pageNumber <= lastPage);

        // A API devolve as reservas onde és dono OU petsitter.
        // Pedidos: só os pendentes recebidos. Reservas: todas.
        bookings.value = props.role === 'petsitter'
            ? all.filter((b) => b.status === 'pending' && b.petsitter_id === currentUserId.value)
            : all;
    } catch {
        setFeedback('Erro de conexão. Tente novamente.', 'error');
    } finally {
        loading.value = false;
    }
};

const successMessages: Record<string, string> = {
    approved: 'Reserva aceite com sucesso!',
    rejected: 'Reserva recusada.',
    cancelled: 'Reserva cancelada com sucesso.',
    completed: 'Reserva concluída.',
};

// Pedidos: aceitar / recusar (o pedido sai da lista). Reservas: o cartão muda de estado.
const updateStatus = async (b: BookingItem, newStatus: 'approved' | 'rejected' | 'cancelled' | 'completed') => {
    processingId.value = b.id;
    feedback.value = '';
    try {
        const res = await apiFetch(`/api/bookings/${b.id}/status`, {
            method: 'PATCH',
            body: JSON.stringify({ status: newStatus }),
        });
        if (res.ok) {
            if (props.role === 'petsitter') {
                bookings.value = bookings.value.filter((item) => item.id !== b.id);
            } else {
                b.status = newStatus;
            }
            setFeedback(successMessages[newStatus], 'success');
        } else {
            setFeedback(
                newStatus === 'cancelled'
                    ? 'Não foi possível cancelar a reserva.'
                    : 'Não foi possível atualizar o estado da reserva.',
                'error',
            );
        }
    } catch {
        setFeedback('Erro de conexão. Tente novamente.', 'error');
    } finally {
        processingId.value = null;
    }
};

// ---------- Ficha do animal ----------
// GET /api/animals/{id} (a AnimalPolicy deixa o petsitter da reserva ver o animal)
const openAnimal = async (animal: AnimalCard) => {
    loadingAnimalId.value = animal.id;
    feedback.value = '';
    try {
        const res = await apiFetch(`/api/animals/${animal.id}`);
        if (!res.ok) {
            setFeedback('Não foi possível carregar a ficha do animal.', 'error');
            return;
        }
        const a = (await res.json()).data;
        selectedPet.value = {
            id: a.id,
            name: a.name,
            species: a.species,
            breed: a.breed,
            size: a.size,
            sex: a.sex ?? undefined,
            weight: a.weight ? `${Number(a.weight)} kg` : undefined,
            care: a.special_needs ?? undefined,
            medication: a.medication ?? undefined,
            // não há colunas de passeio nem (às vezes) de agressividade; '—' evita o texto por defeito do modal
            aggressiveness: a.aggression || '—',
            walks: '—',
            photos: a.photo ? [a.photo] : [],
        };
        showPetDetails.value = true;
    } catch {
        setFeedback('Erro de conexão. Tente novamente.', 'error');
    } finally {
        loadingAnimalId.value = null;
    }
};

// ---------- Avaliar ----------
const openReviewModal = (b: BookingItem) => {
    reviewErrors.value = {};
    selectedBooking.value = b;
    showReviewModal.value = true;
};

const submitBookingReview = async (payload: ReviewPayload) => {
    if (!selectedBooking.value) return;

    try {
        const res = await apiFetch(`/api/bookings/${selectedBooking.value.id}/review`, {
            method: 'POST',
            body: JSON.stringify({
                star_rating: payload.rating,
                comment: payload.comment || null,
            }),
        });

        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            if (res.status === 422) {
                reviewErrors.value = {
                    star_rating: data.errors?.star_rating?.[0],
                    comment: data.errors?.comment?.[0],
                };
                return;
            }

            // 403 / 409: mostra a mensagem do servidor (ex.: "Esta reserva já foi avaliada.")
            reviewErrors.value = { general: data.message ?? 'Não foi possível enviar a avaliação.' };

            // se já estava avaliada, atualiza a lista para o botão "Avaliar" desaparecer
            if (res.status === 409) await loadBookings();
            return;
        }

        showReviewModal.value = false;
        selectedBooking.value = null;
        setFeedback('Avaliação enviada. Obrigado!', 'success');
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
    <!-- Feedback -->
    <div
        v-if="feedback"
        class="alert py-2 small"
        :class="feedbackType === 'success' ? 'alert-success' : 'alert-danger'"
        role="alert"
    >
        {{ feedback }}
    </div>

    <!-- ===================== Petsitter: pedidos de reserva ===================== -->
    <template v-if="role === 'petsitter'">
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
                            v-if="booking.owner?.avatar_url"
                            :src="booking.owner.avatar_url"
                            alt="Foto de perfil"
                            class="w-100 h-100 object-fit-cover"
                        />
                        <UserRound v-else :size="56" class="text-secondary" />
                    </div>

                    <!-- Dono -->
                    <div class="cell-owner">
                        <h2 class="fs-3 fw-bold mb-0">{{ booking.owner?.name ?? 'Proprietário' }}</h2>
                        <small v-if="booking.owner?.location" class="text-secondary d-block">
                            {{ booking.owner.location }}
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
                            href="#"
                            class="btn btn-primary d-inline-flex align-items-center justify-content-between"
                            :class="{ disabled: loadingAnimalId === animal.id }"
                            @click.prevent="openAnimal(animal)"
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
    </template>

    <!-- ===================== Dono: as minhas reservas ===================== -->
    <template v-else>
        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-secondary" role="status">
                <span class="visually-hidden">A carregar...</span>
            </div>
        </div>

        <template v-else>
            <div v-if="bookings.length === 0 && !feedback" class="text-center text-secondary py-5">
                Ainda não tens reservas.
            </div>

            <article
                v-for="b in bookings"
                :key="b.id"
                class="booking-card bg-white mb-3"
            >
                <!-- Avatar da outra pessoa (o petsitter, ou o dono se fores tu o petsitter) -->
                <div class="booking-avatar rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 overflow-hidden">
                    <img
                        v-if="otherPerson(b)?.avatar_url"
                        :src="otherPerson(b)!.avatar_url!"
                        alt="Foto de perfil"
                        class="w-100 h-100 object-fit-cover"
                    />
                    <UserRound v-else :size="56" class="text-secondary" />
                </div>

                <!-- Nome / local / animal -->
                <div class="booking-info">
                    <div>
                        <h2 class="booking-name mb-0">
                            {{ otherPerson(b)?.name ?? (isPetsitterOf(b) ? 'Proprietário' : 'Petsitter') }}
                        </h2>
                        <small class="booking-location d-flex align-items-center gap-1">
                            <MapPin :size="17" />
                            <!-- zona da reserva (onde é o serviço); reservas antigas sem zona: localidade da pessoa -->
                            {{ b.zone || otherPerson(b)?.location || '--' }}
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

                    <!-- Lado do petsitter: reservas que te fizeram -->
                    <div v-if="isPetsitterOf(b)" class="booking-actions d-flex gap-2 mt-2 w-100">
                        <!-- Pedido pendente: Recusar + Aceitar -->
                        <template v-if="b.status === 'pending'">
                            <button
                                type="button"
                                class="btn btn-outline-danger btn-sm rounded-pill flex-fill"
                                :disabled="processingId === b.id"
                                @click="updateStatus(b, 'rejected')"
                            >
                                Recusar
                            </button>

                            <button
                                type="button"
                                class="btn btn-conversar btn-sm rounded-pill flex-fill"
                                :disabled="processingId === b.id"
                                @click="updateStatus(b, 'approved')"
                            >
                                Aceitar
                            </button>
                        </template>

                        <!-- Reserva aceite: Conversar + Concluir -->
                        <template v-else-if="b.status === 'approved'">
                            <a :href="`/messages?with=${chatWith(b)}`" class="btn btn-conversar btn-sm rounded-pill flex-fill">
                                Conversar
                            </a>

                            <button
                                type="button"
                                class="btn btn-concluir btn-sm rounded-pill flex-fill"
                                :disabled="processingId === b.id"
                                @click="updateStatus(b, 'completed')"
                            >
                                Concluir
                            </button>
                        </template>

                        <!-- Outros estados: ver detalhe -->
                        <a
                            v-else
                            :href="`/reservations?booking=${b.id}`"
                            class="btn btn-outline-secondary btn-sm rounded-pill flex-fill"
                        >
                            Ver detalhe
                        </a>
                    </div>

                    <!-- Lado do dono: reservas que fizeste -->
                    <div v-else class="booking-actions d-flex gap-2 mt-2 w-100">
                        <!-- Reserva aceite: Conversar + Cancelar -->
                        <template v-if="b.status === 'approved'">
                            <a :href="`/messages?with=${chatWith(b)}`" class="btn btn-conversar btn-sm rounded-pill flex-fill">
                                Conversar
                            </a>

                            <button
                                type="button"
                                class="btn btn-outline-danger btn-sm rounded-pill flex-fill"
                                :disabled="processingId === b.id"
                                @click="updateStatus(b, 'cancelled')"
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
                            @click="updateStatus(b, 'cancelled')"
                        >
                            Cancelar pedido
                        </button>

                        <!-- Concluída sem avaliação: Avaliar + Ver detalhe -->
                        <template v-else-if="b.status === 'completed' && !b.review">
                            <button
                                type="button"
                                class="btn btn-conversar btn-sm rounded-pill flex-fill"
                                @click="openReviewModal(b)"
                            >
                                Avaliar
                            </button>

                            <a
                                :href="`/reservations?booking=${b.id}`"
                                class="btn btn-outline-secondary btn-sm rounded-pill flex-fill"
                            >
                                Ver detalhe
                            </a>
                        </template>

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
    </template>

    <ModalPetDetails :show="showPetDetails" :pet="selectedPet" @close="showPetDetails = false" />

    <ModalReviewPetsitter
        :show="showReviewModal"
        :close-on-submit="false"
        :petsitter="{
            id: selectedBooking?.petsitter_id,
            name: selectedBooking?.petsitter?.name ?? 'Petsitter',
            avatar_url: selectedBooking?.petsitter?.avatar_url ?? null,
            service_summary: selectedBooking?.service?.name ?? 'Reserva',
        }"
        :server-errors="reviewErrors"
        @submit="submitBookingReview"
        @close="showReviewModal = false"
    />
</template>

<style scoped>
/* ================= Petsitter: cartão de pedido (vindo de Petsitter/ReservationNotification.vue) ================= */
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
    /* ao clicar e enquanto a ficha carrega (disabled) o Bootstrap usava o azul por defeito */
    --bs-btn-active-bg: var(--patudo-laranjaVivo);
    --bs-btn-active-border-color: var(--patudo-laranjaVivo);
    --bs-btn-active-color: #ffffff;
    --bs-btn-disabled-bg: var(--patudo-terracota);
    --bs-btn-disabled-border-color: var(--patudo-terracota);
    --bs-btn-disabled-color: #ffffff;
    --bs-btn-focus-shadow-rgb: 194, 65, 12;
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

/* ================= Dono: cartão de reserva (vindo de Owner/ReservationNotificationToOwner.vue) ================= */
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

/* Os botões de cada par (ex.: Conversar + Concluir) ficam com a mesma largura,
   em vez de a largura depender do tamanho do texto (flex-fill) */
.booking-actions .btn {
    /* !important porque o .flex-fill do Bootstrap também usa !important */
    flex: 1 1 0 !important;
}

/* Conversar / Avaliar: o .btn-success do Bootstrap ignora --bs-success, por isso usa-se a cor do tema */
.btn-conversar {
    color: #ffffff;
    background-color: var(--patudo-verde, #4f6f52);
    border: 1px solid var(--patudo-verde, #4f6f52);
    /* mesmo tamanho de letra dos outros botões do cartão (Cancelar, Ver detalhe) */
    font-size: 0.8rem;
    font-weight: 600;
}

.btn-conversar:hover,
.btn-conversar:focus {
    color: #ffffff;
    background-color: #3f5a42;
    border-color: #3f5a42;
}

/* Concluir: preenchido com a cor do tema, como os outros botões de ação */
.btn-concluir {
    color: #ffffff;
    background-color: var(--patudo-terracota, #c2410c);
    border: 1px solid var(--patudo-terracota, #c2410c);
    font-size: 0.8rem;
    font-weight: 600;
}

.btn-concluir:hover,
.btn-concluir:focus,
.btn-concluir:active {
    color: #ffffff;
    background-color: var(--patudo-laranjaVivo, #f97316);
    border-color: var(--patudo-laranjaVivo, #f97316);
}

.btn-concluir:disabled {
    color: #ffffff;
    background-color: var(--patudo-terracota, #c2410c);
    border-color: var(--patudo-terracota, #c2410c);
    opacity: 0.65;
}

/* ================= Telemóvel ================= */
@media (max-width: 767.98px) {
    /* Petsitter: uma só coluna, pela ordem do HTML */
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

    /* Dono */
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
