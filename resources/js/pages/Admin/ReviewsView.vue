<!--
    PLACEHOLDER PARA TESTES: este front-end é só para testar o backend da moderação de avaliações (KAN-239)
    no browser. Segue o mockup por alto, sem o layout de administração (barra lateral). A página a sério
    fica para o ticket de front-end.
    PLACEHOLDER FOR TESTING: this front-end only exists to test the review moderation backend in the browser.
-->
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Search, Star } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';

type ReviewStatus = 'published' | 'flagged' | 'removed';

type AdminReview = {
    id: number;
    star_rating: number;
    comment: string | null;
    status: ReviewStatus;
    author: { id: number; name: string } | null;
    petsitter: { id: number; name: string } | null;
    created_at: string;
};

type Counts = Record<'all' | ReviewStatus, number>;

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

// Filtros como no mockup (o das Sinalizadas mostra o contador)
const filters: { value: 'all' | ReviewStatus; label: string }[] = [
    { value: 'all', label: 'Todas' },
    { value: 'flagged', label: 'Sinalizadas' },
    { value: 'published', label: 'Publicadas' },
    { value: 'removed', label: 'Removidas' },
];

const statusLabels: Record<ReviewStatus, string> = {
    published: 'Publicada',
    flagged: 'Sinalizada',
    removed: 'Removida',
};

const reviews = ref<AdminReview[]>([]);
const counts = ref<Counts>({ all: 0, published: 0, flagged: 0, removed: 0 });
const status = ref<'all' | ReviewStatus>('all');
const search = ref('');
const page = ref(1);
const lastPage = ref(1);
const loading = ref(false);
const error = ref('');
const busyId = ref<number | null>(null);

const loadReviews = async () => {
    loading.value = true;
    error.value = '';
    const params = new URLSearchParams({ status: status.value, page: String(page.value) });
    if (search.value.trim()) params.set('search', search.value.trim());

    try {
        const res = await apiFetch(`/api/admin/reviews?${params}`);
        if (!res.ok) throw new Error(`Erro ${res.status}`);
        const json = await res.json();
        reviews.value = json.data;
        counts.value = json.meta.counts;
        lastPage.value = json.meta.last_page;
    } catch (e) {
        error.value = `Não foi possível carregar as avaliações (${(e as Error).message}).`;
    } finally {
        loading.value = false;
    }
};

const setFilter = (value: 'all' | ReviewStatus) => {
    status.value = value;
    page.value = 1;
    void loadReviews();
};

const submitSearch = () => {
    page.value = 1;
    void loadReviews();
};

const goToPage = (value: number) => {
    page.value = value;
    void loadReviews();
};

// Publicar ou remover; depois recarrega a lista e os contadores
const moderate = async (review: AdminReview, action: 'publish' | 'remove') => {
    busyId.value = review.id;
    error.value = '';
    await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });
    const res = await apiFetch(`/api/admin/reviews/${review.id}/${action}`, { method: 'POST' });
    busyId.value = null;
    if (!res.ok) {
        error.value = `Não foi possível ${action === 'publish' ? 'publicar' : 'remover'} (erro ${res.status}).`;
        return;
    }
    await loadReviews();
};

const formatDate = (value: string) => new Date(value).toLocaleDateString('pt-PT');

const pages = computed(() => Array.from({ length: lastPage.value }, (_, i) => i + 1));

onMounted(() => {
    void loadReviews();
});
</script>

<template>
    <Head title="Avaliações (admin)" />

    <main class="patudo-page-shell">
        <div class="patudo-content container py-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                <h1 class="h3 fw-bold mb-0">Avaliações</h1>
                <span class="badge text-bg-warning">Placeholder para testes</span>
            </div>
            <p class="text-muted small mb-4">Modera as avaliações sinalizadas pelos utilizadores.</p>

            <!-- Filtros e pesquisa -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div class="d-flex flex-wrap gap-2" role="group" aria-label="Filtrar por estado">
                    <button
                        v-for="f in filters"
                        :key="f.value"
                        type="button"
                        class="btn"
                        :class="status === f.value ? 'btn-primary' : 'btn-outline-primary'"
                        :aria-pressed="status === f.value"
                        @click="setFilter(f.value)"
                    >
                        {{ f.label }}
                        <span v-if="f.value === 'flagged'" class="badge rounded-pill text-bg-danger ms-1">
                            {{ counts.flagged }}
                        </span>
                        <span v-else class="ms-1 opacity-75">({{ counts[f.value] }})</span>
                    </button>
                </div>

                <form class="d-flex gap-2 review-search" role="search" @submit.prevent="submitSearch">
                    <input
                        v-model="search"
                        type="search"
                        class="form-control rounded-pill"
                        placeholder="Pesquisar texto ou utilizador"
                        aria-label="Pesquisar avaliações"
                    />
                    <button type="submit" class="btn btn-primary d-flex align-items-center" aria-label="Pesquisar">
                        <Search :size="16" />
                    </button>
                </form>
            </div>

            <div v-if="error" class="alert alert-danger py-2 small">{{ error }}</div>

            <!-- Lista -->
            <div class="card p-0 overflow-hidden">
                <p v-if="loading" class="text-muted text-center my-4">A carregar...</p>
                <p v-else-if="!reviews.length" class="text-muted text-center my-4">Sem avaliações.</p>

                <div
                    v-for="review in loading ? [] : reviews"
                    :key="review.id"
                    class="review-row d-flex flex-column flex-md-row gap-3 p-3"
                >
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center gap-1 mb-1" :aria-label="`${review.star_rating} estrelas`">
                            <Star
                                v-for="n in 5"
                                :key="n"
                                :size="16"
                                :class="n <= review.star_rating ? 'star-on' : 'star-off'"
                            />
                        </div>
                        <p class="mb-1">{{ review.comment || 'Sem comentário.' }}</p>
                        <p class="small text-muted mb-0">
                            <strong>{{ review.author?.name ?? '—' }}</strong>
                            sobre
                            <strong>{{ review.petsitter?.name ?? '—' }}</strong>
                            · {{ formatDate(review.created_at) }}
                        </p>
                    </div>

                    <div class="d-flex flex-md-column align-items-md-end justify-content-between gap-2 flex-shrink-0">
                        <span class="badge rounded-pill" :class="`status-${review.status}`">
                            {{ statusLabels[review.status] }}
                        </span>
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
                    </div>
                </div>
            </div>

            <!-- Paginação -->
            <nav v-if="lastPage > 1" class="d-flex justify-content-center gap-2 mt-3" aria-label="Páginas">
                <button
                    v-for="p in pages"
                    :key="p"
                    type="button"
                    class="btn btn-sm"
                    :class="p === page ? 'btn-primary' : 'btn-outline-primary'"
                    :aria-current="p === page ? 'page' : undefined"
                    @click="goToPage(p)"
                >
                    {{ p }}
                </button>
            </nav>
        </div>
    </main>
</template>

<style scoped>
.review-search {
    min-width: 16rem;
}

.review-row + .review-row {
    border-top: 1px solid rgba(194, 65, 12, 0.2);
}

.min-w-0 {
    min-width: 0;
}

.star-on {
    color: #f59e0b;
    fill: #f59e0b;
}

.star-off {
    color: #d6d3d1;
}

.status-published {
    background-color: #dcfce7;
    color: #166534;
}

.status-flagged {
    background-color: #fef3c7;
    color: #92400e;
}

.status-removed {
    background-color: #fee2e2;
    color: #991b1b;
}
</style>
