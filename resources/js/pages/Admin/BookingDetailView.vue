<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeft, CircleCheck, CircleX, Clock } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';

type BookingStatus =
    | 'pending'
    | 'approved'
    | 'rejected'
    | 'completed'
    | 'cancelled';

interface Animal {
    id: number;
    name?: string | null;
    pet_name?: string | null;
    animal_name?: string | null;
    species?: string | null;
    breed?: string | null;
    age?: number | null;
    photo?: string | null;
}

interface Service {
    id: number;
    name?: string | null;
    title?: string | null;
    price?: number | null;
    unit?: string | null;
}

interface ParticipantUser {
    id: number;
    name: string;
    location?: string | null;
    avatar_url?: string | null;
}

interface PetsitterUser extends ParticipantUser {
    rating?: number | null;
}

interface Review {
    id: number;
    rating: number;
    comment: string;
    created_at: string;
}

interface Booking {
    id: number;
    user_id: number;
    petsitter_id: number;
    service_id: number;
    service: Service | null;
    owner: ParticipantUser | null;
    petsitter: PetsitterUser | null;
    start_date: string;
    end_date: string;
    status: BookingStatus;
    total_price: number;
    animals?: Animal[];
    animal?: Animal | null;
    review: Review | null;
    created_at: string;
    updated_at?: string | null;
    message?: string | null;
    zone?: string | null;
    entrance_hour?: string | null;
    exit_hour?: string | null;
}

interface TimelineStep {
    label: string;
    dateText: string;
    completed: boolean;
}

const props = defineProps<{
    bookingId: number;
}>();

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

const navItems = computed(() => [
    { label: 'Utilizadores', href: '/admin/users' },
    { label: 'Verificações', href: '/admin/petsitters/pending', badge: 5 },
    { label: 'Reservas', href: '/admin/bookings', active: true },
    { label: 'Avaliações', href: '/admin/reviews' },
    { label: 'Denúncias', href: '/admin/reports', badge: 3 },
    { label: 'Suporte', href: '/admin/support', badge: 2 },
    { label: 'Definições', href: '/admin/settings' },
]);

const booking = ref<Booking | null>(null);
const loading = ref(true);
const loadError = ref('');
const busy = ref(false);
const actionError = ref('');

const statusLabels: Record<BookingStatus, string> = {
    pending: 'Pendente',
    approved: 'Aceite',
    rejected: 'Recusada',
    completed: 'Concluída',
    cancelled: 'Cancelada',
};

const statusIcons: Record<BookingStatus, typeof Clock> = {
    pending: Clock,
    approved: CircleCheck,
    rejected: CircleX,
    completed: CircleCheck,
    cancelled: CircleX,
};

function statusBadgeClass(status: BookingStatus): string {
    switch (status) {
        case 'pending':
            return 'status-pill--pending';
        case 'approved':
            return 'status-pill--approved';
        case 'completed':
            return 'status-pill--completed';
        case 'rejected':
        case 'cancelled':
            return 'status-pill--cancelled';
    }
}

const canCancel = computed(() => {
    if (!booking.value) return false;
    return (
        booking.value.status === 'pending' ||
        booking.value.status === 'approved'
    );
});

function parseDate(iso?: string | null): Date | null {
    if (!iso) return null;
    let date: Date;
    if (iso.includes('T')) {
        date = new Date(iso);
    } else if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(iso)) {
        date = new Date(`${iso.replace(' ', 'T')}Z`);
    } else if (/^\d{4}-\d{2}-\d{2}$/.test(iso)) {
        date = new Date(`${iso}T00:00:00Z`);
    } else {
        date = new Date(iso);
    }
    return Number.isNaN(date.getTime()) ? null : date;
}

const formatDate = (iso?: string | null) => {
    const date = parseDate(iso);
    return date
        ? date.toLocaleDateString('pt-PT', {
              day: '2-digit',
              month: '2-digit',
              year: 'numeric',
          })
        : '—';
};

