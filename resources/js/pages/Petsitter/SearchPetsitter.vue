<!-- Página Vue: SearchPetsitter.vue -->
<!-- Vue page: SearchPetsitter.vue -->
<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    Bell,
    ChevronRight,
    Heart,
    LogOut,
    MapPin,
    MessageCircle,
    SlidersHorizontal,
    Star,
    UserRound,
} from '@lucide/vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import ModalNotifications from '@/pages/Modals/ModalNotifications.vue';

//defineOptions({ layout: null });

interface PetsitterApiData {
    user_id: number;
    avatar_url?: string | null;
    service_location: string;
    price_per_day: number;
    average_rating: number | string | null;
    reviews_count: number;
    user: {
        id: number;
        name: string;
    };
}

const petsitters = ref<PetsitterApiData[]>([]);
const favourites = ref<number[]>([]);
const error = ref<string | null>(null);
const loading = ref(true);
const showNotifications = ref(false);

function isFavourite(petsitterId: number): boolean {
    return favourites.value.includes(petsitterId);
}


// Generate a fake rating for display
function formatRating(p: PetsitterApiData): string {
    const avg = Number(p.average_rating ?? 0)
        .toFixed(1)
        .replace('.', ',');
    return `${avg} (${p.reviews_count ?? 0})`;
}

async function fetchPetsitters() {
    try {
        await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin',
        });

        const response = await fetch('/api/petsitters', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Não foi possível carregar os petsitters.');
        }

        const data = await response.json();
        const apiPetsitters: PetsitterApiData[] = data.data ?? data;
        petsitters.value = apiPetsitters.filter((p) => p.user && p.user.name);
    } catch (e) {
        error.value =
            e instanceof Error
                ? e.message
                : 'Ocorreu um erro ao carregar os petsitters.';
    } finally {
        loading.value = false;
    }
}

function navigateToProfile(userId: number) {
    window.location.href = `/petsitters/${userId}`;
}

function navigateToMyProfile() {
    window.location.href = '/petsitters/profile';
}

function goToLogin() {
    window.location.href = '/login';
}

async function fetchFavourites() {
    try {
        await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin',
        });

        const response = await fetch('/api/favourites', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            return;
        }

        const data = await response.json();
        const apiPetsitters: PetsitterApiData[] = data.data ?? data;
        favourites.value = apiPetsitters.map(
            (p: PetsitterApiData) => p.user_id,
        );
    } catch {
        // Silently fail on load — favourites will just be empty
    }
}

function getCsrfToken(): string | null {
    const name = 'XSRF-TOKEN';
    const cookies = document.cookie.split(';');
    for (const cookie of cookies) {
        const [cookieName, ...cookieValueParts] = cookie.trim().split('=');
        if (cookieName === name) {
            return decodeURIComponent(cookieValueParts.join('='));
        }
    }
    return null;
}

