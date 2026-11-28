<!-- Página do administrador para moderar avaliações: lista com filtros, pesquisa, paginação, publicar e remover. -->
<!-- Admin page to moderate reviews: list with filters, search, pagination, publish and remove. -->
<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Check, ChevronLeft, ChevronRight, Flag, Search, X } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import logoPatudo from '../../../../assets/logoPatudo.svg';

type ReviewStatus = 'published' | 'flagged' | 'removed';
type StatusFilter = 'all' | ReviewStatus;

interface AdminReview {
    id: number;
    star_rating: number;
    comment: string | null;
    status: ReviewStatus;
    author: { id: number; name: string } | null;
    petsitter: { id: number; name: string } | null;
    created_at: string;
}

type Counts = Record<StatusFilter, number>;

interface PageMeta {
    from: number | null;
    to: number | null;
    total: number;
    last_page: number;
    counts: Counts;
}

// ---------- API ----------
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

// ---------- Sidebar ----------
const inertiaPage = usePage();

const menu = computed(() => [
    { label: 'Utilizadores', href: '/admin/users' },
    { label: 'Verificações', href: '/admin/petsitters/pending', badge: inertiaPage.props.adminCounts?.pending_petsitters },
    { label: 'Reservas', href: '/admin/bookings' },
    { label: 'Avaliações', href: '/admin/reviews', active: true },
    { label: 'Denúncias', href: '/admin/reports', badge: inertiaPage.props.adminCounts?.open_reports },
    { label: 'Suporte', href: '/admin/support', badge: inertiaPage.props.adminCounts?.open_support },
    { label: 'Definições', href: '/admin/settings' },
]);

const logout = () => {
    router.post('/logout');
};

// ---------- Estado da página ----------
const statusLabels: Record<ReviewStatus, string> = {
    published: 'Publicada',
    flagged: 'Sinalizada',
    removed: 'Removida',
};

const reviews = ref<AdminReview[]>([]);
const counts = ref<Counts>({ all: 0, published: 0, flagged: 0, removed: 0 });
const meta = ref({ from: 0, to: 0, total: 0 });
const status = ref<StatusFilter>('all');
const search = ref('');
const page = ref(1);
const lastPage = ref(1);
const loading = ref(false);
const error = ref('');
const busyId = ref<number | null>(null);
const expandedId = ref<number | null>(null);

// só a resposta do último pedido é usada (evita resultados antigos a sobrepor-se)
let requestId = 0;

const tabs = computed<{ key: StatusFilter; label: string }[]>(() => [
    { key: 'all', label: 'Todas' },
    { key: 'flagged', label: `Sinalizadas (${counts.value.flagged})` },
    { key: 'published', label: 'Publicadas' },
    { key: 'removed', label: 'Removidas' },
]);

// GET /api/admin/reviews (filtro por estado, pesquisa e página)
const loadReviews = async () => {
    const current = ++requestId;
    loading.value = true;
    error.value = '';

    const params = new URLSearchParams({ status: status.value, page: String(page.value) });
    if (search.value.trim()) params.set('search', search.value.trim());

    try {
        const res = await apiFetch(`/api/admin/reviews?${params}`);
        if (!res.ok) throw new Error(`Erro ${res.status}`);
        const json: { data: AdminReview[]; meta: PageMeta } = await res.json();
        if (current !== requestId) return;

        // ficou sem itens nesta página (ex.: removeu a última): volta à última que existe
        if (!json.data.length && page.value > 1 && page.value > json.meta.last_page) {
            page.value = Math.max(1, json.meta.last_page);
            return loadReviews();
        }

        reviews.value = json.data;
        counts.value = json.meta.counts;
        lastPage.value = json.meta.last_page;
        meta.value = { from: json.meta.from ?? 0, to: json.meta.to ?? 0, total: json.meta.total };
        expandedId.value = null;
    } catch (e) {
        if (current !== requestId) return;
        error.value = `Não foi possível carregar as avaliações (${(e as Error).message}).`;
    } finally {
        if (current === requestId) loading.value = false;
    }
};

const setFilter = (value: StatusFilter) => {
    if (status.value === value) return;
    status.value = value;
    page.value = 1;
    void loadReviews();
};

