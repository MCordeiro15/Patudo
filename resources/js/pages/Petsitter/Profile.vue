<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Bell,
    CirclePlus,
    Heart,
    MapPin,
    MessageCircle,
    PenLine,
    Pencil,
    Star,
    Trash2,
    UserRound,
} from '@lucide/vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import { computed } from 'vue';

type PetsitterPost = {
    id: number;
    description: string;
    photos: string[];
    createdAt: string | null;
};

type PetsitterProfile = {
    id: number;
    name: string;
    location: string;
    biography: string | null;
    experience: string;
    pricePerDay: number;
    acceptedSizes: string[];
    acceptedAnimalTypes: string[];
    memberSince: string | null;
    rating: number | null;
    reviewsCount: number;
    availabilityDates: string[];
    posts: PetsitterPost[];
};

const props = withDefaults(
    defineProps<{
        petsitter?: PetsitterProfile;
    }>(),
    {
        petsitter: () => ({
            id: 1,
            name: 'Nome do Pet Sitter',
            location: 'Localização',
            biography: 'Um pouco sobre mim',
            experience:
                'Breve descrição sobre si e sua experiência profissional.',
            pricePerDay: 20,
            acceptedSizes: ['small', 'medium'],
            acceptedAnimalTypes: ['dog'],
            memberSince: '2019-01-01',
            rating: null,
            reviewsCount: 0,
            availabilityDates: [],
            posts: [],
        }),
    },
);

const sizeLabelMap: Record<string, string> = {
    small: 'Porte Pequeno',
    medium: 'Porte Médio',
    large: 'Porte Grande',
};

const animalTypeLabelMap: Record<string, string> = {
    dog: 'Cão',
    cat: 'Gato',
    bird: 'Ave',
    other: 'Outro',
};

const serviceTags = computed(() => {
    const sizeTags = props.petsitter.acceptedSizes.map(
        (size) => sizeLabelMap[size] ?? size,
    );
    const animalTags = props.petsitter.acceptedAnimalTypes.map(
        (type) => animalTypeLabelMap[type] ?? type,
    );
    const tags = [...sizeTags, ...animalTags];

    return tags.length > 0 ? tags : ['Cuidador'];
});

const availabilitySet = computed(
    () => new Set(props.petsitter.availabilityDates),
);

const nextSevenDays = computed(() => {
    const labels = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    const days: Array<{ label: string; date: string }> = [];

    for (let i = 0; i < 7; i += 1) {
        const currentDate = new Date();
        currentDate.setDate(currentDate.getDate() + i);

        const date = [
            currentDate.getFullYear(),
            String(currentDate.getMonth() + 1).padStart(2, '0'),
            String(currentDate.getDate()).padStart(2, '0'),
        ].join('-');

        days.push({
            label: labels[currentDate.getDay()],
            date,
        });
    }

    return days;
});

const formatPrice = (price: number) => `${price.toFixed(0)} €`;

const formatRating = (rating: number | null) => {
    if (rating === null) {
        return 'Sem avaliações';
    }

    return rating.toString().replace('.', ',');
};

const formatDateTime = (dateTime: string | null) => {
    if (!dateTime) {
        return '--';
    }

    const date = new Date(dateTime);
    return `${date.toLocaleDateString('pt-PT')} ${date.toLocaleTimeString(
        'pt-PT',
        {
            hour: '2-digit',
            minute: '2-digit',
        },
    )}`;
};

const memberSinceLabel = computed(() => {
    if (!props.petsitter.memberSince) {
        return '--';
    }

    return new Date(props.petsitter.memberSince).toLocaleDateString('pt-PT');
});
</script>

