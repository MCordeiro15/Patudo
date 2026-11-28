<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Bell,
    Check,
    Heart,
    LogOut,
    MessageCircle,
    Star,
    UserRound,
    X,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import ModalNotifications from '@/pages/Modals/ModalNotifications.vue';
import ModalReportPetsitter from '@/pages/Modals/ModalReportPetsitter.vue';
import { useNotifications } from '@/composables/useNotifications';
import type { Post } from '@/types/post';

// O id vem do controller (/petsitters/{id}) ou do URL: /profile-owner-view?petsitter=ID
const props = defineProps<{ petsitterId?: number | string }>();
const petsitterId = props.petsitterId
    ? String(props.petsitterId)
    : new URLSearchParams(window.location.search).get('petsitter');

const bookingUrl = computed(() =>
    petsitterId
        ? `/booking?petsitter=${encodeURIComponent(petsitterId)}`
        : '/booking',
);

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

const feedback = ref('');

const posts = ref<Post[]>([]);
const placeholderPost: Post = {
    id: 0,
    petsitter_id: 0,
    title: 'Título do post',
    description:
        'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin sed feugiat diam. Nam sollicitudin finibus dui, non euismod ante feugiat vitae. Vestibulum condimentum arcu id risus maximus, nec elementum tortor gravida.',
    photos: [],
    created_at: null,
};
const visiblePosts = computed(() =>
    petsitterId ? posts.value : [placeholderPost],
);

function formatDate(date: string | null): string {
    if (!date) return '01/01/2026 00:00';
    const d = new Date(date);
    return `${d.toLocaleDateString('pt-PT')} ${d.toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' })}`;
}

const loadPosts = async () => {
    if (!petsitterId) return;
    const res = await apiFetch(`/api/petsitters/${petsitterId}/posts`);
    if (res.ok) posts.value = (await res.json()).data;
};

const isFavourite = ref(false);

const loadFavourite = async () => {
    if (!petsitterId) return;
    const res = await apiFetch('/api/favourites');
    if (res.ok) {
        const list: { user_id?: number; id?: number }[] = (await res.json())
            .data;
        isFavourite.value = list.some(
            (p) => String(p.user_id ?? p.id) === petsitterId,
        );
    }
};

const toggleFavourite = async () => {
    if (!petsitterId) return;
    feedback.value = '';
    await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });
    const res = isFavourite.value
        ? await apiFetch(`/api/favourites/${petsitterId}`, { method: 'DELETE' })
        : await apiFetch('/api/favourites', {
              method: 'POST',
              body: JSON.stringify({ petsitter_id: Number(petsitterId) }),
          });
    if (res.ok) {
        isFavourite.value = !isFavourite.value;
    } else {
        feedback.value =
            (await res.json().catch(() => ({}))).message ??
            'Não foi possível atualizar os favoritos.';
    }
};

const showReportModal = ref(false);
const showNotifications = ref(false);
const { unreadCount } = useNotifications();

const submitReport = async (payload: {
    reason: string;
    description: string;
}) => {
    if (!petsitterId) return;
    feedback.value = '';
    await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });
    const res = await apiFetch('/api/support-tickets', {
        method: 'POST',
        body: JSON.stringify({
            reported_user_id: Number(petsitterId),
            subject: payload.reason,
            description: payload.description || payload.reason,
            type: 'complaint',
        }),
    });
    feedback.value = res.ok
        ? 'Denúncia enviada. Obrigado!'
        : 'Não foi possível enviar a denúncia.';
};

onMounted(() => {
    void loadPosts();
    void loadFavourite();
    void loadPetsitter();
    void loadServices();
});

type PetsitterData = {
    average_rating: number | null;
    reviews_count: number;
    biography: string | null;
    short_bio: string | null;
    service_location: string | null;
    city: string | null;
    price_per_day: number | null;
    accepted_sizes: string[] | null;
    accepted_animal_types: string[] | null;
    availability_days: string[] | null;
    avatar_url: string | null;
    created_at: string | null;
    user?: { name: string };
};

type PetsitterService = {
    id: number;
    type: 'pet-sitting' | 'passeio';
    price: number;
};

const petsitter = ref<PetsitterData | null>(null);
const services = ref<PetsitterService[]>([]);
const servicesLoading = ref(false);
const servicesError = ref('');

const loadPetsitter = async () => {
    if (!petsitterId) return;
    const res = await apiFetch(`/api/petsitters/${petsitterId}`);
    if (res.ok) petsitter.value = (await res.json()).data;
    else feedback.value = 'Não foi possível carregar o petsitter.';
};