// pesquisa enquanto escreve, com um pequeno atraso
let searchTimer: ReturnType<typeof setTimeout> | undefined;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        page.value = 1;
        void loadReviews();
    }, 350);
});

const goToPage = (value: number) => {
    if (value < 1 || value > lastPage.value || value === page.value) return;
    page.value = value;
    void loadReviews();
};

// POST /api/admin/reviews/{id}/publish|remove; depois recarrega a lista e os contadores
const moderate = async (review: AdminReview, action: 'publish' | 'remove') => {
    busyId.value = review.id;
    error.value = '';
    try {
        await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });
        const res = await apiFetch(`/api/admin/reviews/${review.id}/${action}`, { method: 'POST' });
        if (!res.ok) {
            error.value = `Não foi possível ${action === 'publish' ? 'publicar' : 'remover'} a avaliação (erro ${res.status}).`;
            return;
        }
        await loadReviews();
    } catch {
        error.value = 'Não foi possível contactar o servidor.';
    } finally {
        busyId.value = null;
    }
};

const toggleRow = (id: number) => {
    expandedId.value = expandedId.value === id ? null : id;
};

// ---------- Formatação ----------
const formatRating = (value: number) => Number(value).toFixed(1).replace('.', ',');

const formatDate = (value: string) =>
    new Date(value).toLocaleDateString('pt-PT', { day: '2-digit', month: '2-digit', year: 'numeric' });

// ---------- Paginação (janela de 3 páginas + "…") ----------
const pageWindow = computed(() => {
    const start = Math.max(1, Math.min(page.value - 1, lastPage.value - 2));
    const end = Math.min(lastPage.value, start + 2);
    return Array.from({ length: end - start + 1 }, (_, i) => start + i);
});

const hasMorePages = computed(() => (pageWindow.value.at(-1) ?? 1) < lastPage.value);

const summary = computed(() =>
    meta.value.total ? `A mostrar ${meta.value.from}–${meta.value.to} de ${meta.value.total}` : 'Sem resultados',
);

onMounted(() => {
    void loadReviews();
});

onBeforeUnmount(() => {
    clearTimeout(searchTimer);
});
</script>

