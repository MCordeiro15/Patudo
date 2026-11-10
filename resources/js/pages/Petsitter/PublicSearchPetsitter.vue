<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Bell,
    ChevronRight,
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
</script>
<template>
    <Head title="Procurar Petsitter" />

    <main class="min-vh-100 py-4 patudo-page">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <a
                    class="navbar-brand fw-bold fs-3 d-flex align-items-center gap-2 mb-0"
                    href="/"
                >
                    <img
                        src="/images/patudo-logo.svg"
                        alt="Patudo"
                        width="190"
                        height="62"
                    />
                </a>
                <div class="d-flex gap-2 col-6 col-md-4 col-lg-3">
                    <a href="/login" class="btn btn-primary rounded-pill w-50 py-2 fw-bold">
                        Login
                    </a>

                    <a
                        href="/register"
                        class="btn btn-outline-primary rounded-pill w-50 py-2 fw-bold"
                    >
                        Registar
                    </a>
                </div>
            </div>
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
                        <!-- Верх: аватар и избранное -->
                        <div
                            class="position-relative d-flex justify-content-center align-items-center border-bottom border-primary py-4"
                        >
                            <div class="display-2">{{ petsitter.avatar }}</div>
                        </div>

                        <!-- Середина: имя, рейтинг, локация и теги -->
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

                        <!-- Низ: цена и переход в профиль -->
                        <div
                            class="d-flex justify-content-between align-items-end p-4"
                        >
                            <div>
                                <p class="mb-0 small">A partir de</p>
                                <p class="h3 mb-0">{{ petsitter.price }} €</p>
                            </div>

                            <button
                                type="button"
                                class="btn btn-link text-dark fs-5 p-0"
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
