<script setup lang="ts">
// Lista de reservas usada nas páginas Pedidos (petsitter) e Reservas.
// Nas duas: da mais recente para a mais antiga, com paginação.
// Estados da lista: carregamento, erro (com "Tentar novamente") e vazio.
// role="petsitter" -> página Pedidos: os pedidos PENDENTES em que o user é o petsitter (Recusar / Aceitar).
//                     Depois de respondido, o pedido sai daqui e passa para as Reservas.
// role="owner"     -> pedidos pendentes do dono (pendingOnly) ou Reservas com filtro por estado:
//                     - as reservas em que o user é o dono, já respondidas;
//                     - as reservas em que o user é o petsitter e que já não estão pendentes.
//                     Cada cartão mostra a outra pessoa, os animais, as datas, o preço total e o estado,
//                     e os botões do lado do user (dono: Cancelar/Conversar/Avaliar; petsitter: Conversar/Concluir).
import { usePage } from '@inertiajs/vue3';
import { ChevronDown, ChevronRight, MapPin, Menu, UserRound } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { Animal } from '@/types';
import ModalPetDetails, { type PetDetails } from '@/pages/Modals/ModalPetDetails.vue';
import ModalReviewPetsitter, { type ReviewPayload } from '@/pages/Modals/ModalReviewPetsitter.vue';

const props = withDefaults(defineProps<{
    role: 'owner' | 'petsitter';
    pendingOnly?: boolean;
}>(), {
    pendingOnly: false,
});

// Pessoa da reserva (BookingResource): nome, localidade e foto
type Person = { id: number; name: string | null; location: string | null; avatar_url: string | null } | null;

// Campos extra que o cartão do petsitter mostra (idade e porte)
type AnimalCard = Animal & {
    age?: number | string | null;
    size?: string | null;
};

type BookingStatus = 'pending' | 'approved' | 'rejected' | 'cancelled' | 'completed';

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
const loadError = ref(''); // erro ao carregar a lista (mostra "Tentar novamente")
const processingId = ref<number | null>(null);
const feedback = ref('');
const feedbackType = ref<'success' | 'error'>('success');

// Filtro por estado e paginação (dono e petsitter)
const PER_PAGE = 10;
const statusFilter = ref<'all' | BookingStatus>('all');
const currentPage = ref(1);

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

// "12/03/2026" (UTC para a data não recuar um dia nos Açores)
function formatDate(value: string): string {
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleDateString('pt-PT', { timeZone: 'UTC' });
}

function datesLabel(b: BookingItem): string {
    return `${formatDate(b.start_date)} – ${formatDate(b.end_date)}`;
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
    // a idade vem da API (animals.age, em anos) ou é null
    const age = animal.age != null
        ? `${animal.age} ${Number(animal.age) === 1 ? 'ano' : 'anos'}`
        : '';
    return [animal.species, animal.breed, age, animal.size].filter(Boolean).join(' · ');
}

// Cartão do dono: "Rex, Mia"
function animalNames(b: BookingItem): string {
    return (b.animals ?? []).map((a) => a.name).join(', ') || '--';
}

// Em cada reserva, o user é o petsitter ou o dono (bookings.petsitter_id é o id do user)
const isPetsitterOf = (b: BookingItem) => b.petsitter_id === currentUserId.value;

// O cartão das Reservas mostra a outra pessoa: o petsitter, ou o dono se fores tu o petsitter
const otherPerson = (b: BookingItem): Person | undefined => (isPetsitterOf(b) ? b.owner : b.petsitter);

// Conversar abre o chat com a outra pessoa da reserva (ids de users)
const chatWith = (b: BookingItem) => (isPetsitterOf(b) ? b.user_id : b.petsitter_id);

const statusShort: Record<string, string> = {
    pending: 'Pendente',
    approved: 'Aceite',
    rejected: 'Rejeitada',
    cancelled: 'Cancelada',
    completed: 'Concluída',
};

const statusFilters: { value: 'all' | BookingStatus; label: string }[] = [
    { value: 'all', label: 'Todas' },
    { value: 'approved', label: 'Aceites' },
    { value: 'rejected', label: 'Rejeitadas' },
    { value: 'cancelled', label: 'Canceladas' },
    { value: 'completed', label: 'Concluídas' },
];
const statusMenu = ref<HTMLDetailsElement | null>(null);
const selectedStatusLabel = computed(() =>
    statusFilters.find((filter) => filter.value === statusFilter.value)?.label ?? 'Todas',
);

function selectStatusFilter(value: 'all' | BookingStatus) {
    statusFilter.value = value;
    statusMenu.value?.removeAttribute('open');
}