const formatShortDate = (iso?: string | null) => {
    const date = parseDate(iso);
    return date
        ? date.toLocaleDateString('pt-PT', { day: '2-digit', month: '2-digit' })
        : '—';
};

const formatDateWithTime = (iso?: string | null) => {
    const date = parseDate(iso);
    if (!date) return '—';
    const d = date.toLocaleDateString('pt-PT', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
    const t = date.toLocaleTimeString('pt-PT', {
        hour: '2-digit',
        minute: '2-digit',
    });
    return `${d} · ${t}`;
};

const serviceName = computed(() => {
    const raw =
        booking.value?.service?.name ?? booking.value?.service?.title ?? '';
    return raw.trim() || 'Serviço';
});

const isWalkService = computed(() =>
    serviceName.value.toLowerCase().includes('passeio'),
);

const formattedPeriod = computed(() => {
    if (!booking.value) return '—';
    const start = formatShortDate(booking.value.start_date);
    const end = formatDate(booking.value.end_date);
    return start === formatShortDate(booking.value.end_date)
        ? end
        : `${start} – ${end}`;
});

const durationUnits = computed(() => {
    if (!booking.value) return 1;
    const start = parseDate(booking.value.start_date);
    const end = parseDate(booking.value.end_date);
    if (!start || !end) return 1;
    const diffDays = Math.max(
        0,
        Math.round((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)),
    );
    return isWalkService.value ? diffDays + 1 : Math.max(1, diffDays);
});

const durationLabel = computed(() => {
    const units = durationUnits.value;
    if (isWalkService.value) {
        return `${units} ${units === 1 ? 'passeio' : 'passeios'}`;
    }
    return `${units} ${units === 1 ? 'noite' : 'noites'}`;
});

const unitPriceLabel = computed(() => {
    const b = booking.value;
    if (!b) return '0 € / dia';

    const unit = isWalkService.value ? 'passeio' : 'dia';
    const directPrice = b.service?.price != null ? Number(b.service.price) : 0;

    if (directPrice > 0) {
        return `${directPrice} € / ${unit}`;
    }

    const total = Number(b.total_price ?? 0);
    const units = durationUnits.value;
    if (total > 0 && units > 0) {
        const estimated = total / units;
        const formatted = Number.isInteger(estimated)
            ? String(estimated)
            : estimated.toFixed(2).replace('.', ',');
        return `${formatted} € / ${unit}`;
    }

    return `0 € / ${unit}`;
});

function extractAnimalName(animal?: Animal | null): string | null {
    if (!animal) return null;
    const candidates = [animal.name, animal.pet_name, animal.animal_name]
        .map((v) => v?.trim())
        .filter((v): v is string => Boolean(v));

    if (!candidates.length) return null;

    const specific = candidates.find((name) => !/^pet\s+de\s+/i.test(name));
    return specific ?? candidates[0];
}

const primaryAnimalName = computed(() => {
    const b = booking.value;
    if (!b) return '—';

    const list = b.animals?.length ? b.animals : b.animal ? [b.animal] : [];
    const names = list
        .map((a) => extractAnimalName(a))
        .filter((name): name is string => Boolean(name));

    return names.length ? names.join(', ') : '—';
});

const sitterRatingText = computed(() => {
    const rating =
        booking.value?.petsitter?.rating ?? booking.value?.review?.rating;
    if (rating == null) return '5,0';
    return Number(rating).toFixed(1).replace('.', ',');
});

const timelineSteps = computed<TimelineStep[]>(() => {
    const b = booking.value;
    if (!b) return [];

    const createdAt = parseDate(b.created_at);
    const startDate = parseDate(b.start_date);
    const endDate = parseDate(b.end_date);
    const updatedAt = parseDate(b.updated_at ?? b.created_at);

    const createdBeforeStart =
        createdAt && startDate
            ? createdAt.getTime() <= startDate.getTime()
            : true;

    const isAcceptedOrBeyond =
        b.status === 'approved' || b.status === 'completed';
    const isCompleted = b.status === 'completed';
    const isCancelledOrRejected =
        b.status === 'cancelled' || b.status === 'rejected';

    if (createdBeforeStart) {
        const steps: TimelineStep[] = [
            {
                label: 'Pedido criado',
                dateText: formatDateWithTime(b.created_at),
                completed: true,
            },
        ];

        if (isCancelledOrRejected) {
            steps.push({
                label:
                    b.status === 'rejected'
                        ? 'Recusado pelo cuidador'
                        : 'Reserva cancelada',
                dateText: formatDateWithTime(b.updated_at ?? b.created_at),
                completed: true,
            });
        } else {
            steps.push({
                label: 'Aceite pelo cuidador',
                dateText: isAcceptedOrBeyond
                    ? formatDateWithTime(b.updated_at ?? b.created_at)
                    : 'Aguarda resposta',
                completed: isAcceptedOrBeyond,
            });
        }

        steps.push(
            {
                label: 'Início da reserva',
                dateText: formatDate(b.start_date),
                completed: isCompleted,
            },
            {
                label: 'Fim da reserva',
                dateText: formatDate(b.end_date),
                completed: isCompleted,
            },
        );

        return steps;
    }

    const steps: TimelineStep[] = [
        {
            label: 'Pedido criado',
            dateText: formatDateWithTime(b.created_at),
            completed: true,
        },
    ];

    if (isCancelledOrRejected) {
        steps.push({
            label:
                b.status === 'rejected'
                    ? 'Recusado pelo cuidador'
                    : 'Reserva cancelada',
            dateText: formatDateWithTime(b.updated_at ?? b.created_at),
            completed: true,
        });
    } else if (isAcceptedOrBeyond) {
        steps.push({
            label: 'Aceite pelo cuidador',
            dateText: formatDateWithTime(
                updatedAt ? (b.updated_at ?? b.created_at) : b.created_at,
            ),
            completed: true,
        });
    }

    if (isCompleted) {
        steps.push({
            label: 'Reserva concluída',
            dateText: formatDateWithTime(b.updated_at ?? b.created_at),
            completed: true,
        });
    } else if (startDate && endDate) {
        steps.push({
            label: 'Período agendado',
            dateText: `${formatDate(b.start_date)} – ${formatDate(b.end_date)}`,
            completed: false,
        });
    }

    return steps;
});

const load = async () => {
    loading.value = true;
    loadError.value = '';
    try {
        const res = await apiFetch(`/api/bookings/${props.bookingId}`);
        if (!res.ok) throw new Error();
        const data = await res.json();
        booking.value = data.data ?? data;
    } catch {
        loadError.value = 'Não foi possível carregar a reserva.';
        booking.value = null;
    } finally {
        loading.value = false;
    }
};

const cancelBooking = async () => {
    if (!booking.value || busy.value || !canCancel.value) return;
    busy.value = true;
    actionError.value = '';
    try {
        const res = await apiFetch(`/api/bookings/${booking.value.id}/status`, {
            method: 'PATCH',
            body: JSON.stringify({ status: 'cancelled' }),
        });
        if (res.ok) {
            const data = await res.json();
            booking.value = data.data ?? data;
        } else {
            const body = await res.json().catch(() => null);
            actionError.value =
                body?.message ?? 'Não foi possível cancelar a reserva.';
        }
    } catch {
        actionError.value = 'Erro de conexão. Tente novamente.';
    } finally {
        busy.value = false;
    }
};

const logout = () => {
    router.post('/logout');
};

const goBack = () => {
    router.visit('/admin/bookings');
};

onMounted(() => {
    void load();
});
</script>

<template>
    <Head
        :title="`Reserva #${booking?.id ?? props.bookingId} — Administração`"
    />

    <div class="patudo-page-shell patudo-admin-shell">
        <aside class="patudo-navbar" aria-label="Menu de administração">
            <a
                class="navbar-brand fw-bold fs-4 d-flex align-items-center gap-2"
                href="/"
            >
                <img :src="logoPatudo" alt="Patudo" width="36" height="36" />
                <span>Patudo</span>
            </a>

            <div class="navbar-section-title">Administração</div>

            <nav class="navbar-nav">
                <a
                    v-for="item in navItems"
                    :key="item.label"
                    :href="item.href"
                    class="navbar-link"
                    :class="{ active: item.active }"
                >
                    <span>{{ item.label }}</span>
                    <span
                        v-if="item.badge !== undefined && item.badge !== null"
                        class="navbar-badge"
                        >{{ item.badge }}</span
                    >
                </a>
            </nav>

            <div class="navbar-footer">
                <button
                    type="button"
                    class="navbar-link logout w-100 border-0 bg-transparent text-start"
                    @click="logout"
                >
                    Terminar sessão
                </button>
            </div>
        </aside>

        <main
            class="patudo-content booking-detail-container flex-grow-1 py-4 px-4 px-lg-5"
        >
            <!-- Topo: Voltar a Reservas + Administrador -->
            <div
                class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4"
            >
                <button type="button" class="back-link-btn" @click="goBack">
                    <ChevronLeft :size="18" :stroke-width="2.4" />
                    <span>Voltar a Reservas</span>
                </button>

                <div class="d-flex align-items-center gap-2 ms-auto">
                    <span class="fw-semibold small">Administrador</span>
                    <span class="admin-avatar"></span>
                </div>
            </div>

            <div v-if="loading" class="text-center py-5 text-secondary">
                A carregar...
            </div>
            <div v-else-if="loadError" class="alert alert-danger">
                {{ loadError }}
            </div>

            <template v-else-if="booking">
                <div
                    v-if="actionError"
                    class="alert alert-danger py-2 px-3 mb-3 small"
                >
                    {{ actionError }}
                </div>

                <!-- Cartão de Cabeçalho da Reserva -->
                <section class="patudo-outline-card p-4 mb-4">
                    <div
                        class="d-flex justify-content-between align-items-center flex-wrap gap-3"
                    >
                        <div class="d-flex align-items-center flex-wrap gap-4">
                            <div>
                                <h1 class="booking-title mb-1">
                                    Reserva #{{ booking.id }}
                                </h1>
                                <p class="text-secondary small mb-0">
                                    Criada a
                                    {{ formatDate(booking.created_at) }} ·
                                    {{ serviceName }}
                                </p>
                            </div>

                            <span
                                class="status-pill"
                                :class="statusBadgeClass(booking.status)"
                            >
                                <component
                                    :is="statusIcons[booking.status]"
                                    :size="14"
                                    :stroke-width="2.5"
                                    class="me-1"
                                />
                                {{ statusLabels[booking.status] }}
                            </span>
                        </div>

                        <button
                            v-if="canCancel"
                            type="button"
                            class="btn btn-cancel-booking"
                            :disabled="busy"
                            @click="cancelBooking"
                        >
                            <span
                                v-if="busy"
                                class="spinner-border spinner-border-sm me-2"
                                role="status"
                            ></span>
                            Cancelar reserva
                        </button>
                    </div>
                </section>

                <!-- Cartões Dono e Cuidador -->
                <section class="row g-4 mb-4">
                    <div class="col-12 col-lg-6">
                        <div class="patudo-outline-card p-4 h-100">
                            <div
                                class="d-flex justify-content-between align-items-center mb-3"
                            >
                                <span class="card-kicker">DONO</span>
                                <a
                                    :href="
                                        booking.owner?.id
                                            ? `/admin/users/${booking.owner.id}`
                                            : '#'
                                    "
                                    class="profile-link"
                                >
                                    Ver perfil &rsaquo;
                                </a>
                            </div>

                            <div class="d-flex align-items-center gap-3">
                                <img
                                    v-if="booking.owner?.avatar_url"
                                    :src="booking.owner.avatar_url"
                                    :alt="booking.owner.name"
                                    class="person-avatar"
                                />
                                <span v-else class="person-avatar"></span>

                                <div class="min-w-0">
                                    <div class="fw-bold fs-6 text-truncate">
                                        {{
                                            booking.owner?.name ??
                                            'Nome do dono'
                                        }}
                                    </div>
                                    <div
                                        class="text-secondary small text-truncate"
                                    >
                                        {{
                                            booking.owner?.location ??
                                            'Localização'
                                        }}
                                        · Animal: {{ primaryAnimalName }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="patudo-outline-card p-4 h-100">
                            <div
                                class="d-flex justify-content-between align-items-center mb-3"
                            >
                                <span class="card-kicker">CUIDADOR</span>
                                <a
                                    :href="
                                        booking.petsitter?.id
                                            ? `/admin/users/${booking.petsitter.id}`
                                            : '#'
                                    "
                                    class="profile-link"
                                >
                                    Ver perfil &rsaquo;
                                </a>
                            </div>

                            <div class="d-flex align-items-center gap-3">
                                <img
                                    v-if="booking.petsitter?.avatar_url"
                                    :src="booking.petsitter.avatar_url"
                                    :alt="booking.petsitter.name"
                                    class="person-avatar"
                                />
                                <span v-else class="person-avatar"></span>

                                <div class="min-w-0">
                                    <div class="fw-bold fs-6 text-truncate">
                                        {{
                                            booking.petsitter?.name ??
                                            'Nome do cuidador'
                                        }}
                                    </div>
                                    <div
                                        class="text-secondary small text-truncate"
                                    >
                                        {{
                                            booking.petsitter?.location ??
                                            'Localização'
                                        }}
                                        · ★ {{ sitterRatingText }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Cartões Detalhes e Histórico de estados -->
                <section class="row g-4">
                    <div class="col-12 col-lg-6">
                        <div class="patudo-outline-card p-4 h-100">
                            <h2 class="section-card-title mb-4">Detalhes</h2>

                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="detail-label">Serviço</div>
                                    <div class="detail-value">
                                        {{ serviceName }}
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="detail-label">Animal</div>
                                    <div class="detail-value">
                                        {{ primaryAnimalName }}
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="detail-label">Período</div>
                                    <div class="detail-value">
                                        {{ formattedPeriod }}
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="detail-label">Duração</div>
                                    <div class="detail-value">
                                        {{ durationLabel }}
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="detail-label">Preço</div>
                                    <div class="detail-value">
                                        {{ unitPriceLabel }}
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="detail-label">Total</div>
                                    <div class="detail-value">
                                        {{ booking.total_price }} €
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="patudo-outline-card p-4 h-100">
                            <h2 class="section-card-title mb-4">
                                Histórico de estados
                            </h2>

                            <ul class="status-timeline list-unstyled mb-0">
                                <li
                                    v-for="(step, idx) in timelineSteps"
                                    :key="step.label"
                                    class="timeline-item"
                                    :class="{
                                        'is-completed': step.completed,
                                        'is-last':
                                            idx === timelineSteps.length - 1,
                                    }"
                                >
                                    <span
                                        class="timeline-dot"
                                        aria-hidden="true"
                                    ></span>
                                    <div
                                        class="d-flex justify-content-between align-items-baseline gap-2 flex-wrap w-100"
                                    >
                                        <span class="timeline-label">{{
                                            step.label
                                        }}</span>
                                        <span class="timeline-date">{{
                                            step.dateText
                                        }}</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>
            </template>
        </main>
    </div>
</template>

<style scoped>
.patudo-admin-shell {
    flex-direction: row;
}

.booking-detail-container {
    max-width: 1360px;
}

.back-link-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    border: 0;
    background: transparent;
    padding: 0;
    color: var(--patudo-castanhoDark);
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    transition: color 0.15s ease;
}

.back-link-btn:hover {
    color: var(--patudo-terracota);
}

.admin-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background-color: var(--patudo-bege);
    border: 1px solid var(--patudo-castanhoDark);
    flex-shrink: 0;
}