async function toggleFavourite(petsitterId: number) {
    const isFavourited = favourites.value.includes(petsitterId);

    // Optimistic update
    const previousState = favourites.value;
    if (isFavourited) {
        favourites.value = favourites.value.filter((id) => id !== petsitterId);
    } else {
        favourites.value = [...favourites.value, petsitterId];
    }

    try {
        // Get CSRF cookie first
        await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin',
        });

        const csrfToken = getCsrfToken();

        if (isFavourited) {
            // Remove favourite
            const response = await fetch(`/api/favourites/${petsitterId}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': csrfToken || '',
                },
            });

            if (!response.ok) {
                throw new Error('Não foi possível remover dos favoritos.');
            }
        } else {
            // Add favourite
            const response = await fetch('/api/favourites', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': csrfToken || '',
                },
                body: JSON.stringify({ petsitter_id: petsitterId }),
            });

            if (!response.ok) {
                // If 401, redirect to login
                if (response.status === 401) {
                    goToLogin();
                    return;
                }
                throw new Error('Não foi possível adicionar aos favoritos.');
            }
        }
    } catch (e) {
        // Revert on error
        favourites.value = previousState;
        error.value = e instanceof Error ? e.message : 'Ocorreu um erro.';
    }
}

onMounted(() => {
    fetchPetsitters();
    fetchFavourites();
});
</script>

<template>
    <Head title="Procurar Petsitter" />

    <!-- Conteúdo principal da página. -->
    <!-- Main content area of the page. -->
    <main class="min-vh-100 py-4 patudo-page">
        <div class="container">
            <!-- Barra de navegação com links principais. -->
            <!-- Navigation bar with main links. -->
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
                    <a class="nav-link active rounded-pill px-4" href="/">
                        Início
                    </a>
                    <a
                        class="nav-link text-dark rounded-pill px-4"
                        href="/orders"
                    >
                        Pedidos
                    </a>
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
                    <a href="/messages" class="text-dark">
                        <MessageCircle :size="25" />
                    </a>
                    <button type="button" class="text-dark bg-transparent border-0 p-0" @click="showNotifications = true">
                        <Bell :size="25" />
                    </button>
                    <button
                        type="button"
                        class="text-dark p-0 border-0 bg-transparent"
                        @click="navigateToMyProfile()"
                    >
                        <UserRound :size="25" />
                    </button>

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="btn btn-outline-danger btn-sm rounded-pill d-flex align-items-center gap-2 px-3 py-1 ms-1"
                    >
                        <LogOut :size="16" />
                        <span>Sair</span>
                    </Link>
                </div>
            </nav>

            <div
                class="d-flex align-items-center justify-content-between gap-3 m-5"
            >
                <h1 class="h2 mb-0 text-nowrap">
                    Encontra o cuidador ideal para o teu animal
                </h1>

                <button
                    class="btn btn-outline-secondary rounded-pill text-nowrap"
                >
                    <SlidersHorizontal :size="18" class="me-2" />
                    Filtros (X)
                </button>
            </div>

            <!-- Error message -->
            <div
                v-if="error"
                class="alert alert-danger alert-dismissible fade show mb-4"
                role="alert"
            >
                {{ error }}
                <button
                    type="button"
                    class="btn-close"
                    @click="error = null"
                ></button>
            </div>

            <!-- Loading state -->
            <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">A carregar...</span>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else-if="petsitters.length === 0" class="text-center py-5">
                <p class="text-muted">
                    Nenhum petsitter disponível no momento.
                </p>
            </div>

            <!-- Petsitters list -->
            <div v-else class="row g-3">
                <div
                    v-for="petsitter in petsitters"
                    :key="petsitter.user_id"
                    class="col-12 col-md-6 col-lg-4"
                >
                    <article
                        class="card petsitter-card h-100 overflow-hidden border border-primary rounded-5"
                    >
                        <div
                            class="position-relative d-flex justify-content-center align-items-center border-bottom border-primary py-4"
                        >
                            <img
                                v-if="petsitter.avatar_url"
                                :src="petsitter.avatar_url"
                                :alt="petsitter.user.name"
                                style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%"
                            />
                            <div v-else class="display-2">🐾</div>

                            <button
                                type="button"
                                class="btn position-absolute top-0 end-0 m-3 p-0 text-primary"
                                :aria-label="`Adicionar ${petsitter.user.name} aos favoritos`"
                                @click="toggleFavourite(petsitter.user_id)"
                            >
                                <Heart
                                    :size="32"
                                    :fill="
                                        isFavourite(petsitter.user_id)
                                            ? 'currentColor'
                                            : 'transparent'
                                    "
                                    stroke-width="2.5"
                                />
                            </button>
                        </div>

                        <div class="p-4 border-bottom border-primary">
                            <div
                                class="d-flex justify-content-between align-items-start gap-2"
                            >
                                <h2 class="h3 mb-1">
                                    {{ petsitter.user.name }}
                                </h2>

                                <div
                                    class="d-flex align-items-center gap-1 text-dark text-nowrap"
                                >
                                    <Star
                                        :size="24"
                                        class="text-warning"
                                        fill="currentColor"
                                    />
                                    <span>{{ formatRating(petsitter) }}</span>
                                </div>
                            </div>

                            <div
                                class="d-flex align-items-center gap-1 text-muted mb-2"
                            >
                                <MapPin :size="17" />
                                <span>{{ petsitter.service_location }}</span>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <span
                                    class="badge rounded-pill text-bg-success px-3 py-2"
                                >
                                    Passeios
                                </span>
                                <span
                                    class="badge rounded-pill text-bg-success px-3 py-2"
                                >
                                    Hospedagem
                                </span>
                            </div>
                        </div>

                        <div
                            class="d-flex justify-content-between align-items-end p-4"
                        >
                            <div>
                                <p class="mb-0 small">A partir de</p>
                                <p class="h3 mb-0">
                                    {{ petsitter.price_per_day }} €
                                </p>
                            </div>

                            <button
                                type="button"
                                class="btn btn-link text-dark fs-5 p-0 cursor-pointer"
                                @click="navigateToProfile(petsitter.user_id)"
                            >
                                Ver perfil
                                <ChevronRight :size="24" />
                            </button>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </main>

    <ModalNotifications
        :show="showNotifications"
        @close="showNotifications = false"
    />
</template>

<style scoped>
.nav-pills .nav-link.active {
    background-color: var(--patudo-terracota, #c2410c) !important;
    color: #ffffff !important;
}

.nav-pills .nav-link:not(.active):hover {
    color: var(--patudo-terracota, #c2410c) !important;
}
</style>