function closeStatusMenuOnOutsideClick(event: MouseEvent) {
    const menu = statusMenu.value;
    if (menu?.open && event.target instanceof Node && !menu.contains(event.target)) {
        menu.removeAttribute('open');
    }
}

// ---------- Lista: filtro, ordenação e paginação ----------
// Mais recente primeiro (created_at; em empate, id mais alto primeiro)
const sortedBookings = computed(() =>
    [...bookings.value].sort((a, b) => {
        const diff = new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
        return diff !== 0 && !Number.isNaN(diff) ? diff : b.id - a.id;
    }),
);

const filteredBookings = computed(() =>
    statusFilter.value === 'all'
        ? sortedBookings.value
        : sortedBookings.value.filter((b) => b.status === statusFilter.value),
);

const totalPages = computed(() => Math.max(1, Math.ceil(filteredBookings.value.length / PER_PAGE)));

const pagedBookings = computed(() => {
    const start = (currentPage.value - 1) * PER_PAGE;
    return filteredBookings.value.slice(start, start + PER_PAGE);
});

// Ao mudar de filtro volta à página 1; se a página atual deixar de existir, recua
watch(statusFilter, () => {
    currentPage.value = 1;
});
watch(totalPages, (n) => {
    if (currentPage.value > n) currentPage.value = n;
});

