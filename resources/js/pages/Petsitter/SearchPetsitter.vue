<script setup lang="ts">
import { ref } from 'vue';
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

//defineOptions({ layout: null });

const petsitters = [
    {
        name: 'Sofia',
        city: 'Lisboa',
        price: 12,
        rating: '4,9 (18)',
        avatar: '👩🏻',
    },
    {
        name: 'Tiago',
        city: 'Porto',
        price: 10,
        rating: '4,8 (12)',
        avatar: '👨🏻',
    },
    { name: 'João', city: 'Braga', price: 11, rating: '5,0 (9)', avatar: '👨🏾' },
    {
        name: 'Inês',
        city: 'Coimbra',
        price: 9,
        rating: '4,7 (15)',
        avatar: '👩🏾',
    },
    {
        name: 'Maria',
        city: 'Aveiro',
        price: 13,
        rating: '4,9 (21)',
        avatar: '👩🏻',
    },
    { name: 'Rui', city: 'Faro', price: 10, rating: '4,8 (8)', avatar: '👨🏼' },
];
const favourites = ref<string[]>([]);

const toggleFavourite = (name: string) => {
    favourites.value = favourites.value.includes(name)
        ? favourites.value.filter((item) => item !== name)
        : [...favourites.value, name];
};
</script>

<template>
    <Head title="Procurar Petsitter" />

    <main class="min-vh-100 py-4 patudo-page">
        <div class="container">
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
                    <a href="/notifications" class="text-dark">
                        <Bell :size="25" />
                    </a>
                    <a href="/petsitters/profile" class="text-dark">
                        <UserRound :size="25" />
                    </a>

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

            <div class="row g-3">
                <div
                    v-for="petsitter in petsitters"
                    :key="petsitter.name"
                    class="col-12 col-md-6 col-lg-4"
                >
                    <article
                        class="card petsitter-card h-100 overflow-hidden border border-primary rounded-5"
                    >
                        <div
                            class="position-relative d-flex justify-content-center align-items-center border-bottom border-primary py-4"
                        >
                            <div class="display-2">{{ petsitter.avatar }}</div>

                            <button
                                type="button"
                                class="btn position-absolute top-0 end-0 m-3 p-0 text-primary"
                                :aria-label="`Adicionar ${petsitter.name} aos favoritos`"
                                @click="toggleFavourite(petsitter.name)"
                            >
                                <Heart
                                    :size="32"
                                    :fill="
                                        favourites.includes(petsitter.name)
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
                                <h2 class="h3 mb-1">{{ petsitter.name }}</h2>

                                <div
                                    class="d-flex align-items-center gap-1 text-dark text-nowrap"
                                >
                                    <Star
                                        :size="24"
                                        class="text-warning"
                                        fill="currentColor"
                                    />
                                    <span>{{ petsitter.rating }}</span>
                                </div>
                            </div>

                            <div
                                class="d-flex align-items-center gap-1 text-muted mb-2"
                            >
                                <MapPin :size="17" />
                                <span>{{ petsitter.city }}</span>
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
                                <p class="h3 mb-0">{{ petsitter.price }} €</p>
                            </div>

                            <a
                                href="/petsitters/profile"
                                class="btn btn-link text-dark fs-5 p-0"
                            >
                                Ver perfil
                                <ChevronRight :size="24" />
                            </a>
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