<template>
    <Head title="Avaliações — Administração" />

    <div class="patudo-admin-shell">
        <aside class="patudo-navbar" aria-label="Menu de administração">
            <Link class="navbar-brand fw-bold fs-3 d-flex align-items-center gap-2" href="/">
                <img :src="logoPatudo" alt="Patudo" width="70" height="70" />
                <span>Patudo</span>
            </Link>

            <div class="navbar-section-title">Administração</div>

            <nav class="navbar-nav">
                <Link
                    v-for="item in menu"
                    :key="item.href"
                    :href="item.href"
                    class="navbar-link"
                    :class="{ active: item.active }"
                    :aria-current="item.active ? 'page' : undefined"
                >
                    <span>{{ item.label }}</span>
                    <span v-if="item.badge" class="navbar-badge">{{ item.badge }}</span>
                </Link>
            </nav>

            <div class="navbar-footer">
                <button type="button" class="navbar-link logout w-100 border-0 bg-transparent text-start" @click="logout">
                    Terminar sessão
                </button>
            </div>
        </aside>

        <main class="patudo-content reviews-container flex-grow-1 py-4 px-4 px-lg-5">
            <header class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <h1 class="fw-bold mb-1">Avaliações</h1>

                <div class="d-flex align-items-center gap-2">
                    <span class="fw-semibold small">Administrador</span>
                    <span class="admin-avatar"></span>
                </div>
            </header>

            <!-- Pesquisa e filtros -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div class="search-wrapper">
                    <span class="search-icon" aria-hidden="true">
                        <Search :size="18" :stroke-width="2.2" />
                    </span>
                    <input
                        v-model="search"
                        type="search"
                        class="form-control reviews-search"
                        placeholder="Pesquisar avaliação ou utilizador"
                        aria-label="Pesquisar avaliação ou utilizador"
                    />
                </div>

                <div class="d-flex flex-wrap gap-2" role="group" aria-label="Filtrar por estado">
                    <button
                        v-for="t in tabs"
                        :key="t.key"
                        type="button"
                        class="filter-btn"
                        :class="{ active: status === t.key }"
                        :aria-pressed="status === t.key"
                        @click="setFilter(t.key)"
                    >
                        {{ t.label }}
                    </button>
                </div>
            </div>

            <div v-if="error" class="alert alert-danger py-2 small" role="alert">{{ error }}</div>

            <!-- Tabela -->
            <div class="card reviews-shell p-3 p-md-4">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 reviews-table">
                        <colgroup>
                            <col style="width: 36%" />
                            <col style="width: 16%" />
                            <col style="width: 16%" />
                            <col style="width: 15%" />
                            <col style="width: 17%" />
                        </colgroup>
                        <thead>
                            <tr class="text-secondary small text-uppercase">
                                <th>Avaliação</th>
                                <th>Autor</th>
                                <th>Cuidador</th>
                                <th>Data</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="review in reviews" :key="review.id">
                                <tr
                                    class="review-row"
                                    tabindex="0"
                                    :aria-expanded="expandedId === review.id"
                                    @click="toggleRow(review.id)"
                                    @keydown.enter.prevent="toggleRow(review.id)"
                                    @keydown.space.prevent="toggleRow(review.id)"
                                >
                                    <td>
                                        <div class="cell-review">
                                            <span class="rating">★ {{ formatRating(review.star_rating) }}</span>
                                            <span class="comment">{{ review.comment || 'Sem comentário.' }}</span>
                                        </div>
                                    </td>
                                    <td class="fw-bold cell-ellipsis">{{ review.author?.name ?? '—' }}</td>
                                    <td class="cell-ellipsis">{{ review.petsitter?.name ?? '—' }}</td>
                                    <td class="cell-ellipsis">{{ formatDate(review.created_at) }}</td>
                                    <td>
                                        <span class="status-pill" :class="`status-pill--${review.status}`">
                                            <Check v-if="review.status === 'published'" :size="14" :stroke-width="2.5" />
                                            <Flag v-else-if="review.status === 'flagged'" :size="14" fill="currentColor" />
                                            <X v-else :size="14" :stroke-width="2.5" />
                                            {{ statusLabels[review.status] }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- Detalhe (ao clicar na linha): comentário completo e ações de moderação -->
                                <tr v-if="expandedId === review.id" class="review-detail">
                                    <td colspan="5">
                                        <p class="mb-3">{{ review.comment || 'Sem comentário.' }}</p>
                                        <div class="d-flex gap-2">
                                            <button
                                                v-if="review.status !== 'published'"
                                                type="button"
                                                class="btn btn-outline-primary btn-sm"
                                                :disabled="busyId === review.id"
                                                @click="moderate(review, 'publish')"
                                            >
                                                Publicar
                                            </button>
                                            <button
                                                v-if="review.status !== 'removed'"
                                                type="button"
                                                class="btn btn-outline-danger btn-sm rounded-pill"
                                                :disabled="busyId === review.id"
                                                @click="moderate(review, 'remove')"
                                            >
                                                Remover
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="!reviews.length">
                                <td colspan="5" class="text-center text-secondary py-4">
                                    {{ loading ? 'A carregar...' : 'Nenhuma avaliação encontrada com os filtros atuais.' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Rodapé: resumo e paginação -->
            <footer class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                <small class="text-secondary">{{ summary }}</small>

                <div v-if="lastPage > 1" class="d-flex align-items-center gap-1">
                    <button type="button" class="page-btn" :disabled="page <= 1" aria-label="Página anterior" @click="goToPage(page - 1)">
                        <ChevronLeft :size="16" :stroke-width="2.2" />
                    </button>
                    <button
                        v-for="p in pageWindow"
                        :key="p"
                        type="button"
                        class="page-btn"
                        :class="{ active: page === p }"
                        :aria-current="page === p ? 'page' : undefined"
                        @click="goToPage(p)"
                    >
                        {{ p }}
                    </button>
                    <button
                        v-if="hasMorePages"
                        type="button"
                        class="page-btn"
                        aria-label="Mais páginas"
                        @click="goToPage((pageWindow.at(-1) ?? page) + 1)"
                    >
                        …
                    </button>
                    <button type="button" class="page-btn" :disabled="page >= lastPage" aria-label="Página seguinte" @click="goToPage(page + 1)">
                        <ChevronRight :size="16" :stroke-width="2.2" />
                    </button>
                </div>
            </footer>
        </main>
    </div>
</template>

<style scoped>
/*
    Casca e barra lateral: mesmas classes e valores do app.css da branch das Reservas (patudo-navbar, navbar-link, ...).
    Quando essa branch for integrada, este bloco pode ser apagado e passa a valer o do app.css.
*/
.patudo-admin-shell {
    min-height: 100vh;
    display: flex;
    flex-direction: row;
    background-color: rgba(var(--bs-light-rgb), 0.25);
    background-image:
        radial-gradient(circle at 0% 0%, rgba(194, 65, 12, 0.12) 0%, transparent 32%),
        radial-gradient(circle at 100% 100%, rgba(249, 115, 22, 0.25) 0%, transparent 35%);
}

.patudo-navbar {
    width: 200px;
    min-width: 200px;
    height: 100vh;
    position: sticky;
    top: 0;
    z-index: 1;
    background-color: #ffffff;
    border-right: 1px solid var(--patudo-laranjaVivo);
    display: flex;
    flex-direction: column;
    padding: 1rem;
}

.patudo-navbar .navbar-brand {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    margin-bottom: 1.5rem;
    text-decoration: none;
    color: var(--patudo-castanhoDark);
}

.patudo-navbar .navbar-brand img {
    width: 36px;
    height: 36px;
    object-fit: contain;
}

.patudo-navbar .navbar-section-title {
    font-size: 0.7rem;
    text-transform: uppercase;
    color: var(--patudo-castanhoMid);
    font-weight: 600;
    padding: 0 0.5rem;
    margin-bottom: 0.75rem;
}

.patudo-navbar .navbar-nav {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.patudo-navbar .navbar-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.65rem 1rem;
    border-radius: 25px;
    color: var(--patudo-castanhoDark);
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    transition:
        background-color 0.2s ease,
        color 0.2s ease;
}

.patudo-navbar .navbar-link:hover {
    background-color: rgba(194, 65, 12, 0.08);
    color: var(--patudo-terracota);
}

.patudo-navbar .navbar-link.active,
.patudo-navbar .navbar-link.active:hover {
    background-color: var(--patudo-castanhoDark);
    color: #ffffff;
}

.patudo-navbar .navbar-badge {
    background-color: var(--patudo-terracota);
    color: #ffffff;
    font-size: 0.7rem;
    font-weight: 700;
    min-width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    padding: 0 0.4rem;
}

.patudo-navbar .navbar-footer {
    margin-top: auto;
    padding-top: 1rem;
    border-top: 1px solid var(--patudo-laranjaVivo);
}

.patudo-navbar .navbar-link.logout {
    justify-content: flex-start;
}

/* ---------- Conteúdo ---------- */
.patudo-content {
    flex: 1;
    min-width: 0;
}

.reviews-container {
    max-width: 1360px;
}

.admin-avatar {
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    border-radius: 50%;
    background-color: var(--patudo-bege);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.55);
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
    display: flex;
    color: var(--patudo-castanhoMid);
    pointer-events: none;
}

.reviews-search {
    height: 34px;
    padding: 0 1rem 0 2.5rem;
    border: 2px solid var(--patudo-terracota);
    border-radius: 999px;
    background-color: #fffaf3;
    box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.12);
    color: var(--patudo-castanhoDark);
    font-size: 0.78rem;
}

.reviews-search::placeholder {
    color: var(--patudo-castanhoMid);
    opacity: 0.8;
}

.reviews-search:focus {
    border-color: var(--patudo-terracota);
    background-color: #fffaf3;
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.22);
}