.person-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background-color: var(--patudo-bege);
    border: 1px solid var(--patudo-terracota);
    flex-shrink: 0;
    object-fit: cover;
}

.patudo-outline-card {
    background-color: rgba(255, 255, 255, 0.45);
    border: 1px solid var(--patudo-terracota);
    border-radius: var(--bs-border-radius-xl);
}

.booking-title {
    font-size: 1.55rem;
    font-weight: 800;
    color: var(--patudo-castanhoDark);
}

.section-card-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--patudo-castanhoDark);
}

.card-kicker {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--patudo-castanhoMid);
}

.profile-link {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--patudo-terracota);
    text-decoration: underline;
    text-underline-offset: 3px;
}

.profile-link:hover {
    color: var(--patudo-laranjaVivo);
}

.status-pill {
    display: inline-flex;
    align-items: center;
    padding: 0.32rem 0.9rem;
    border-radius: 25px;
    font-size: 0.78rem;
    font-weight: 600;
}

.status-pill--approved {
    background-color: var(--patudo-verde);
    color: #ffffff;
}

.status-pill--pending {
    background-color: #ffffff;
    color: var(--patudo-terracota);
    border: 1px solid var(--patudo-terracota);
}

.status-pill--completed {
    background-color: var(--patudo-castanhoDark);
    color: #ffffff;
}