function goToPage(n: number) {
    currentPage.value = Math.min(Math.max(1, n), totalPages.value);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ---------- Carregar ----------
const loadBookings = async () => {
    loading.value = true;
    loadError.value = '';
    try {
        // Pedidos: só os pendentes, filtrados na API (?pending=1).
        // Reservas: só estados já respondidos, com filtro adicional nos botões.
        // A API pagina de 15 em 15, por isso lê todas as páginas.
        const pendingParam = props.role === 'petsitter' || props.pendingOnly ? '&pending=1' : '';
        const all: BookingItem[] = [];
        let pageNumber = 1;
        let lastPage = 1;
        do {
            const res = await apiFetch(`/api/bookings?page=${pageNumber}${pendingParam}`);
            if (!res.ok) {
                loadError.value = props.role === 'petsitter' || props.pendingOnly
                    ? 'Não foi possível carregar os pedidos de reserva.'
                    : 'Não foi possível carregar as reservas.';
                return;
            }
            const json = await res.json();
            all.push(...(json.data ?? []));
            lastPage = json.meta?.last_page ?? pageNumber;
            pageNumber++;
        } while (pageNumber <= lastPage);

        // A API devolve as reservas onde és dono OU petsitter; o lado e estado determinam a aba.
        bookings.value = props.role === 'petsitter'
            ? all.filter((b) => isPetsitterOf(b) && b.status === 'pending')
            : props.pendingOnly
                ? all.filter((b) => b.user_id === currentUserId.value && b.status === 'pending')
                : all.filter((b) => (b.user_id === currentUserId.value || isPetsitterOf(b)) && b.status !== 'pending');
    } catch {
        loadError.value = 'Erro de conexão. Tente novamente.';
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

// Sem recarregar a página: nos Pedidos o pedido respondido sai da lista (passa para as Reservas);
// nas Reservas o cartão muda logo de estado
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
                setFeedback(`${successMessages[newStatus]} Podes vê-la nas Reservas.`, 'success');
            } else if (props.pendingOnly) {
                bookings.value = bookings.value.filter((item) => item.id !== b.id);
                setFeedback(successMessages[newStatus], 'success');
            } else {
                b.status = newStatus;
                setFeedback(successMessages[newStatus], 'success');
            }
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
            age: a.age != null ? `${a.age} ${a.age === 1 ? 'ano' : 'anos'}` : undefined,
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
    document.addEventListener('click', closeStatusMenuOnOutsideClick);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', closeStatusMenuOnOutsideClick);
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

    <!-- Carregamento -->
    <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-secondary" role="status">
            <span class="visually-hidden">A carregar...</span>
        </div>
    </div>

    <!-- Erro -->
    <div v-else-if="loadError" class="text-center py-5">
        <p class="text-danger mb-3" role="alert">{{ loadError }}</p>
        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" @click="loadBookings">
            Tentar novamente
        </button>
    </div>

    <template v-else>
        <!-- Filtro por estado (só nas Reservas; nos Pedidos estão todos pendentes) -->
        <details v-if="role === 'owner' && !pendingOnly" ref="statusMenu" class="status-filter-menu mb-4">
            <summary class="status-filter-trigger" aria-label="Filtrar reservas por estado">
                <Menu :size="20" aria-hidden="true" />
                <span>Estado: {{ selectedStatusLabel }}</span>
                <ChevronDown :size="16" aria-hidden="true" />
            </summary>
            <div class="status-filter-options" role="group" aria-label="Filtrar por estado">
                <button
                    v-for="f in statusFilters"
                    :key="f.value"
                    type="button"
                    class="status-filter-option"
                    :class="{ active: statusFilter === f.value }"
                    :aria-pressed="statusFilter === f.value"
                    @click="selectStatusFilter(f.value)"
                >
                    {{ f.label }}
                </button>
            </div>
        </details>

        <!-- Estado vazio -->
        <div v-if="filteredBookings.length === 0" class="text-center text-secondary py-5">
            <template v-if="role === 'petsitter' || pendingOnly">
                Ainda não tens pedidos de reserva pendentes.
            </template>
            <template v-else>
                {{ bookings.length === 0
                ? 'Ainda não tens reservas.'
                : 'Não tens reservas com este estado.' }}
            </template>
        </div>

        <!-- ===================== Petsitter: pedidos de reserva ===================== -->
        <div v-if="role === 'petsitter'" class="d-flex flex-column gap-4">
            <article
                v-for="booking in pagedBookings"
                :key="booking.id"
                class="card request-card rounded-4 p-4 px-md-5"
            >
                <!--
                    Grelha com 3 colunas (avatar | dono e animal | serviço e preço) e 3 linhas
                    partilhadas, para que cada linha fique à mesma altura nas duas colunas:
                    linha 1: nome / estado, serviço e período
                    linha 2: Animal e datas / Preço
                    linha 3: Ver ficha do animal / botões do estado
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

                    <!-- Animal e datas -->
                    <div class="cell-animal">
                        <p
                            v-for="animal in booking.animals"
                            :key="animal.id"
                            class="mb-0 fs-5"
                        >
                            <strong>Animal:</strong> {{ animalLine(animal) }}
                        </p>
                        <p class="mb-0 fs-5">
                            <strong>Datas:</strong> {{ datesLabel(booking) }}
                        </p>
                    </div>

                    <!-- Ver ficha do animal -->
                    <div class="cell-ficha d-flex flex-column gap-2">
                        <a
                            v-for="animal in booking.animals"
                            :key="animal.id"
                            href="#"
                            class="btn btn-primary btn-ficha d-inline-flex align-items-center justify-content-between"
                            :class="{ disabled: loadingAnimalId === animal.id }"
                            @click.prevent="openAnimal(animal)"
                        >
                            Ver ficha do animal
                            <ChevronRight :size="14" />
                        </a>
                    </div>

                    <!-- Estado, serviço e período -->
                    <div class="cell-service text-center">
                        <span class="status-pill d-block mb-2" :class="`status-${booking.status}`">
                            {{ statusShort[booking.status] || booking.status }}
                        </span>
                        <p class="fs-3 fw-medium mb-1">{{ serviceLabel(booking) }}</p>
                        <small><strong>Período:</strong> {{ periodLabel(booking) }}</small>
                    </div>

                    <!-- Preço -->
                    <div class="cell-price text-center">
                        <p class="fw-bold fs-4 mb-0">Preço: {{ formatPrice(booking.total_price) }}</p>
                    </div>

                    <!-- Ações (dependem do estado) -->
                    <div class="cell-actions d-flex gap-2">
                        <!-- Pendente: Recusar + Aceitar -->
                        <template v-if="booking.status === 'pending'">
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
                        </template>
                    </div>
                </div>
            </article>
        </div>

        <!-- ===================== Reservas: como dono e como petsitter ===================== -->
        <template v-else>

            <article
                v-for="b in pagedBookings"
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

                <!-- Outra pessoa / local / animais / datas -->
                <div class="booking-info">
                    <div>
                        <h2 class="booking-name mb-0">
                            {{ otherPerson(b)?.name ?? (isPetsitterOf(b) ? 'Proprietário' : 'Petsitter') }}
                        </h2>
                        <small class="booking-location d-flex align-items-center gap-1">
                            <MapPin :size="17" />
                            <!-- zona da reserva (onde é o serviço); reservas antigas sem zona: localidade da outra pessoa -->
                            {{ b.zone || otherPerson(b)?.location || '--' }}
                        </small>
                    </div>

                    <div>
                        <p class="booking-animal mb-1">
                            <strong>{{ (b.animals?.length ?? 0) > 1 ? 'Animais' : 'Animal' }}:</strong>
                            {{ animalNames(b) }}
                        </p>
                        <p class="booking-animal mb-0">
                            <strong>Datas:</strong> {{ datesLabel(b) }}
                        </p>
                    </div>
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

                    <!-- Lado do petsitter: reservas que te fizeram (já respondidas) -->
                    <div v-if="isPetsitterOf(b)" class="booking-actions d-flex gap-2 mt-2 w-100">
                        <!-- Aceite: Conversar + Concluir -->
                        <template v-if="b.status === 'approved'">
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
                            class="btn btn-primary btn-sm rounded-pill flex-fill"
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
                                class="btn btn-primary btn-sm rounded-pill flex-fill"
                            >
                                Ver detalhe
                            </a>
                        </template>

                        <!-- Outros estados: ver detalhe -->
                        <a
                            v-else
                            :href="`/reservations?booking=${b.id}`"
                            class="btn btn-primary btn-sm rounded-pill flex-fill"
                        >
                            Ver detalhe
                        </a>
                    </div>
                </div>
            </article>

        </template>

            <!-- Paginação (dono e petsitter) -->
            <nav v-if="totalPages > 1" aria-label="Paginação" class="mt-4">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item" :class="{ disabled: currentPage === 1 }">
                        <button type="button" class="page-link" :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">
                            Anterior
                        </button>
                    </li>
                    <li
                        v-for="n in totalPages"
                        :key="n"
                        class="page-item"
                        :class="{ active: n === currentPage }"
                    >
                        <button
                            type="button"
                            class="page-link"
                            :aria-current="n === currentPage ? 'page' : undefined"
                            @click="goToPage(n)"
                        >
                            {{ n }}
                        </button>
                    </li>
                    <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                        <button type="button" class="page-link" :disabled="currentPage === totalPages" @click="goToPage(currentPage + 1)">
                            Seguinte
                        </button>
                    </li>
                </ul>
            </nav>
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

/* Ver ficha do animal (classe própria, para o Concluir usar o .btn-primary normal do app.css) */
.btn-ficha {
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
    background-color: #f9c9c9;
    color: #dc2626;
}

/* Menu de filtros por estado */
.status-filter-menu {
    position: relative;
    width: fit-content;
}

.status-filter-trigger {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.55rem 0.9rem;
    border: 1px solid var(--patudo-terracota, #c2410c);
    border-radius: 999px;
    color: var(--patudo-terracota, #c2410c);
    background-color: #ffffff;
    font-weight: 600;
    cursor: pointer;
    list-style: none;
}

.status-filter-trigger::-webkit-details-marker {
    display: none;
}

.status-filter-trigger:hover,
.status-filter-menu[open] .status-filter-trigger {
    background-color: #fdeee4;
    color: var(--patudo-terracota, #c2410c);
}

.status-filter-options {
    position: absolute;
    top: calc(100% + 0.45rem);
    left: 0;
    z-index: 20;
    display: flex;
    flex-direction: column;
    min-width: 12rem;
    padding: 0.35rem;
    border: 1px solid var(--patudo-terracota, #c2410c);
    border-radius: 0.85rem;
    background-color: #ffffff;
    box-shadow: 0 0.5rem 1.25rem rgb(46 29 20 / 14%);
}

.status-filter-option {
    padding: 0.5rem 0.7rem;
    border: 0;
    border-radius: 0.55rem;
    color: var(--patudo-terracota, #c2410c);
    background: transparent;
    font-weight: 600;
    text-align: left;
}

.status-filter-option:hover,
.status-filter-option:focus-visible {
    background-color: #fdeee4;
}

.status-filter-option.active {
    color: #ffffff;
    background-color: var(--patudo-terracota, #c2410c);
}

/* Paginação com as cores do tema */
.pagination {
    --bs-pagination-color: var(--patudo-terracota, #c2410c);
    --bs-pagination-hover-color: var(--patudo-terracota, #c2410c);
    --bs-pagination-focus-color: var(--patudo-terracota, #c2410c);
    --bs-pagination-active-bg: var(--patudo-terracota, #c2410c);
    --bs-pagination-active-border-color: var(--patudo-terracota, #c2410c);
    --bs-pagination-active-color: #ffffff;
}

/* Os botões de cada par (ex.: Conversar + Cancelar) ficam com a mesma largura,
   em vez de a largura depender do tamanho do texto (flex-fill) */
.booking-actions .btn {
    /* !important porque o .flex-fill do Bootstrap também usa !important */
    flex: 1 1 0 !important;
    /* todos com o tamanho do .btn-sm: as classes do app.css (.btn-outline-danger, .btn-outline-primary...)
       têm padding próprio e ficavam mais altas que o Conversar */
    --bs-btn-padding-y: 0.25rem;
    --bs-btn-padding-x: 0.5rem;
    line-height: 1.5;
    /* texto centrado também nos links (<a>), como o Conversar */
    display: inline-flex;
    align-items: center;
    justify-content: center;
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

/* Concluir: cor terracota do tema, com o mesmo tamanho do Conversar
   (o .btn-primary do app.css tem padding próprio e ficava maior) */
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