/*
    Filtros: botões próprios (sem .btn do Bootstrap) para a forma ser sempre a mesma.
    Só mudam a cor de fundo e do texto quando estão selecionados.
*/
.filter-btn {
    height: 34px;
    padding: 0 1.1rem;
    border: 2px solid var(--patudo-terracota);
    border-radius: 999px;
    background-color: #fffaf3;
    color: var(--patudo-castanhoDark);
    font-size: 0.75rem;
    font-weight: 700;
    line-height: 1;
    white-space: nowrap;
    transition:
        background-color 0.15s ease,
        color 0.15s ease;
}

.filter-btn:hover {
    background-color: rgba(194, 65, 12, 0.08);
}

.filter-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.3);
}

.filter-btn.active,
.filter-btn.active:hover,
.filter-btn.active:focus,
.filter-btn.active:active {
    border-color: var(--patudo-terracota);
    background-color: var(--patudo-terracota);
    color: #ffffff;
}

/* ---------- Tabela ---------- */
.reviews-shell {
    background-color: rgba(255, 250, 243, 0.8);
    border-color: var(--patudo-terracota);
    border-radius: 16px;
}

.reviews-table {
    min-width: 760px;
    table-layout: fixed;
    border-collapse: separate;
    border-spacing: 0 0.4rem;
}

