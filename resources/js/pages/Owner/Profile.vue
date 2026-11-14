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
import ModalEditOwnerProfile from '@/pages/Modals/ModalEditOwnerProfile.vue';

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

// Função para formatar o número de telemóvel com prefixo +351 e separação 3 dígitos.
// Function to format phone number with +351 prefix and 3-digit separation.
function formatPhone(phone: string | null | undefined): string | null {
    if (!phone) return null;
    // Remove o prefixo +351 se já existir e extrai apenas os 9 dígitos
    const digits = phone.replace(/\D/g, '').replace(/^351/, '').slice(-9);
    const formatted = digits.replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
    return `+351 ${formatted}`;
}

const showEditModal = ref(false);
const profileError = ref('');

// Dados que o modal precisa, já no formato que ele espera
const editData = computed(() => ({
    name: user.value?.name ?? '',
    location: user.value?.location ?? '',
    phone: user.value?.phone ?? '',
    about: user.value?.about ?? '',
    avatar_url: user.value?.avatar_url ?? null,
}));

function getXsrf(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

const handleSaveProfile = async (formData: FormData) => {
    if (!user.value) return;
    profileError.value = '';

    // O Laravel só lê ficheiros em POST, por isso simulamos o PUT
    formData.append('_method', 'PUT');

    try {
        const response = await fetch(`/api/users/${user.value.id}`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getXsrf(),
                // sem Content-Type: o browser define o multipart sozinho
            },
            body: formData,
        });

        if (response.ok) {
            const data = await response.json();
            user.value = data.data ?? data; // atualiza o estado global
        } else {
            const err = await response.json().catch(() => ({}));
            profileError.value = err.errors
                ? Object.values(err.errors).flat().join(' ')
                : (err.message ?? 'Erro ao guardar o perfil.');
        }
    } catch {
        profileError.value = 'Erro de conexão. Tente novamente.';
    }
};

const handleDeleteAccount = async () => {
    if (!user.value) return;
    if (!window.confirm('Tens a certeza que queres eliminar a tua conta?')) return;

    try {
        const response = await fetch(`/api/users/${user.value.id}`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getXsrf(),
            },
        });
        if (response.ok) {
            window.location.href = '/login';
        } else {
            profileError.value = 'Não foi possível eliminar a conta.';
        }
    } catch {
        profileError.value = 'Erro de conexão. Tente novamente.';
    }
};

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
                                        class="d-flex align-items-center justify-content-center rounded-circle bg-light flex-shrink-0 overflow-hidden"
                                        style="width: 80px; height: 80px"
                                    >
                                        <img
                                            v-if="user?.avatar_url"
                                            :src="user.avatar_url"
                                            alt="Foto de perfil"
                                            style="width: 80px; height: 80px; object-fit: cover"
                                        />
                                        <UserRound
                                            v-else
                                            :size="40"
                                            class="text-primary"
                                        />
                                    </div>

                                    <div>
                                        <h2 class="h5 mb-1">
                                            {{ displayName }}
                                        </h2>
                                        <p class="mb-0 text-muted small">
                                            {{ user?.email }}
                                        </p>
                                        <p
                                            v-if="user?.phone"
                                            class="mb-0 text-muted small"
                                        >
                                            {{ formatPhone(user.phone) }}
                                        </p>
                                        <p v-if="user?.location" class="mb-0 text-muted small">
                                            {{ user.location }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 text-md-end">
                                <button
                                    type="button"
                                    class="btn btn-outline-primary rounded-pill"
                                    @click="showEditModal = true"
                                >
                                    Editar perfil
                                </button>
                            </div>
                            <div v-if="user?.about" class="col-12">
                                <p class="mb-0 small" style="white-space: pre-line">
                                    {{ user.about }}
                                </p>
                            </div>
                            <div v-if="profileError" class="alert alert-danger py-2 small" role="alert">
                                {{ profileError }}
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

        <ModalEditOwnerProfile
            :show="showEditModal"
            :user="editData"
            @close="showEditModal = false"
            @save="handleSaveProfile"
            @delete-account="handleDeleteAccount"
        />
    </main>
</template>