const loadServices = async () => {
    if (!petsitterId) return;
    servicesLoading.value = true;
    servicesError.value = '';

    try {
        const res = await apiFetch(`/api/petsitters/${petsitterId}/services`);
        const data = await res.json().catch(() => ({}));
        if (res.ok) services.value = data.data ?? [];
        else
            servicesError.value =
                data.message ?? 'Não foi possível carregar os serviços.';
    } catch {
        servicesError.value = 'Erro de rede ao carregar os serviços.';
    } finally {
        servicesLoading.value = false;
    }
};

const serviceTypeLabel = (type: PetsitterService['type']) =>
    type === 'passeio' ? 'Passeio' : 'Pet Sitting';
const formatServicePrice = (price: number) =>
    `${Number(price).toLocaleString('pt-PT', { maximumFractionDigits: 2 })} €`;

const sizeLabels: Record<string, string> = {
    small: 'Porte Pequeno',
    medium: 'Porte Médio',
    large: 'Porte Grande',
};
const typeLabels: Record<string, string> = {
    dog: 'Cão',
    cat: 'Gato',
    bird: 'Ave',
    other: 'Outro',
};

const demoTags = ['Dog sitting', 'Medium dogs', 'Outdoor'];
const tags = computed(() => {
    if (!petsitter.value) return demoTags;
    return [
        ...(petsitter.value.accepted_animal_types ?? []).map(
            (t) => typeLabels[t] ?? t,
        ),
        ...(petsitter.value.accepted_sizes ?? []).map(
            (s) => sizeLabels[s] ?? s,
        ),
    ].slice(0, 3);
});

const dayList = [
    { key: 'monday', label: 'Seg' },
    { key: 'tuesday', label: 'Ter' },
    { key: 'wednesday', label: 'Qua' },
    { key: 'thursday', label: 'Qui' },
    { key: 'friday', label: 'Sex' },
    { key: 'saturday', label: 'Sáb' },
    { key: 'sunday', label: 'Dom' },
];
const days = computed(() =>
    dayList.map((d) => ({
        label: d.label,
        ok: petsitter.value
            ? (petsitter.value.availability_days ?? []).includes(d.key)
            : d.key !== 'saturday' && d.key !== 'sunday',
    })),
);

const displayName = computed(() =>
    petsitter.value
        ? (petsitter.value.user?.name ?? 'Petsitter')
        : 'Nome do Pet Sitter',
);
const displayLocation = computed(() =>
    petsitter.value
        ? (petsitter.value.service_location ?? petsitter.value.city ?? '--')
        : 'Localização',
);
const displayShortBio = computed(() =>
    petsitter.value
        ? (petsitter.value.short_bio ?? '')
        : 'Breve descrição sobre si e a sua experiência profissional.',
);
const displayAbout = computed(() =>
    petsitter.value
        ? (petsitter.value.biography ??
          'Este petsitter ainda não adicionou uma biografia.')
        : 'Um pouco sobre mim',
);
const displayRating = computed(() => {
    if (!petsitter.value) return '5.0';
    return petsitter.value.average_rating != null
        ? Number(petsitter.value.average_rating).toFixed(1)
        : 'Sem avaliações';
});
const displayReviews = computed(() =>
    petsitter.value ? petsitter.value.reviews_count : '',
);
const displayMemberSince = computed(() =>
    petsitter.value?.created_at
        ? new Date(petsitter.value.created_at).toLocaleDateString('pt-PT')
        : '01/01/1990',
);
</script>

