<!-- Página Vue: Favorites.vue -->
<!-- Vue page: Favorites.vue -->
<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
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

interface PetsitterData {
    user_id: number;
    biography: string | null;
    experience: string;
    service_location: string;
    age: number;
    price_per_day: number;
    accepted_sizes: string[] | null;
    accepted_animal_types: string[] | null;
    sex: string | null;
    approval_status: string;
    availability_days: string[] | null;
    user: {
        id: number;
        name: string;
    };
}

const favouritePetsitters = ref<PetsitterData[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

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
    loading.value = true;
    error.value = null;
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
            throw new Error('Não foi possível carregar os favoritos.');
        }

        const data = await response.json();
        favouritePetsitters.value = data.data ?? data;
    } catch (e) {
        error.value =
            e instanceof Error ? e.message : 'Erro ao carregar favoritos.';
    } finally {
        loading.value = false;
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
    const previousState = favouritePetsitters.value;
    const index = favouritePetsitters.value.findIndex(
        (p) => p.user_id === petsitterId,
    );

    if (index === -1) {
        return;
    }

    // Optimistically remove from the list
    favouritePetsitters.value = favouritePetsitters.value.filter(
        (p) => p.user_id !== petsitterId,
    );

    try {
        // Get CSRF cookie first
        await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin',
        });

        const csrfToken = getCsrfToken();

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
    } catch (e) {
        // Revert on error
        favouritePetsitters.value = previousState;
        error.value =
            e instanceof Error ? e.message : 'Erro ao remover dos favoritos.';
    }
}

onMounted(() => {
    fetchFavourites();
});
</script>

<template>
    <Head title="Favoritos" />

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
                        src="/images/patudo-logo.svg"
                        alt="Patudo"
                        width="190"
                        height="62"
                    />
                </a>

                <div class="nav nav-pills border rounded-pill p-1 mx-auto">
                    <a
                        class="nav-link text-dark rounded-pill px-4"
                        href="/petsitters"
                    >
                        Início
                    </a>
                    <a
                        class="nav-link text-dark rounded-pill px-4"
                        href="/orders"
                    >
                        Pedidos
                    </a>
                    <a
                        class="nav-link active rounded-pill px-4"
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
                    <a href="/notifications" class="text-dark">
                        <Bell :size="25" />
                    </a>
                    <button
                        type="button"
                        class="text-dark p-0 border-0 bg-none"
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

            <div class="m-5">
                <h1 class="h2 mb-0">Os teus petsitters favoritos</h1>
            </div>

            <!-- Error message -->
            <div
                v-if="error"
                class="alert alert-danger alert-dismissible fade show m-5"
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
            <div v-if="loading" class="text-center my-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">A carregar...</span>
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-else-if="favouritePetsitters.length === 0"
                class="text-center my-5"
            >
                <p class="text-muted">Ainda não tens petsitters favoritos.</p>
                <a href="/petsitters" class="btn btn-primary rounded-pill">
                    Procurar petsitters
                </a>
            </div>

            <!-- Favourites list -->
            <div v-else class="row g-3">
                <div
                    v-for="petsitter in favouritePetsitters"
                    :key="petsitter.user_id"
                    class="col-12 col-md-6 col-lg-4"
                >
                    <article
                        class="card petsitter-card h-100 overflow-hidden border border-primary rounded-5"
                    >
                        <div
                            class="position-relative d-flex justify-content-center align-items-center border-bottom border-primary py-4"
                        >
                            <div class="display-2">🐾</div>

                            <button
                                type="button"
                                class="btn position-absolute top-0 end-0 m-3 p-0 text-primary"
                                :aria-label="`Remover ${petsitter.user.name} dos favoritos`"
                                @click="toggleFavourite(petsitter.user_id)"
                            >
                                <Heart
                                    :size="32"
                                    fill="currentColor"
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
                            </div>

                            <div
                                class="d-flex align-items-center gap-1 text-muted mb-2"
                            >
                                <MapPin :size="17" />
                                <span>{{ petsitter.service_location }}</span>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <span
                                    v-for="type in petsitter.accepted_animal_types"
                                    :key="type"
                                    class="badge rounded-pill text-bg-success px-3 py-2"
                                >
                                    {{ type }}
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
