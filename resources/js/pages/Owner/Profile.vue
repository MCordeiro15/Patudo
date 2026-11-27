<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CirclePlus, LogOut, UserRound, SquarePen } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import AdminNavigationMenu from '@/components/AdminNavigationMenu.vue';
import HeaderNotificationsChat from '@/components/HeaderNotificationsChat.vue';
import type { Animal } from '@/types';
import { useAuth } from '@/composables/useAuth';
import AnimalCard from '@/components/AnimalCard.vue';
import ModalAddPet from '@/pages/Modals/ModalAddPet.vue';
import ModalDeleteAnimal from '@/pages/Modals/ModalDeleteAnimal.vue';
import ModalEditOwnerProfile from '@/pages/Modals/ModalEditOwnerProfile.vue';
import ModalFormPetsitter from '@/pages/Modals/ModalFormPetsitter.vue';

const { user, logout } = useAuth();

const animals = ref<Animal[]>([]);
const isLoading = ref(false);
const loadError = ref('');
const showAddModal = ref(false);
const showEditAnimalModal = ref(false);
const showDeleteModal = ref(false);

// "Quero ser petsitter" — botão placeholder: no futuro passa para a navbar
// O estado vem do servidor (rota /profile): candidatura, perfil desativado ou sem candidatura.
const props = defineProps<{
    petsitterApplicationStatus?: 'pending' | 'rejected' | 'inactive' | null;
}>();

const showFormPetsitter = ref(false);
const petsitterApplicationStatus = ref(props.petsitterApplicationStatus ?? null);

const handlePetsitterApplicationSaved = () => {
    petsitterApplicationStatus.value = 'pending';
};
const deletingAnimal = ref<{ id: number; name: string } | null>(null);
const deletingAnimalLoading = ref(false);
const editingAnimal = ref<Animal | null>(null);

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

const handleAnimalSaved = (animal?: Animal) => {
    if (!animal) {
        void fetchAnimals();
        return;
    }

    const index = animals.value.findIndex((item) => item.id === animal.id);
    if (index === -1) {
        animals.value.push(animal);
    } else {
        animals.value[index] = animal;
    }
};

const openEditAnimalModal = (animalId: number) => {
    editingAnimal.value = animals.value.find((animal) => animal.id === animalId) ?? null;
    if (editingAnimal.value) showEditAnimalModal.value = true;
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
    <main class="min-vh-100 py-4 patudo-page patudo-page-shell">
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
                    <AdminNavigationMenu />
                </div>

                <div class="d-flex align-items-center gap-3">
                    <HeaderNotificationsChat />
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

            <h1 class="text-center fw-bold display-5 mt-5 pt-4 mb-4">O meu perfil</h1>

            <div
                v-if="profileError"
                class="alert alert-danger py-2 small"
                role="alert"
            >
                {{ profileError }}
            </div>

            <!-- PLACEHOLDER: o botão "Quero ser petsitter" vai passar para a navbar -->
            <div class="d-flex justify-content-end mb-3">
                <div
                    v-if="petsitterApplicationStatus === 'pending'"
                    class="alert alert-success py-2 px-3 small mb-0"
                    role="status"
                >
                    Candidatura enviada! Aguarda a aprovação de um administrador.
                </div>
                <div
                    v-else-if="petsitterApplicationStatus === 'rejected'"
                    class="alert alert-danger py-2 px-3 small mb-0"
                    role="status"
                >
                    A tua candidatura a petsitter foi recusada.
                </div>
                <div
                    v-else-if="petsitterApplicationStatus === 'inactive'"
                    class="alert alert-secondary py-2 px-3 small mb-0"
                    role="status"
                >
                    O teu perfil de petsitter está desativado.
                </div>
                <button
                    v-else
                    type="button"
                    class="btn btn-outline-primary btn-sm rounded-pill px-4"
                    @click="showFormPetsitter = true"
                >
                    Quero ser petsitter
                </button>
            </div>

            <section
                class="card bg-white border border-primary rounded-4 p-3 p-md-4 mb-3 position-relative"
            >
                <button
                    type="button"
                    class="btn btn-primary btn-sm d-flex align-items-center gap-2 position-absolute top-0 end-0 mt-3 me-3 px-4"
                    @click="showEditModal = true"
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
                                class="profile-avatar d-flex align-items-center justify-content-center rounded-circle border border-primary flex-shrink-0 overflow-hidden"
                            >
                                <img
                                    v-if="user?.avatar_url"
                                    :src="user.avatar_url"
                                    alt="Foto de perfil"
                                    class="profile-avatar-img"
                                />
                                <UserRound v-else :size="64" class="text-primary" />
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
                <h2 class="h5 fw-semibold mb-3">Os meus animais</h2>

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
                        <AnimalCard
                            :animal="animal"
                            @edit="openEditAnimalModal"
                            @delete="openDeleteModal"
                        />
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

        <ModalAddPet
            :show="showEditAnimalModal"
            :animal="editingAnimal"
            @close="showEditAnimalModal = false; editingAnimal = null"
            @saved="handleAnimalSaved"
        />

        <ModalDeleteAnimal
            :show="showDeleteModal"
            :animal-name="deletingAnimal?.name ?? ''"
            :deleting="deletingAnimalLoading"
            @close="showDeleteModal = false; deletingAnimal = null"
            @confirmed="confirmDeleteAnimal"
        />

        <ModalEditOwnerProfile
            :show="showEditModal"
            :user="editData"
            @close="showEditModal = false"
            @save="handleSaveProfile"
            @delete-account="handleDeleteAccount"
        />

        <ModalFormPetsitter
            :show="showFormPetsitter"
            @close="showFormPetsitter = false"
            @saved="handlePetsitterApplicationSaved"
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

.profile-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
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
