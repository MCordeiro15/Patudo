<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';

type BookingStatus = 'pending' | 'approved' | 'rejected' | 'completed' | 'cancelled';
type FilterTab = 'Todas' | 'Pendentes' | 'Aceites' | 'Concluídas' | 'Canceladas';

interface ApiBooking {
    id: number;
    user_id: number;
    petsitter_id: number;
    service_id: number;
    service: { id: number; name: string; price: number } | null;
    owner: { id: number; name: string; location?: string | null; avatar_url?: string | null } | null;
    petsitter: { id: number; name: string; location?: string | null; avatar_url?: string | null } | null;
    start_date: string;
    end_date: string;
    status: BookingStatus;
    total_price: number;
    animals: { id: number; name: string; species: string; breed?: string | null; age?: number | null; photo?: string | null }[];
    review: { id: number; rating: number; comment: string; created_at: string } | null;
    created_at: string;
    message?: string | null;
    zone?: string | null;
    entrance_hour?: string | null;
    exit_hour?: string | null;
}

interface AdminBooking {
    id: number;
    ownerName: string;
    petName: string;
    sitterName: string;
    location: string;
    service: string;
    period: string;
    total: number;
    status: BookingStatus;
    ownerAvatar?: string | null;
    sitterAvatar?: string | null;
}

interface AdminNavItem {
    label: string;
    href: string;
    badge?: number;
    active?: boolean;
}

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

function parseDate(iso?: string): Date | null {
    if (!iso) return null;
    let date: Date;
    if (iso.includes('T')) {
        date = new Date(iso);
    } else if (iso.match(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/)) {
        date = new Date(iso.replace(' ', 'T') + 'Z');
    } else if (iso.match(/^\d{4}-\d{2}-\d{2}$/)) {
        date = new Date(iso + 'T00:00:00Z');
    } else {
        date = new Date(iso);
    }
    return isNaN(date.getTime()) ? null : date;
}

const pendingVerifications = ref(0);

const loadPendingVerifications = async () => {
    try {
        const res = await apiFetch('/api/petsitters?approval_status=pending');
        if (res.ok) {
            const data = await res.json();
            pendingVerifications.value = (data.data ?? data).length;
        }
    } catch {
        pendingVerifications.value = 0;
    }
};

const searchQuery = ref('');
const selectedFilter = ref<FilterTab>('Todas');
const currentPage = ref(1);
const pageSize = 8;
const totalItems = ref(0);
const loading = ref(false);

const navItems = computed<AdminNavItem[]>(() => [
    { label: 'Utilizadores', href: '/admin/users' },
    { label: 'Verificações', href: '/admin/petsitters/pending', badge: pendingVerifications.value },
    { label: 'Reservas', href: '/admin/bookings', active: true },
    { label: 'Avaliações', href: '/admin/reviews' },
    { label: 'Denúncias', href: '/admin/reports', badge: 3 },
    { label: 'Suporte', href: '/admin/support', badge: 2 },
    { label: 'Definições', href: '/admin/settings' },
]);

const filterTabs: FilterTab[] = [
    'Todas',
    'Pendentes',
    'Aceites',
    'Concluídas',
    'Canceladas',
];

const bookings = ref<AdminBooking[]>([]);

const mapApiToAdmin = (api: ApiBooking): AdminBooking => {
    const start = parseDate(api.start_date);
    const end = parseDate(api.end_date);
    const period = start && end
        ? `${start.toLocaleDateString('pt-PT', { day: '2-digit', month: '2-digit' })} – ${end.toLocaleDateString('pt-PT', { day: '2-digit', month: '2-digit' })}`
        : '—';
    const petNames = api.animals.map(a => a.name).join(', ') || '—';

    return {
        id: api.id,
        ownerName: api.owner?.name ?? '—',
        petName: petNames,
        sitterName: api.petsitter?.name ?? '—',
        location: api.petsitter?.location ?? api.owner?.location ?? '—',
        service: api.service?.name ?? '—',
        period,
        total: api.total_price,
        status: api.status,
        ownerAvatar: api.owner?.avatar_url ?? null,
        sitterAvatar: api.petsitter?.avatar_url ?? null,
    };
};

