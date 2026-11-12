<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CirclePlus, LogOut, UserRound, MessageCircle, Bell ,SquarePen } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import type { Animal } from '@/types';
import { useAuth } from '@/composables/useAuth';
import AnimalCard from '@/components/AnimalCard.vue';
import ModalAddPet from '@/pages/Modals/ModalAddPet.vue';
import ModalDeleteAnimal from '@/pages/Modals/ModalDeleteAnimal.vue';

const { user, logout } = useAuth();

const animals = ref<Animal[]>([]);
const isLoading = ref(false);
const loadError = ref('');
const showAddModal = ref(false);
const showDeleteModal = ref(false);
const deletingAnimal = ref<{ id: number; name: string } | null>(null);

const displayName = computed(() => user.value?.name ?? 'Utilizador');

const fetchAnimals = async () => {
    isLoading.value = true;
    loadError.value = '';

    try {
        await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin',
        });

        const response = await fetch('/api/animals', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            const data = await response.json();
            animals.value = data.data ?? [];
        } else {
            loadError.value = 'Erro ao carregar os animais.';
        }
    } catch {
        loadError.value = 'Erro de conexão. Tente novamente.';
    } finally {
        isLoading.value = false;
    }
};

const handleAnimalSaved = () => {
    // Refresh the list after a new animal is saved
    void fetchAnimals();
};

const openDeleteModal = (animalId: number) => {
    const animal = animals.value.find((a) => a.id === animalId);
    if (animal) {
        deletingAnimal.value = { id: animalId, name: animal.name };
        showDeleteModal.value = true;
    }
};

const confirmDeleteAnimal = async () => {
    if (!deletingAnimal.value) return;

    try {
        await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin',
        });

        const token = document.cookie
            .split('xsrf_token=')
            .pop()
            .split(';')
            .shift();
        const xsrfToken = token ? decodeURIComponent(token) : '';

        const response = await fetch(`/api/animals/${deletingAnimal.value.id}`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken,
            },
        });

        if (response.ok) {
            animals.value = animals.value.filter((a) => a.id !== deletingAnimal.value!.id);
            showDeleteModal.value = false;
            deletingAnimal.value = null;
        }
    } catch {
        // Erro de conexão
    }
};

onMounted(() => {
    void fetchAnimals();
});
</script>

<template>
    <Head title="O meu perfil" />

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
                    <button
                        type="button"
                        class="text-dark p-0 border-0 bg-none"
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

            <h1 class="text-center fw-bold display-5 mt-5 pt-4 mb-4">O meu perfil</h1>

            <section
                class="card bg-white border border-primary rounded-4 p-3 p-md-4 mb-3 position-relative"
            >
                <button
                    type="button"
                    class="btn btn-primary btn-sm d-flex align-items-center gap-2 position-absolute top-0 end-0 mt-3 me-3 px-4"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                        <path d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z" />
                    </svg>
                    Editar
                </button>

                <div class="row g-4 align-items-center">
                    <div class="col-12 col-md-8">
                        <div class="d-flex align-items-center gap-4">
                            <div
                                class="profile-avatar d-flex align-items-center justify-content-center rounded-circle border border-primary flex-shrink-0"
                            >
                                <UserRound :size="64" class="text-primary" />
                            </div>

                            <div>
                                <h2 class="h5 fw-bold mb-0">{{ displayName }}</h2>
                                <p class="text-muted small mb-2">
                                    {{ user?.location ?? 'Localização' }}
                                </p>

                                <div class="d-flex flex-wrap gap-3 mb-2">
                                    <span
                                        v-for="species in [...new Set(animals.map((a) => a.species))]"
                                        :key="species"
                                        class="tag-pill rounded-pill py-1 small"
                                    >
                                        {{ species }}
                                    </span>
                                </div>

                                <p class="text-muted small mb-0">
                                    {{ user?.bio ?? 'Breve descrição sobre o que procura, sobre o seu animal.' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="border-start border-secondary ps-4 py-2">
                            <p class="fs-5 text-muted mb-1">
                                <span class="star">★</span> 5,0
                            </p>
                            <p class="text-muted mb-2">Avaliações</p>
                            <p class="text-muted mb-0">
                                Membro desde:<br />
                                {{
                                    user?.created_at
                                        ? new Date(user.created_at).toLocaleDateString('pt-PT')
                                        : '—'
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="card bg-white border border-primary rounded-4 p-3 px-md-4 mb-5 about-card"
            >
                <h2 class="h5 fw-semibold mb-3">Sobre mim</h2>
                <p class="text-muted fs-5 mb-0 ps-md-2">
                    {{ user?.about ?? 'Um pouco sobre mim' }}
                </p>
            </section>

            <h1 class="text-center fw-bold display-5 mb-4">Meus animais</h1>

            <section
                class="card bg-white border border-primary rounded-4 p-3 p-md-4 mb-4"
            >
                <h2 class="h6 fw-semibold mb-3">Os meus animais</h2>

                <div v-if="isLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">A carregar...</span>
                    </div>
                </div>

                <div
                    v-else-if="loadError"
                    class="alert alert-danger py-2 small"
                    role="alert"
                >
                    {{ loadError }}
                </div>

                <div v-else class="row g-4">
                    <div
                        v-for="animal in animals"
                        :key="animal.id"
                        class="col-12 col-md-6 col-lg-4"
                    >
                        <AnimalCard :animal="animal" @delete="openDeleteModal" />
                    </div>

                    <div class="col-12 col-md-6 col-lg-4">
                        <button
                            type="button"
                            class="add-animal-card w-100 h-100 d-flex flex-column align-items-center justify-content-center gap-2 border border-primary rounded-4 bg-white"
                            @click="showAddModal = true"
                        >
                            <CirclePlus :size="56" :stroke-width="1" class="text-primary" />
                            <span class="small text-muted">Adicionar animal</span>
                        </button>
                    </div>
                </div>
            </section>
        </div>

        <ModalAddPet
            :show="showAddModal"
            @close="showAddModal = false"
            @saved="handleAnimalSaved"
        />

        <ModalDeleteAnimal
            :show="showDeleteModal"
            :animal-name="deletingAnimal?.name ?? ''"
            @close="showDeleteModal = false; deletingAnimal = null"
            @confirmed="confirmDeleteAnimal"
        />
    </main>
</template>

<style scoped>
.patudo-page {
    background-color: #fffaf3;
}

.profile-avatar {
    width: 140px;
    height: 140px;
}

.tag-pill {
    background-color: #587257;
    color: #fff;
    min-width: 120px;
    text-align: center;
}

.about-card {
    min-height: 210px;
}

.add-animal-card {
    min-height: 200px;
    cursor: pointer;
}

.add-animal-card:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.05) !important;
}

.star {
    color: #f5b301;
}
</style>