.reviews-table thead th {
    padding: 0 1rem 0.25rem;
    border-bottom: 0;
    background: transparent;
    font-weight: 500;
    letter-spacing: 0.04em;
    font-size: 0.7rem;
}

.review-row {
    cursor: pointer;
    outline: none;
}

.review-row td,
.review-detail td {
    padding: 0.7rem 1rem;
    background-color: #fffaf3;
    border-top: 1px solid rgba(194, 65, 12, 0.3);
    border-bottom: 1px solid rgba(194, 65, 12, 0.3);
    font-size: 0.82rem;
}

.review-row td:first-child,
.review-detail td {
    border-left: 1px solid rgba(194, 65, 12, 0.3);
    border-top-left-radius: 12px;
    border-bottom-left-radius: 12px;
}

.review-row td:last-child,
.review-detail td {
    border-right: 1px solid rgba(194, 65, 12, 0.3);
    border-top-right-radius: 12px;
    border-bottom-right-radius: 12px;
}

.review-row:hover td {
    background-color: #fff3e6;
}

.review-row:focus-visible td {
    border-color: var(--patudo-terracota);
}

.review-detail td {
    padding: 1rem 1.25rem;
    color: var(--patudo-castanhoMid);
    white-space: normal;
}

.review-detail p {
    white-space: pre-wrap;
}

.cell-ellipsis {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.cell-review {
    display: flex;
    align-items: baseline;
    gap: 1.25rem;
    min-width: 0;
}

.rating {
    flex-shrink: 0;
    font-size: 0.9rem;
    font-weight: 700;
}

.comment {
    min-width: 0;
    overflow: hidden;
    color: var(--patudo-castanhoMid);
    font-size: 0.75rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* ---------- Estados ---------- */
.status-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    min-width: 100px;
    padding: 0.22rem 0.75rem;
    border: 1.5px solid transparent;
    border-radius: 25px;
    font-size: 0.72rem;
    font-weight: 600;
}

.status-pill--published {
    background-color: #fffaf3;
    border-color: var(--patudo-verde);
    color: var(--patudo-castanhoDark);
}

.status-pill--published svg {
    color: var(--patudo-verde);
}

.status-pill--flagged {
    background-color: var(--patudo-terracota);
    border-color: var(--patudo-terracota);
    color: #ffffff;
}

.status-pill--removed {
    background-color: #fff0ee;
    border-style: dashed;
    border-color: #f5a3a3;
    color: #e5484d;
}

/* ---------- Paginação ---------- */
.page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 32px;
    padding: 0 0.6rem;
    border: 2px solid var(--patudo-terracota);
    border-radius: 8px;
    background-color: #fffaf3;
    color: var(--patudo-castanhoDark);
    font-size: 0.78rem;
    font-weight: 700;
    line-height: 1;
}

.page-btn:hover:not(:disabled) {
    background-color: rgba(194, 65, 12, 0.08);
}

.page-btn.active,
.page-btn.active:hover,
.page-btn.active:focus,
.page-btn.active:active {
    background-color: var(--patudo-terracota);
    color: #ffffff;
}

.page-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.3);
}

.page-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

/* ---------- Ecrãs pequenos ---------- */
@media (max-width: 767px) {
    .patudo-admin-shell {
        flex-direction: column;
    }

    .patudo-navbar {
        position: static;
        width: auto;
        min-width: 0;
        height: auto;
        border-right: 0;
        border-bottom: 1px solid var(--patudo-laranjaVivo);
    }

    .patudo-navbar .navbar-nav {
        flex-direction: row;
        flex-wrap: wrap;
    }

    .patudo-navbar .navbar-footer {
        display: none;
    }
}
</style>