const loadBookings = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        params.set('page', String(currentPage.value));
        params.set('per_page', String(pageSize));

        if (selectedFilter.value === 'Pendentes') {
            params.set('pending', '1');
        } else if (selectedFilter.value !== 'Todas') {
            params.set('pending', '0');
        }

        const res = await apiFetch(`/api/bookings?${params.toString()}`);
        const text = await res.text();
        console.log('[BookingsView] API response:', res.status, text);

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const data = JSON.parse(text);
        const items = data.data ?? data;

        bookings.value = items.map(mapApiToAdmin);
        totalItems.value = data.total ?? data.meta?.total ?? items.length;
    } catch (e) {
        console.error('[BookingsView] loadBookings error:', e);
        bookings.value = [];
        totalItems.value = 0;
    } finally {
        loading.value = false;
    }
};

const totalPages = computed(() => Math.max(1, Math.ceil(totalItems.value / pageSize)));

watch([currentPage, selectedFilter], () => {
    void loadBookings();
}, { deep: true });

onMounted(() => {
    void loadPendingVerifications();
    void loadBookings();
});

function setFilter(tab: FilterTab) {
    selectedFilter.value = tab;
    currentPage.value = 1;
}

function viewBooking(id: number) {
    router.visit(`/admin/bookings/${id}`);
}

function logout() {
    router.post('/logout');
}

function statusBadgeClass(status: BookingStatus) {
    switch (status) {
        case 'approved':
        case 'completed':
            return 'status-pill--success';
        case 'pending':
            return 'status-pill--pending';
        case 'rejected':
        case 'cancelled':
            return 'status-pill--cancelled';
    }
}

const statusLabels: Record<BookingStatus, string> = {
    pending: 'Pendente',
    approved: 'Aceite',
    rejected: 'Recusada',
    completed: 'Concluída',
    cancelled: 'Cancelada',
};

const filteredBookings = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();

    return bookings.value.filter((item) => {
        const matchesSearch =
            !q ||
            String(item.id).includes(q) ||
            item.ownerName.toLowerCase().includes(q) ||
            item.sitterName.toLowerCase().includes(q) ||
            item.petName.toLowerCase().includes(q) ||
            item.location.toLowerCase().includes(q);

        return matchesSearch;
    });
});

const paginatedBookings = computed(() => filteredBookings.value);
</script>

<template>
    <Head title="Reservas — Administração" />

    <div class="patudo-page-shell patudo-admin-shell">
        <aside class="patudo-navbar" aria-label="Menu de administração">
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
                    <span v-if="item.badge !== undefined && item.badge !== null" class="navbar-badge">{{ item.badge }}</span>
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

        <main class="patudo-content bookings-container flex-grow-1 py-4 px-4 px-lg-5">
            <header class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="fw-bold mb-1">Reservas</h1>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="fw-semibold small">Administrador</span>
                    <span class="admin-avatar"></span>
                </div>
            </header>

            <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
                <div class="search-wrapper">
                    <span class="search-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="7" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                        </svg>
                    </span>
                    <input
                        v-model="searchQuery"
                        type="search"
                        class="form-control bookings-search"
                        placeholder="Pesquisar dono, cuidador ou nº"
                        @input="currentPage = 1"
                    />
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button
                        v-for="tab in filterTabs"
                        :key="tab"
                        type="button"
                        class="btn"
                        :class="selectedFilter === tab ? 'btn-primary' : 'btn-outline-primary'"
                        @click="setFilter(tab)"
                    >
                        {{ tab }}
                    </button>
                </div>
            </div>

            <div class="card bookings-shell p-3 p-md-4">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 bookings-table">
                        <thead>
                        <tr class="text-secondary small text-uppercase">
                            <th>Nº</th>
                            <th>Dono</th>
                            <th>Cuidador</th>
                            <th>Serviço</th>
                            <th>Período</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th class="text-end">Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="booking in paginatedBookings" :key="booking.id" class="booking-row">
                            <td class="fw-bold">#{{ booking.id }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img v-if="booking.ownerAvatar" :src="booking.ownerAvatar" :alt="booking.ownerName" class="person-avatar" />
                                    <span v-else class="person-avatar"></span>
                                    <div>
                                        <div class="fw-semibold">{{ booking.ownerName }}</div>
                                        <small class="text-secondary">{{ booking.petName }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img v-if="booking.sitterAvatar" :src="booking.sitterAvatar" :alt="booking.sitterName" class="person-avatar" />
                                    <span v-else class="person-avatar"></span>
                                    <div>
                                        <div class="fw-semibold">{{ booking.sitterName }}</div>
                                        <small class="text-secondary">{{ booking.location }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ booking.service }}</td>
                            <td>{{ booking.period }}</td>
                            <td class="fw-bold">{{ booking.total }} €</td>
                            <td>
                                <span class="status-pill" :class="statusBadgeClass(booking.status)">
                                    <svg
                                        v-if="booking.status === 'approved' || booking.status === 'completed'"
                                        width="13"
                                        height="13"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2.5"
                                        class="me-1"
                                        aria-hidden="true"
                                    >
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                    <svg
                                        v-else-if="booking.status === 'pending'"
                                        width="13"
                                        height="13"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2.2"
                                        class="me-1"
                                        aria-hidden="true"
                                    >
                                        <circle cx="12" cy="12" r="9" />
                                        <polyline points="12 7 12 12 15.5 14" />
                                    </svg>
                                    <svg
                                        v-else
                                        width="13"
                                        height="13"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2.5"
                                        class="me-1"
                                        aria-hidden="true"
                                    >
                                        <line x1="18" y1="6" x2="6" y2="18" />
                                        <line x1="6" y1="6" x2="18" y2="18" />
                                    </svg>
                                    {{ statusLabels[booking.status] }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button
                                    type="button"
                                    class="btn btn-outline-primary"
                                    @click="viewBooking(booking.id)"
                                >
                                    Ver
                                </button>
                            </td>
                        </tr>
                        <tr v-if="paginatedBookings.length === 0">
                            <td colspan="8" class="text-center text-secondary py-4">
                                Nenhuma reserva encontrada com os filtros atuais.
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <footer class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                <small class="text-secondary">
                    A mostrar {{ paginatedBookings.length }} de {{ totalItems }} resultados
                </small>

                <div class="d-flex align-items-center gap-1">
                    <button
                        type="button"
                        class="btn btn-outline-primary page-btn"
                        :disabled="currentPage <= 1"
                        @click="currentPage--"
                    >
                        &lsaquo;
                    </button>
                    <button
                        v-for="page in totalPages"
                        :key="page"
                        type="button"
                        class="btn page-btn"
                        :class="currentPage === page ? 'btn-primary' : 'btn-outline-primary'"
                        @click="currentPage = page"
                    >
                        {{ page }}
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-primary page-btn"
                        :disabled="currentPage >= totalPages"
                        @click="currentPage++"
                    >
                        &rsaquo;
                    </button>
                </div>
            </footer>
        </main>
    </div>
</template>

<style scoped>

.brand-icon,
.brand-text {
    color: var(--patudo-laranjaVivo);
}

.bookings-container {
    max-width: 1360px;
}

.search-wrapper {
    position: relative;
    width: 100%;
    max-width: 290px;
}

.search-icon {
    position: absolute;
    left: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--patudo-castanhoMid);
    display: flex;
    pointer-events: none;
}