<template>
    <Head title="Perfil do Petsitter" />

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
                        :src="logoPatudo"
                        alt="Patudo"
                        width="70"
                        height="70"
                    />
                    <span>Patudo</span>
                </a>

                <div
                    class="nav nav-pills border rounded-pill p-1 mx-auto d-none d-lg-flex"
                >
                    <a class="nav-link active rounded-pill px-4" href="/"
                        >Início</a
                    >
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

                <div class="d-flex align-items-center gap-3 ms-auto">
                    <a href="/messages" class="text-dark">
                        <MessageCircle :size="25" />
                    </a>
                    <a href="/notifications" class="text-dark">
                        <Bell :size="25" />
                    </a>
                    <a href="/profile" class="text-dark">
                        <UserRound :size="25" />
                    </a>
                </div>
            </nav>

            <div class="row justify-content-center mt-4">
                <div class="col-12 col-xl-10">
                    <h1 class="text-center fw-bold mb-4">O meu perfil</h1>

                    <section
                        class="card border border-primary rounded-4 p-3 p-md-4 mb-3"
                    >
                        <div class="row g-4 align-items-start">
                            <div class="col-12 col-md-8">
                                <div class="d-flex gap-3">
                                    <div
                                        class="d-flex align-items-center justify-content-center rounded-circle bg-light flex-shrink-0"
                                        style="width: 100px; height: 100px"
                                    >
                                        <span class="display-6">🐶</span>
                                    </div>

                                    <div class="w-100">
                                        <h2 class="h5 mb-0">
                                            {{ petsitter.name }}
                                        </h2>
                                        <p class="mb-2 text-muted small">
                                            {{ petsitter.location }}
                                        </p>

                                        <div
                                            class="d-flex flex-wrap gap-2 mb-2"
                                        >
                                            <span
                                                v-for="tag in serviceTags"
                                                :key="tag"
                                                class="badge rounded-pill text-bg-success px-3 py-2"
                                            >
                                                {{ tag }}
                                            </span>
                                        </div>

                                        <p class="mb-0 text-muted small">
                                            {{ petsitter.experience }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="d-flex justify-content-end mb-2">
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary btn-sm rounded-pill"
                                    >
                                        <Pencil :size="14" class="me-1" />
                                        Editar
                                    </button>
                                </div>

                                <div
                                    class="border-start ps-3 d-grid gap-1 text-muted small"
                                >
                                    <div>
                                        Rating
                                        <strong class="text-dark ms-1">
                                            {{ formatRating(petsitter.rating) }}
                                        </strong>
                                    </div>
                                    <div>
                                        Avaliações {{ petsitter.reviewsCount }}
                                    </div>
                                    <div>
                                        Membro desde {{ memberSinceLabel }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section
                        class="card border border-primary rounded-4 p-3 p-md-4 mb-3"
                    >
                        <h3 class="h5">Sobre mim</h3>
                        <p class="mb-0 text-muted">
                            {{
                                petsitter.biography ??
                                'Este petsitter ainda não adicionou uma biografia.'
                            }}
                        </p>
                    </section>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-lg-5">
                            <section
                                class="card border border-primary rounded-4 p-3 p-md-4 h-100"
                            >
                                <h3 class="h5 mb-3">Serviços e preços</h3>
                                <div
                                    class="d-flex align-items-center justify-content-between"
                                >
                                    <span>Dog Sitting (diário)</span>
                                    <strong>{{
                                        formatPrice(petsitter.pricePerDay)
                                    }}</strong>
                                </div>
                            </section>
                        </div>

                        <div class="col-12 col-lg-7">
                            <section
                                class="card border border-primary rounded-4 p-3 p-md-4 h-100"
                            >
                                <h3 class="h5 mb-3">Disponibilidade</h3>
                                <div class="d-flex flex-wrap gap-2">
                                    <div
                                        v-for="day in nextSevenDays"
                                        :key="day.date"
                                        class="text-center text-white rounded-3 px-2 py-2"
                                        :class="
                                            availabilitySet.has(day.date)
                                                ? 'bg-success'
                                                : 'bg-danger'
                                        "
                                        style="min-width: 44px"
                                    >
                                        <small class="d-block">{{
                                            day.label
                                        }}</small>
                                        <strong>{{
                                            availabilitySet.has(day.date)
                                                ? '✓'
                                                : '✕'
                                        }}</strong>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>

                    <section>
                        <h2 class="text-center fw-bold mb-3">Publicações</h2>
                        <div class="d-grid gap-3">
                            <article
                                v-for="post in petsitter.posts"
                                :key="post.id"
                                class="card border border-primary rounded-4 p-3"
                            >
                                <div class="row g-3 align-items-stretch">
                                    <div class="col-12 col-md-3">
                                        <img
                                            :src="
                                                post.photos[0] ??
                                                'https://images.unsplash.com/photo-1583512603877-2005f1f46c74?q=80&w=900&auto=format&fit=crop'
                                            "
                                            alt="Imagem da publicação"
                                            class="img-fluid rounded-4 h-100 object-fit-cover"
                                        />
                                    </div>

                                    <div class="col-12 col-md-8">
                                        <h3 class="h4 mb-2">Título do post</h3>
                                        <p class="text-muted mb-3">
                                            {{ post.description }}
                                        </p>
                                        <small class="text-muted">{{
                                            formatDateTime(post.createdAt)
                                        }}</small>
                                    </div>

                                    <div
                                        class="col-12 col-md-1 d-flex justify-content-md-end gap-2"
                                    >
                                        <button
                                            type="button"
                                            class="btn btn-link text-dark p-0"
                                        >
                                            <PenLine :size="18" />
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-link text-dark p-0"
                                        >
                                            <Trash2 :size="18" />
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-link text-primary p-0"
                                        >
                                            <Heart :size="24" />
                                        </button>
                                    </div>
                                </div>
                            </article>

                            <article
                                class="card border border-primary rounded-4 p-4 text-center d-flex align-items-center justify-content-center"
                                style="min-height: 170px"
                            >
                                <CirclePlus
                                    :size="56"
                                    class="text-secondary mb-2"
                                />
                                <p class="mb-0">Adicionar nova publicação</p>
                            </article>
                        </div>
                    </section>

                    <div class="d-flex justify-content-end mt-3">
                        <a
                            href="/petsitters"
                            class="btn btn-outline-primary rounded-pill px-4"
                        >
                            Voltar à pesquisa
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</template>
