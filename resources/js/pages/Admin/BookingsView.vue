<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';

type BookingStatus = 'Aceite' | 'Pendente' | 'Concluída' | 'Cancelada';
type FilterTab = 'Todas' | 'Pendentes' | 'Aceites' | 'Concluídas' | 'Canceladas';

interface AdminBooking {
    id: string;
    ownerName: string;
    petName: string;
    sitterName: string;
    location: string;
    service: string;
    period: string;
    total: number;
    status: BookingStatus;
}

interface AdminNavItem {
    label: string;
    href: string;
    badge?: number;
    active?: boolean;
}

const searchQuery = ref('');
const selectedFilter = ref<FilterTab>('Todas');
const currentPage = ref(1);
const pageSize = 8;

const navItems: AdminNavItem[] = [
    { label: 'Painel', href: '#' },
    { label: 'Utilizadores', href: '#' },
    { label: 'Verificações', href: '/admin/petsitters/pending', badge: 5 },
    { label: 'Reservas', href: '/admin/bookings', active: true },
    { label: 'Avaliações', href: '#' },
    { label: 'Denúncias', href: '#', badge: 3 },
    { label: 'Suporte', href: '#', badge: 2 },
    { label: 'Relatórios', href: '#' },
    { label: 'Definições', href: '#' },
];

const filterTabs: FilterTab[] = [
    'Todas',
    'Pendentes',
    'Aceites',
    'Concluídas',
    'Canceladas',
];

// Substituir por dados vindos da API / props do Inertia
const bookings = ref<AdminBooking[]>([
    { id: '#1048', ownerName: 'Nome do dono', petName: 'Animal: Nome', sitterName: 'Nome do cuidador', location: 'Localização', service: 'Diária', period: '12/11 – 15/11', total: 60, status: 'Aceite' },
    { id: '#1047', ownerName: 'Nome do dono', petName: 'Animal: Nome', sitterName: 'Nome do cuidador', location: 'Localização', service: 'Visita', period: '14/11', total: 12, status: 'Pendente' },
    { id: '#1046', ownerName: 'Nome do dono', petName: 'Animal: Nome', sitterName: 'Nome do cuidador', location: 'Localização', service: 'Passeio', period: '02/11 – 03/11', total: 20, status: 'Concluída' },
    { id: '#1045', ownerName: 'Nome do dono', petName: 'Animal: Nome', sitterName: 'Nome do cuidador', location: 'Localização', service: 'Diária', period: '18/11 – 20/11', total: 40, status: 'Aceite' },
    { id: '#1044', ownerName: 'Nome do dono', petName: 'Animal: Nome', sitterName: 'Nome do cuidador', location: 'Localização', service: 'Passeio', period: '10/11', total: 10, status: 'Cancelada' },
    { id: '#1043', ownerName: 'Nome do dono', petName: 'Animal: Nome', sitterName: 'Nome do cuidador', location: 'Localização', service: 'Diária', period: '28/10 – 31/10', total: 60, status: 'Concluída' },
    { id: '#1042', ownerName: 'Nome do dono', petName: 'Animal: Nome', sitterName: 'Nome do cuidador', location: 'Localização', service: 'Visita', period: '20/11', total: 12, status: 'Pendente' },
    { id: '#1041', ownerName: 'Nome do dono', petName: 'Animal: Nome', sitterName: 'Nome do cuidador', location: 'Localização', service: 'Passeio', period: '16/11', total: 20, status: 'Aceite' },
]);

const filteredBookings = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();

    return bookings.value.filter((item) => {
        const matchesStatus =
            selectedFilter.value === 'Todas' ||
            (selectedFilter.value === 'Pendentes' && item.status === 'Pendente') ||
            (selectedFilter.value === 'Aceites' && item.status === 'Aceite') ||
            (selectedFilter.value === 'Concluídas' && item.status === 'Concluída') ||
            (selectedFilter.value === 'Canceladas' && item.status === 'Cancelada');

        const matchesSearch =
            !q ||
            item.id.toLowerCase().includes(q) ||
            item.ownerName.toLowerCase().includes(q) ||
            item.sitterName.toLowerCase().includes(q) ||
            item.petName.toLowerCase().includes(q) ||
            item.location.toLowerCase().includes(q);

        return matchesStatus && matchesSearch;
    });
});

const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredBookings.value.length / pageSize))
);

const paginatedBookings = computed(() => {
    const start = (currentPage.value - 1) * pageSize;
    return filteredBookings.value.slice(start, start + pageSize);
});

function setFilter(tab: FilterTab) {
    selectedFilter.value = tab;
    currentPage.value = 1;
}

function viewBooking(id: string) {
    const numericId = id.replace('#', '');
    // router.visit(`/admin/bookings/${numericId}`);
    console.log('Abrir reserva:', id, numericId);
}

function logout() {
    router.post('/logout');
}

function statusBadgeClass(status: BookingStatus) {
    switch (status) {
        case 'Aceite':
        case 'Concluída':
            return 'status-pill--success';
        case 'Pendente':
            return 'status-pill--pending';
        case 'Cancelada':
            return 'status-pill--cancelled';
    }
}
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
                    <span v-if="item.badge" class="navbar-badge">{{ item.badge }}</span>
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
                    <p class="text-secondary mb-0">486 reservas este mês</p>
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
                            <td class="fw-bold">{{ booking.id }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="person-avatar"></span>
                                    <div>
                                        <div class="fw-semibold">{{ booking.ownerName }}</div>
                                        <small class="text-secondary">{{ booking.petName }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="person-avatar"></span>
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
                                        v-if="booking.status === 'Aceite' || booking.status === 'Concluída'"
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
                                        v-else-if="booking.status === 'Pendente'"
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
                                    {{ booking.status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
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
                    A mostrar {{ paginatedBookings.length }} de {{ filteredBookings.length }}
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
.patudo-admin-shell {
    flex-direction: row;
}

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