.bookings-search {
    padding-left: 2.35rem;
    border-radius: 25px;
    border-color: var(--patudo-terracota);
    background-color: #ffffff;
    font-size: 0.85rem;
}

.bookings-search:focus {
    border-color: var(--patudo-laranjaVivo);
    box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.15);
}

.admin-avatar,
.person-avatar {
    border-radius: 50%;
    background-color: var(--patudo-bege);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.55);
    flex-shrink: 0;
}

.admin-avatar {
    width: 38px;
    height: 38px;
}

.person-avatar {
    width: 34px;
    height: 34px;
}

.bookings-shell {
    background-color: rgba(255, 255, 255, 0.78);
    border-color: var(--patudo-terracota);
}

.bookings-table {
    border-collapse: separate;
    border-spacing: 0 0.7rem;
}

.bookings-table thead th {
    border-bottom: 0;
    padding-top: 0;
    padding-bottom: 0.25rem;
}

.booking-row td {
    background-color: #ffffff;
    border-top: 1px solid rgba(194, 65, 12, 0.38);
    border-bottom: 1px solid rgba(194, 65, 12, 0.38);
}

.booking-row td:first-child {
    border-left: 1px solid rgba(194, 65, 12, 0.38);
    border-top-left-radius: 16px;
    border-bottom-left-radius: 16px;
}

.booking-row td:last-child {
    border-right: 1px solid rgba(194, 65, 12, 0.38);
    border-top-right-radius: 16px;
    border-bottom-right-radius: 16px;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    padding: 0.28rem 0.8rem;
    border-radius: 25px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-pill--success {
    background-color: var(--patudo-verde);
    color: #ffffff;
}

.status-pill--pending {
    background-color: #ffffff;
    color: var(--patudo-terracota);
    border: 1px solid var(--patudo-terracota);
}

.status-pill--cancelled {
    background-color: #ffffff;
    color: #a64b43;
    border: 1px dashed #a64b43;
}

.page-btn {
    padding-left: 0.75rem !important;
    padding-right: 0.75rem !important;
    min-width: 34px;
}
</style>