<template>
    <Head title="Perfil do Petsitter" />

    <div
        class="min-vh-100 d-flex flex-column position-relative overflow-hidden patudo-page"
    >
        <div class="patudo-glow patudo-glow-top"></div>
        <div class="patudo-glow patudo-glow-bottom"></div>

        <main class="patudo-content flex-grow-1 py-4">
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
                        <a class="nav-link active rounded-pill px-4" href="/"
                            >Início</a
                        >
                        <a
                            class="nav-link text-dark rounded-pill px-4"
                            href="/orders"
                            >Pedidos</a
                        >
                        <a
                            class="nav-link text-dark rounded-pill px-4"
                            href="/favorites"
                        >
                            Favoritos
                        </a>
                        <a
                            class="nav-link text-dark rounded-pill px-4"
                            href="/reservations"
                        >
                            Reservas
                        </a>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <a href="/messages" class="text-dark"
                            ><MessageCircle :size="25"
                        /></a>
                        <button
                            type="button"
                            class="text-dark bg-transparent border-0 p-0 position-relative"
                            @click="showNotifications = true"
                        >
                            <Bell :size="25" />
                            <span
                                v-if="unreadCount > 0"
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                style="font-size: 0.6rem"
                            >
                                {{ unreadCount > 9 ? '9+' : unreadCount }}
                            </span>
                        </button>
                        <button
                            type="button"
                            class="text-dark p-0 border-0 bg-transparent"
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

                <h1 class="text-center fw-bold display-5 mt-5 pt-4 mb-4">
                    Perfil do Petsitter
                </h1>

                <section
                    class="card bg-white border border-primary rounded-4 p-3 p-md-4 mb-3 position-relative"
                >
                    <button
                        type="button"
                        class="btn btn-primary btn-sm position-absolute top-0 end-0 mt-3 me-3 px-4"
                        @click="showReportModal = true"
                    >
                        Denunciar
                    </button>

                    <div class="row g-4 align-items-center">
                        <div class="col-12 col-md-8">
                            <div class="d-flex align-items-center gap-4">
                                <div
                                    class="profile-avatar d-flex align-items-center justify-content-center rounded-circle border border-primary flex-shrink-0 overflow-hidden"
                                >
                                    <img
                                        v-if="petsitter?.avatar_url"
                                        :src="petsitter.avatar_url"
                                        alt="Foto de perfil"
                                        class="w-100 h-100 object-fit-cover"
                                    />
                                    <UserRound
                                        v-else
                                        :size="64"
                                        class="text-primary"
                                    />
                                </div>

                                <div class="flex-grow-1">
                                    <h2
                                        class="h5 fw-bold mb-0 d-flex align-items-center gap-2"
                                    >
                                        {{ displayName }}
                                        <button
                                            type="button"
                                            class="p-0 border-0 bg-transparent shadow-none d-flex"
                                            aria-label="Favorito"
                                            @click="toggleFavourite"
                                        >
                                            <Star
                                                :size="20"
                                                :fill="
                                                    isFavourite
                                                        ? '#f5b301'
                                                        : 'none'
                                                "
                                            />
                                        </button>
                                    </h2>
                                    <p class="text-muted small mb-2">
                                        {{ displayLocation }}
                                    </p>

                                    <div class="tags-row mb-2">
                                        <span
                                            v-for="t in tags"
                                            :key="t"
                                            class="tag-pill rounded-pill py-1 small"
                                        >
                                            {{ t }}
                                        </span>
                                    </div>

                                    <p class="text-muted small mb-2">
                                        {{ displayShortBio }}
                                    </p>

                                    <div class="actions-row">
                                        <Link
                                            v-if="petsitterId"
                                            :href="bookingUrl"
                                            class="action-btn text-decoration-none text-dark d-flex align-items-center justify-content-center"
                                        >
                                            Reservar
                                        </Link>
                                        <button
                                            v-else
                                            type="button"
                                            class="action-btn"
                                            disabled
                                        >
                                            Reservar
                                        </button>
                                        <!-- abre o chat com este petsitter -->
                                        <Link
                                            v-if="petsitterId"
                                            :href="`/messages?with=${encodeURIComponent(petsitterId)}`"
                                            class="action-btn text-decoration-none text-dark d-flex align-items-center justify-content-center"
                                        >
                                            Chat
                                        </Link>
                                        <button
                                            v-else
                                            type="button"
                                            class="action-btn"
                                            disabled
                                        >
                                            Chat
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div
                                class="border-start border-secondary ps-4 py-2"
                            >
                                <p class="fs-5 text-muted mb-1">
                                    Rating {{ displayRating }}
                                </p>
                                <p class="text-muted mb-2">
                                    Avaliações {{ displayReviews }}
                                </p>
                                <p class="text-muted mb-0">
                                    Membro desde:<br />{{ displayMemberSince }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    class="card bg-white border border-primary rounded-4 p-3 px-md-4 mb-3 about-card"
                >
                    <h2 class="h5 fw-semibold mb-3">Sobre mim</h2>
                    <p class="text-muted fs-5 mb-0 ps-md-2">
                        {{ displayAbout }}
                    </p>
                </section>

                <div class="row g-3 mb-5">
                    <div class="col-12 col-md-6">
                        <section
                            class="card bg-white border border-primary rounded-4 p-3 h-100 info-card"
                        >
                            <h2 class="h6 fw-semibold mb-2">
                                Serviços e preços
                            </h2>
                            <p v-if="servicesLoading" class="small text-muted">
                                A carregar serviços...
                            </p>
                            <p
                                v-if="servicesError"
                                class="small text-danger"
                                role="alert"
                            >
                                {{ servicesError }}
                            </p>
                            <div
                                v-for="service in services"
                                :key="service.id"
                                class="d-flex justify-content-between border-bottom pb-1 mb-2"
                            >
                                <span>{{
                                    serviceTypeLabel(service.type)
                                }}</span>
                                <span>{{
                                    formatServicePrice(service.price)
                                }}</span>
                            </div>
                            <p
                                v-if="
                                    petsitterId &&
                                    !servicesLoading &&
                                    !services.length &&
                                    !servicesError
                                "
                                class="small text-muted mb-0"
                            >
                                Ainda não foram adicionados serviços.
                            </p>
                        </section>
                    </div>
                    <div class="col-12 col-md-6">
                        <section
                            class="card bg-white border border-primary rounded-4 p-3 h-100 info-card"
                        >
                            <h2 class="h6 fw-semibold mb-3">Disponibilidade</h2>
                            <div class="days-grid">
                                <div
                                    v-for="d in days"
                                    :key="d.label"
                                    class="day"
                                    :class="d.ok ? 'day-ok' : 'day-no'"
                                >
                                    <span class="small fw-semibold">{{
                                        d.label
                                    }}</span>
                                    <Check v-if="d.ok" :size="18" />
                                    <X v-else :size="18" />
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <h1 class="text-center fw-bold display-5 mb-4">Publicações</h1>

                <p
                    v-if="petsitterId && !visiblePosts.length"
                    class="text-center text-muted"
                >
                    Ainda não há publicações.
                </p>

                <section
                    v-for="post in visiblePosts"
                    :key="post.id"
                    class="card bg-white border border-primary rounded-4 p-2 mb-4 mx-auto post-card"
                >
                    <div class="d-flex gap-3">
                        <img
                            :src="post.photos[0] ?? '/images/patudo-logo.svg'"
                            alt="Post"
                            class="post-img rounded-3"
                        />
                        <div
                            class="flex-grow-1 d-flex flex-column py-2 pe-2 position-relative"
                        >
                            <h3 class="fs-2 mb-2">{{ post.title }}</h3>
                            <p class="text-muted mb-auto post-text small">
                                {{ post.description }}
                            </p>
                            <small class="text-muted">{{
                                formatDate(post.created_at)
                            }}</small>
                            <button
                                type="button"
                                class="like-btn position-absolute bottom-0 end-0"
                            >
                                <Heart :size="26" />
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            <div
                v-if="feedback"
                class="alert alert-info position-fixed bottom-0 start-50 translate-middle-x mb-3 py-2"
            >
                {{ feedback }}
            </div>

            <ModalReportPetsitter
                :show="showReportModal"
                :petsitter-id="petsitterId ?? undefined"
                @close="showReportModal = false"
                @submit="submitReport"
            />

            <ModalNotifications
                :show="showNotifications"
                @close="showNotifications = false"
            />
        </main>
    </div>
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
.profile-avatar {
    width: 140px;
    height: 140px;
}
.tag-pill {
    background-color: #587257;
    color: #fff;
    text-align: center;
    padding: 0.25rem 1rem;
    display: inline-block;
}
.about-card {
    min-height: 210px;
}

.info-card {
    min-height: 170px;
}

.tags-row,
.actions-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
}
.tags-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    width: 100%;
}
.action-btn {
    background: #fbd5a5;
    border: 1px solid #e8c08f;
    border-radius: 6px;
    font-size: 0.85rem;
    padding: 2px 0;
}
.days-grid {
    display: flex;
    justify-content: flex-start;
    gap: 8px;
    padding: 0;
    width: 100%;
}
.day {
    flex: 1;
    height: 71px;
    border-radius: 14px;
    color: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.day-ok {
    background: #587257;
}
.day-no {
    background: #c8470f;
}
.post-card {
    max-width: 1060px;
    height: 265px;
    padding: 15px !important;
}
.post-img {
    width: 248px;
    height: 248px;
    object-fit: contain;
    background: #fffaf3;
}
.post-text {
    max-width: 380px;
}
.like-btn {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: 0;
    background: #c8470f;
    color: #3a1a0a;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