.status-pill--cancelled {
    background-color: #ffffff;
    color: #a64b43;
    border: 1px dashed #a64b43;
}

.btn-cancel-booking {
    background-color: var(--patudo-terracota);
    border-color: var(--patudo-terracota);
    color: #ffffff;
    font-size: 0.82rem;
    font-weight: 700;
    padding: 0.65rem 1.75rem;
    border-radius: 25px;
}

.btn-cancel-booking:hover:not(:disabled) {
    background-color: var(--patudo-laranjaVivo);
    border-color: var(--patudo-laranjaVivo);
    color: #ffffff;
}

.detail-label {
    font-size: 0.76rem;
    color: var(--patudo-castanhoMid);
    margin-bottom: 0.15rem;
}

.detail-value {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--patudo-castanhoDark);
}

.status-timeline {
    display: flex;
    flex-direction: column;
}

.timeline-item {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    padding-bottom: 1.15rem;
}

.timeline-item.is-last {
    padding-bottom: 0;
}

.timeline-item:not(.is-last)::before {
    content: '';
    position: absolute;
    left: 6px;
    top: 15px;
    bottom: -3px;
    width: 1.5px;
    background-color: rgba(var(--bs-primary-rgb), 0.35);
}

.timeline-dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    margin-top: 3px;
    flex-shrink: 0;
    background-color: #ffffff;
    border: 2px solid var(--patudo-terracota);
    position: relative;
    z-index: 1;
}

.timeline-item.is-completed .timeline-dot {
    background-color: var(--patudo-terracota);
}

.timeline-label {
    font-size: 0.86rem;
    font-weight: 700;
    color: var(--patudo-castanhoDark);
}

.timeline-item:not(.is-completed) .timeline-label {
    font-weight: 600;
}

.timeline-date {
    font-size: 0.78rem;
    color: var(--patudo-castanhoMid);
}

@media (max-width: 991.98px) {
    .patudo-admin-shell {
        flex-direction: column;
    }

    .booking-detail-container {
        padding: 1rem !important;
    }
}
</style>
