<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Heart, LogOut, Star, UserRound } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import AdminNavigationMenu from '@/components/AdminNavigationMenu.vue';
import HeaderNotificationsChat from '@/components/HeaderNotificationsChat.vue';

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

interface FavoritePetsitter {
    id: number;
    user_id?: number;
    petsitter_id?: number;
    name?: string;
    city?: string;
    service_location?: string;
    average_rating?: number | null;
    reviews_count?: number;
    price_per_hour?: number | null;
    price_per_day?: number | null;
    avatar_url?: string | null;
    accepted_animal_types?: string[] | null;
    user?: { name: string };
}

const animalLabelMap: Record<string, string> = {
    dog: 'Dogs',
    dogs: 'Dogs',
    cat: 'Cats',
    cats: 'Cats',
};

function formatAnimalTag(animal: string): string {
    const key = animal.toLowerCase();
    return animalLabelMap[key] ?? animal;
}

const favourites = ref<FavoritePetsitter[]>([]);
const feedback = ref('');

function navigateToMyProfile() {
    window.location.href = '/petsitters/profile';
}

const logout = () => {
    router.post('/logout');
};

const loadFavourites = async () => {
    const res = await apiFetch('/api/favourites');
    if (res.ok) {
        const data = await res.json();
        favourites.value = data.data ?? data;
    } else {
        feedback.value = 'Não foi possível carregar os favoritos.';
    }
};

const removeFavourite = async (petsitterId: number) => {
    const res = await apiFetch(`/api/favourites/${petsitterId}`, { method: 'DELETE' });
    if (res.ok) {
        favourites.value = favourites.value.filter(
            (p) => (p.petsitter_id ?? p.user_id ?? p.id) !== petsitterId
        );
    } else {
        feedback.value = 'Não foi possível remover dos favoritos.';
    }
};

onMounted(() => {
    void loadFavourites();
});
</script>

<template>
    <Head title="Petsitters Favoritos" />
        <main class="patudo-content patudo-page-shell flex-column flex-grow-1">
            <div class="container">
                <!-- Navbar Superior com Logout -->
                <nav
                    class="navbar navbar-expand-lg bg-white border border-primary rounded-4 mt-lg-4 px-3 px-md-4 position-relative"
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

                    <div
                        class="nav nav-pills border rounded-pill p-1 position-absolute start-50 translate-middle-x d-none d-lg-flex"
                    >
                        <a class="nav-link text-dark rounded-pill px-4" href="/">Início</a>
                        <a class="nav-link text-dark rounded-pill px-4" href="/orders">Pedidos</a>
                        <a class="nav-link active rounded-pill px-4" href="/favorites">Favoritos</a>
                        <a class="nav-link text-dark rounded-pill px-4" href="/reservations">Reservas</a>
                        <AdminNavigationMenu />
                    </div>

                    <div class="d-flex align-items-center gap-3 ms-auto">
                        <HeaderNotificationsChat />
                        <button
                            type="button"
                            class="text-dark p-0 border-0 bg-transparent"
                            @click="navigateToMyProfile()"
                        >
                            <UserRound :size="25" />
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-danger rounded-pill d-flex align-items-center gap-2 px-3"
                            @click="logout"
                        >
                            <LogOut :size="16" />
                            <span>Sair</span>
                        </button>
                    </div>
                </nav>

                <h1 class="text-center fw-semibold display-5 mb-2 mt-xl-4">Petsitters favoritos</h1>
                <p class="text-muted mt-3 mb-4">Aqui podes ver os petsitters que guardaste nos teus favoritos.</p>

                <div v-if="feedback" class="alert alert-info text-center mb-4">
                    {{ feedback }}
                </div>

                <div v-if="favourites.length === 0" class="text-center text-muted py-5">
                    Ainda não tens nenhum petsitter nos favoritos.
                </div>

                <!-- Lista de Cards de Favoritos -->
                <div
                    v-for="fav in favourites"
                    :key="fav.id"
                    class="card bg-white border border-primary rounded-4 p-4 mb-3 align-items-center flex-column flex-xl-row justify-content-between gap-3 shadow-sm favorite-card"
                >
                    <!-- Esquerda: Avatar + Info -->
                    <div class="d-flex align-items-center gap-4">
                        <div class="profile-avatar rounded-circle border border-primary overflow-hidden d-flex align-items-center justify-content-center flex-shrink-0">
                            <img
                                v-if="fav.avatar_url"
                                :src="fav.avatar_url"
                                alt="Avatar"
                                class="w-100 h-100 object-fit-cover"
                            />
                            <UserRound v-else :size="48" class="text-primary" />
                        </div>

                        <div>
                            <h2 class="h5 fw-bold mb-1">{{ fav.user?.name ?? fav.name ?? 'Petsitter' }}</h2>
                            <p class="text-muted small mb-1">{{ fav.service_location ?? fav.city ?? 'Localização' }}</p>
                            <div class="d-flex align-items-center gap-1 text-muted small">
                                <Star :size="16" fill="#f5b301" stroke="#f5b301" />
                                <span>{{ fav.average_rating ? Number(fav.average_rating).toFixed(1) : '5.0' }}</span>
                                <span class="text-muted">({{ fav.reviews_count ?? 0 }} avaliações)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Centro: Tags maiores entre o nome e o preço -->
                    <div class="d-flex align-items-center justify-content-center flex-wrap gap-3 mx-xl-auto px-3">
                        <span
                            v-for="animal in (fav.accepted_animal_types ?? ['cats', 'dogs'])"
                            :key="animal"
                            class="tag-pill"
                        >
                            {{ formatAnimalTag(animal) }}
                        </span>
                    </div>

                    <!-- Direita: Preço + Ver perfil + Coração -->
                    <div class="d-flex align-items-center gap-4">
                        <div class="text-end px-2">
                            <p class="fw-bold fs-5 mb-0">{{ fav.price_per_hour ?? fav.price_per_day ?? 10 }} €/hora</p>
                            <small class="text-muted">Disponível esta semana</small>
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <a
                                :href="`/petsitters/${fav.petsitter_id ?? fav.user_id ?? fav.id}`"
                                class="btn btn-primary px-4 py-2 text-white fw-semibold profile-btn"
                            >
                                Ver perfil
                            </a>
                            <button
                                type="button"
                                class="border-0 bg-transparent p-0 shadow-none"
                                @click="removeFavourite(fav.petsitter_id ?? fav.user_id ?? fav.id)"
                            >
                                <Heart :size="26" fill="#c8470f" stroke="#c8470f" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </main>
</template>

<style scoped>
.profile-avatar {
    width: 90px;
    height: 90px;
}
.tag-pill {
    background-color: #587257 !important;
    color: #ffffff !important;
    border-radius: 50rem;
    font-size: 0.9rem;
    padding: 0.35rem 2.2rem;
    min-width: 118px;
    text-align: center;
    line-height: 1.25;
}
.favorite-card {
    border-color: #e8c08f !important;
    border-radius: 1rem;
}

h1, h2, p {
    color: #3a1a0a;
}
</style>
