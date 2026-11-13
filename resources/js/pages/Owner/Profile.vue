<!-- Página de perfil do utilizador. Mostra e permite editar informações pessoais. -->
<!-- User profile page. Shows and allows editing personal information. -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CirclePlus, LogOut, UserRound } from '@lucide/vue';
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
const deletingAnimalLoading = ref(false);

// Propriedade computada que retorna o nome de exibição do utilizador.
// Computed property that returns the user's display name.
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

    deletingAnimalLoading.value = true;
    try {
        const cookiePart = document.cookie.split('xsrf_token=').pop();
        const token = cookiePart ? cookiePart.split(';').shift() : '';
        const xsrfToken = token ? decodeURIComponent(token) : '';

        const response = await fetch(
            `/api/animals/${deletingAnimal.value.id}`,
            {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': xsrfToken,
                },
            },
        );

        if (response.ok) {
            animals.value = animals.value.filter(
                (a) => a.id !== deletingAnimal.value!.id,
            );
            showDeleteModal.value = false;
            deletingAnimal.value = null;
        }
    } catch {
        // Erro de conexão
    } finally {
        deletingAnimalLoading.value = false;
    }
};

onMounted(() => {
    void fetchAnimals();
});
</script>

<template>
    <Head title="O meu perfil" />

    <!-- Conteúdo principal da página. -->
    <!-- Main content area of the page. -->
    <main class="min-vh-100 py-4 patudo-page">
        <div class="container">
            <!-- Navbar -->
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
                    <a class="nav-link text-dark rounded-pill px-4" href="/"
                        >Início</a
                    >
                    <a
                        class="nav-link text-dark rounded-pill px-4"
                        href="/petsitters"
                    >
                        Petsitters
                    </a>
                    <a
                        class="nav-link text-dark rounded-pill px-4"
                        href="/favorites"
                    >
                        Favoritos
                    </a>
                    <a
                        class="nav-link active rounded-pill px-4"
                        aria-current="page"
                        href="/petsitters/profile"
                    >
                        Perfil
                    </a>
                </div>

                <div class="d-flex align-items-center gap-3 ms-auto">
                    <Link
                        href="/login"
                        class="text-dark"
                        @click.prevent="logout"
                    >
                        <LogOut :size="25" />
                    </Link>
                    <Link href="/petsitters/profile" class="text-dark">
                        <UserRound :size="25" />
                    </Link>
                </div>
            </nav>

            <!-- Page title -->
            <div class="row justify-content-center mt-5 pt-4">
                <div class="col-12">
                    <h1 class="text-center fw-bold mb-4 pb-2">O meu perfil</h1>

                    <!-- User info card -->
                    <section
                        class="card border border-primary rounded-4 p-3 p-md-4 mb-4"
                    >
                        <div class="row g-4 align-items-center">
                            <div class="col-12 col-md-8">
                                <div class="d-flex gap-3">
                                    <div
                                        class="d-flex align-items-center justify-content-center rounded-circle bg-light flex-shrink-0"
                                        style="width: 80px; height: 80px"
                                    >
                                        <UserRound
                                            :size="40"
                                            class="text-primary"
                                        />
                                    </div>

                                    <div>
                                        <h2 class="h5 mb-1">
                                            {{ displayName }}
                                        </h2>
                                        <p class="mb-1 text-muted small">
                                            {{ user?.email }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Animals section -->
                    <section class="mb-4">
                        <div
                            class="d-flex align-items-center justify-content-between mb-3"
                        >
                            <h2 class="h4 mb-0">Os meus animais</h2>
                            <button
                                type="button"
                                class="btn btn-primary rounded-pill d-flex align-items-center gap-2"
                                @click="showAddModal = true"
                            >
                                <CirclePlus :size="18" />
                                Adicionar animal
                            </button>
                        </div>

                        <!-- Loading state -->
                        <div v-if="isLoading" class="text-center py-5">
                            <div
                                class="spinner-border text-primary"
                                role="status"
                            >
                                <span class="visually-hidden"
                                    >A carregar...</span
                                >
                            </div>
                        </div>

                        <!-- Error state -->
                        <div
                            v-else-if="loadError"
                            class="alert alert-danger py-2 small"
                            role="alert"
                        >
                            {{ loadError }}
                        </div>

                        <!-- Empty state -->
                        <div
                            v-else-if="animals.length === 0"
                            class="card border border-warning rounded-4 p-4 text-center"
                        >
                            <p class="mb-0 text-muted">
                                Ainda não adicionaste nenhum animal. Clica em
                                <strong>"Adicionar animal"</strong> para
                                começar.
                            </p>
                        </div>

                        <!-- Animals grid -->
                        <div v-else class="row g-3">
                            <div
                                v-for="animal in animals"
                                :key="animal.id"
                                class="col-12 col-md-6 col-lg-4"
                            >
                                <AnimalCard
                                    :animal="animal"
                                    @delete="openDeleteModal"
                                />
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <!-- Add Pet Modal -->
        <ModalAddPet
            :show="showAddModal"
            @close="showAddModal = false"
            @saved="handleAnimalSaved"
        />

        <!-- Delete Animal Modal -->
        <ModalDeleteAnimal
            :show="showDeleteModal"
            :animal-name="deletingAnimal?.name ?? ''"
            :deleting="deletingAnimalLoading"
            @close="
                showDeleteModal = false;
                deletingAnimal = null;
            "
            @confirmed="confirmDeleteAnimal"
        />
    </main>
</template>
