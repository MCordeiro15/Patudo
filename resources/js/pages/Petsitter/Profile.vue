<!-- Página de perfil do utilizador. Mostra e permite editar informações pessoais. -->
<!-- User profile page. Shows and allows editing personal information. -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Bell,
    MessageCircle,
    Save,
    Trash2,
    UserRound, LogOut,
} from '@lucide/vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import AdminNavigationMenu from '@/components/AdminNavigationMenu.vue';
import { computed, onMounted, ref } from 'vue';
import ModalDeletePost from '@/pages/Modals/ModalDeletePost.vue';
import ModalEditPetsitterProfile from '@/pages/Modals/ModalEditPetsitterProfile.vue';
import ModalEditPost from '@/pages/Modals/ModalEditPost.vue';
import ModalNewPost from '@/pages/Modals/ModalNewPost.vue';
import ModalNotifications from '@/pages/Modals/ModalNotifications.vue';
import { useNotifications } from '@/composables/useNotifications';
import type { PetsitterProfile as PetsitterProfileType, Post } from '@/types';

// Tipo TypeScript para PetsitterProfile.
// TypeScript type for PetsitterProfile.
type PetsitterProfile = {
    id: number;
    user_id: number;
    name: string;
    phone: string | null;
    avatar_url?: string | null;
    biography: string | null;
    experience: string;
    service_location: string;
    age: number;
    price_per_day: number;
    accepted_sizes: string[] | null;
    accepted_animal_types: string[] | null;
    sex: string | null;
    approval_status: string;
    identification_documents?: string | null;
    availability_days: string[] | null;
    created_at: string;
    updated_at: string;
};

const SERVICE_OPTIONS = [
    { value: 'pet-sitting', label: 'Pet Sitting' },
    { value: 'passeio', label: 'Passeio' },
] as const;

type PetsitterService = {
    id: number;
    type: string; // texto livre; os dois tipos antigos têm nome bonito em SERVICE_OPTIONS
    price: number;
};

// Opções de dias da semana para o seletor de disponibilidade.
// Weekday options for the availability selector.
const DAY_OPTIONS: Array<{ value: string; label: string }> = [
    { value: 'monday', label: 'Segunda' },
    { value: 'tuesday', label: 'Terça' },
    { value: 'wednesday', label: 'Quarta' },
    { value: 'thursday', label: 'Quinta' },
    { value: 'friday', label: 'Sexta' },
    { value: 'saturday', label: 'Sábado' },
    { value: 'sunday', label: 'Domingo' },
];

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<{
    petsitter?: PetsitterProfile | null;
    isOwnProfile?: boolean;
    isAdmin?: boolean;
}>();

// Reactive availability days for editing
const editingDays = ref<string[]>([]);
const isEditing = ref(false);
const saving = ref(false);
const saveError = ref<string | null>(null);
const saveSuccess = ref(false);

// Edit profile modal state
const showEditProfileModal = ref(false);
const showNotifications = ref(false);
const { unreadCount } = useNotifications();

// Propriedade computada que retorna os dados do petsitter a partir das props.
// Computed property that returns petsitter data from props.
const petsitter = computed(() => props.petsitter);
// Propriedade computada que verifica se o perfil pertence ao utilizador autenticado.
// Computed property that checks if the profile belongs to the authenticated user.
const isOwnProfile = computed(() => props.isOwnProfile ?? false);
// Propriedade computada que verifica se o utilizador é administrador.
// Computed property that checks if the user is an administrator.
const isAdmin = computed(() => props.isAdmin ?? false);
// Propriedade computada que determina se o utilizador pode editar o perfil.
// Computed property that determines if the user can edit the profile.
const canEdit = computed(() => isOwnProfile.value || isAdmin.value);

// Propriedade computada que retorna os dias de disponibilidade atuais.
// Computed property that returns the current availability days.
const currentAvailabilityDays = computed(() => {
    return petsitter.value?.availability_days ?? [];
});

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

// Propriedade computada que gera as tags de serviço a partir dos tamanhos e tipos de animal aceites.
// Computed property that generates service tags from accepted animal sizes and types.
const serviceTags = computed(() => {
    if (!petsitter.value) return ['Cuidador'];
    const sizeTags = (petsitter.value.accepted_sizes ?? []).map(
        (size) => sizeLabelMap[size] ?? size,
    );
    const animalTags = (petsitter.value.accepted_animal_types ?? []).map(
        (type) => animalTypeLabelMap[type] ?? type,
    );
    const tags = [...sizeTags, ...animalTags];
    return tags.length > 0 ? tags : ['Cuidador'];
});

const formatServicePrice = (price: number) =>
    `${Number(price).toLocaleString('pt-PT', { maximumFractionDigits: 2 })} €`;

const serviceTypeLabel = (type: PetsitterService['type']) =>
    SERVICE_OPTIONS.find((option) => option.value === type)?.label ?? type;

// Formata uma classificação numérica para exibição.
// Formats a numeric rating for display.
const formatRating = (rating: number | null) => {
    if (rating === null) {
        return 'Sem avaliações';
    }
    return rating.toString().replace('.', ',');
};

// Formata uma data/hora para exibição no formato pt-PT.
// Formats a date/time for display in pt-PT format.
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

// Propriedade computada que formata a data de registo como string legível.
// Computed property that formats the registration date as a readable string.
const memberSinceLabel = computed(() => {
    if (!petsitter.value?.created_at) {
        return '--';
    }
    return new Date(petsitter.value.created_at).toLocaleDateString('pt-PT');
});

// Propriedade computada que retorna o nome de exibição do utilizador.
// Computed property that returns the user's display name.
const displayName = computed(() => {
    return petsitter.value?.name ?? 'Petsitter';
});

// Propriedade computada que retorna a localização de serviço.
// Computed property that returns the service location.
const displayLocation = computed(() => {
    return petsitter.value?.service_location ?? '--';
});

// Propriedade computada que retorna o número de telemóvel formatado com prefixo +351.
// Computed property that returns the formatted phone number with +351 prefix.
const displayPhone = computed(() => {
    const phone = petsitter.value?.phone;
    if (!phone) return null;
    // Remove o prefixo +351 se já existir e extrai apenas os 9 dígitos
    const digits = phone.replace(/\D/g, '').replace(/^351/, '').slice(-9);
    // Formata o número separando 3 dígitos por 3 dígitos
    const formatted = digits.replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
    return `+351 ${formatted}`;
});

// Propriedade computada que retorna a URL da foto de perfil.
// Computed property that returns the profile photo URL.
const displayAvatar = computed(() => {
    return petsitter.value?.avatar_url ?? null;
});

// Propriedade computada que retorna a biografia ou mensagem padrão.
// Computed property that returns the biography or default message.
const displayBiography = computed(() => {
    return (
        petsitter.value?.biography ??
        'Este petsitter ainda não adicionou uma biografia.'
    );
});

// Propriedade computada que retorna a experiência do petsitter.
// Computed property that returns the petsitter's experience.
const displayExperience = computed(() => {
    return petsitter.value?.experience ?? '';
});

// Propriedade computada que retorna a classificação média.
// Computed property that returns the average rating.
const displayRating = computed(() => {
    return null;
});

// Propriedade computada que retorna o número de avaliações.
// Computed property that returns the number of reviews.
const displayReviewsCount = computed(() => {
    return 0;
});

// --- Publicações: carregadas da API (GET /api/petsitters/{id}/posts) ---
const posts = ref<Post[]>([]);
const postsLoading = ref(false);
const postsError = ref('');
const showNewPostModal = ref(false);

// Estado do modal de eliminação de post
const showDeletePostModal = ref(false);
const deletingPostId = ref<number | null>(null);
const deletingPost = ref(false);

// Estado do modal de edição de post
const showEditPostModal = ref(false);
const editingPostId = ref<number | null>(null);

const services = ref<PetsitterService[]>([]);
const servicesLoading = ref(false);
const servicesError = ref('');
const serviceFormOpen = ref(false);
const editingServiceId = ref<number | null>(null);
const serviceForm = ref({ type: '', price: '' });
const serviceFieldErrors = ref<{ type?: string; price?: string }>({});
const serviceFormError = ref('');
const savingService = ref(false);
const deletingServiceId = ref<number | null>(null);

// só o dono do perfil, se aprovado, pode publicar (o back-end devolve 403 aos outros)
const canPost = computed(
    () => isOwnProfile.value && petsitter.value?.approval_status === 'approved',
);

function navigateToMyProfile() {
    window.location.href = '/petsitters/profile';
}

// Carrega as publicações do petsitter a partir da API.
// Loads the petsitter's posts from the API.
const loadPosts = async () => {
    if (!petsitter.value) return;

    postsLoading.value = true;
    postsError.value = '';

    try {
        const res = await fetch(
            `/api/petsitters/${petsitter.value.user_id}/posts`,
            {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        );
        const data = await res.json();

        if (res.ok) {
            posts.value = data.data;
        } else {
            postsError.value =
                data.message ?? 'Erro ao carregar as publicações.';
        }
    } catch {
        postsError.value = 'Erro de rede ao carregar as publicações.';
    } finally {
        postsLoading.value = false;
    }
};

// o post novo entra no topo da lista, sem recarregar a página
const onPostCreated = (post: Post) => {
    posts.value.unshift(post);
};

// Abre o modal de confirmação para eliminar uma publicação.
// Opens the confirmation modal to delete a post.
const deletePost = (postId: number) => {
    deletingPostId.value = postId;
    showDeletePostModal.value = true;
};

// Confirma a eliminação de uma publicação via API DELETE.
// Confirms the deletion of a post via API DELETE.
const confirmDeletePost = async () => {
    if (deletingPostId.value === null) return;

    deletingPost.value = true;

    try {
        // Get CSRF token
        await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin',
        });

        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        const xsrfToken = match ? decodeURIComponent(match[1]) : '';

        const response = await fetch(`/api/posts/${deletingPostId.value}`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken,
            },
        });

        if (response.ok || response.status === 204) {
            // Remove o post da lista local
            posts.value = posts.value.filter((post) => post.id !== deletingPostId.value);
            showDeletePostModal.value = false;
            deletingPostId.value = null;
        } else {
            const data = await response.json().catch(() => ({}));
            alert(data.message ?? 'Erro ao eliminar a publicação.');
        }
    } catch {
        alert('Erro de rede ao eliminar a publicação.');
    } finally {
        deletingPost.value = false;
    }
};

// Cancela a eliminação e fecha o modal.
// Cancels the deletion and closes the modal.
const cancelDeletePost = () => {
    showDeletePostModal.value = false;
    deletingPostId.value = null;
};

// Abre o modal de edição para uma publicação.
// Opens the edit modal for a post.
const editPost = (postId: number) => {
    editingPostId.value = postId;
    showEditPostModal.value = true;
};

// Cancela a edição e fecha o modal.
// Cancels the edit and closes the modal.
const cancelEditPost = () => {
    showEditPostModal.value = false;
    editingPostId.value = null;
};

// Atualiza um post na lista local após edição bem-sucedida.
// Updates a post in the local list after successful edit.
const onPostUpdated = (updatedPost: Post) => {
    const index = posts.value.findIndex((post) => post.id === updatedPost.id);
    if (index !== -1) {
        posts.value[index] = updatedPost;
    }
    cancelEditPost();
};

onMounted(loadPosts);

const loadServices = async () => {
    if (!petsitter.value?.user_id) return;

    servicesLoading.value = true;
    servicesError.value = '';

    try {
        const response = await fetch(`/api/petsitters/${petsitter.value.user_id}/services`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await response.json().catch(() => ({}));

        if (response.ok) {
            services.value = data.data ?? [];
        } else {
            servicesError.value = data.message ?? 'Não foi possível carregar os serviços.';
        }
    } catch {
        servicesError.value = 'Erro de rede ao carregar os serviços.';
    } finally {
        servicesLoading.value = false;
    }
};

function beginCreateService() {
    if (!isOwnProfile.value) return;
    editingServiceId.value = null;
    serviceForm.value = { type: '', price: '' };
    serviceFieldErrors.value = {};
    serviceFormError.value = '';
    serviceFormOpen.value = true;
}

function beginEditService(service: PetsitterService) {
    if (!isOwnProfile.value) return;
    editingServiceId.value = service.id;
    // mostra o nome bonito (ex.: "Pet Sitting" em vez de "pet-sitting")
    serviceForm.value = { type: serviceTypeLabel(service.type), price: String(service.price) };
    serviceFieldErrors.value = {};
    serviceFormError.value = '';
    serviceFormOpen.value = true;
}

function cancelServiceForm() {
    serviceFormOpen.value = false;
    editingServiceId.value = null;
    serviceFieldErrors.value = {};
    serviceFormError.value = '';
}

// O que vai para a API: se ao editar o nome ficou igual ao nome bonito, mantém o tipo guardado
// (ex.: "Pet Sitting" volta a "pet-sitting"), para só mudar o preço sem renomear o serviço.
function servicePayload() {
    const type = serviceForm.value.type.trim();
    const original = services.value.find((service) => service.id === editingServiceId.value);
    if (original && type === serviceTypeLabel(original.type)) {
        return { type: original.type, price: serviceForm.value.price };
    }
    return { type, price: serviceForm.value.price };
}

async function saveService() {
    if (!isOwnProfile.value || !petsitter.value?.user_id) return;

    savingService.value = true;
    serviceFieldErrors.value = {};
    serviceFormError.value = '';

    try {
        await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });
        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        const xsrfToken = match ? decodeURIComponent(match[1]) : '';
        const isEditing = editingServiceId.value !== null;
        const response = await fetch(
            isEditing
                ? `/api/services/${editingServiceId.value}`
                : `/api/petsitters/${petsitter.value.user_id}/services`,
            {
                method: isEditing ? 'PUT' : 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': xsrfToken,
                },
                body: JSON.stringify(servicePayload()),
            },
        );
        const data = await response.json().catch(() => ({}));

        if (response.ok) {
            const savedService = data.data as PetsitterService;
            const index = services.value.findIndex((service) => service.id === savedService.id);
            if (index === -1) services.value.push(savedService);
            else services.value[index] = savedService;
            services.value.sort((a, b) => a.type.localeCompare(b.type));
            cancelServiceForm();
        } else if (data.errors) {
            serviceFieldErrors.value = Object.fromEntries(
                Object.entries(data.errors).map(([field, messages]) => [field, (messages as string[])[0]]),
            );
            serviceFormError.value = data.message ?? '';
        } else {
            serviceFormError.value = data.message ?? 'Não foi possível guardar o serviço.';
        }
    } catch {
        serviceFormError.value = 'Erro de rede ao guardar o serviço.';
    } finally {
        savingService.value = false;
    }
}

async function deleteService(service: PetsitterService) {
    if (!isOwnProfile.value || !window.confirm(`Tem a certeza que quer apagar o serviço ${serviceTypeLabel(service.type)}?`)) return;

    deletingServiceId.value = service.id;
    servicesError.value = '';

    try {
        await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });
        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        const xsrfToken = match ? decodeURIComponent(match[1]) : '';
        const response = await fetch(`/api/services/${service.id}`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken,
            },
        });

        if (response.ok || response.status === 204) {
            services.value = services.value.filter((item) => item.id !== service.id);
            if (editingServiceId.value === service.id) cancelServiceForm();
        } else {
            const data = await response.json().catch(() => ({}));
            servicesError.value = data.message ?? 'Não foi possível apagar o serviço.';
        }
    } catch {
        servicesError.value = 'Erro de rede ao apagar o serviço.';
    } finally {
        deletingServiceId.value = null;
    }
}

onMounted(loadServices);

// Propriedade computada que verifica se o perfil existe.
// Computed property that checks if the profile exists.
const hasProfile = computed(() => petsitter.value != null);

// Inicia o modo de edição da disponibilidade, copiando os dias atuais.
// Starts availability edit mode, copying the current days.
const startEditing = () => {
    editingDays.value = [...currentAvailabilityDays.value];
    isEditing.value = true;
    saveError.value = null;
    saveSuccess.value = false;
};

// Cancela o modo de edição e limpa mensagens de erro/sucesso.
// Cancels edit mode and clears error/success messages.
const cancelEditing = () => {
    isEditing.value = false;
    saveError.value = null;
    saveSuccess.value = false;
};

// Altera o estado de um dia na lista de edição (adiciona ou remove).
// Toggles a day's state in the editing list (adds or removes).
const toggleDay = (day: string) => {
    const index = editingDays.value.indexOf(day);
    if (index >= 0) {
        editingDays.value.splice(index, 1);
    } else {
        editingDays.value.push(day);
    }
};

// Guarda os dias de disponibilidade atualizados via API PUT.
// Saves the updated availability days via API PUT.
const saveAvailability = async () => {
    saving.value = true;
    saveError.value = null;
    saveSuccess.value = false;

    try {
        // Get CSRF token
        await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin',
        });

        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        const xsrfToken = match ? decodeURIComponent(match[1]) : '';

        const response = await fetch('/api/petsitters/availability/days', {
            method: 'PUT',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken,
            },
            body: JSON.stringify({
                days: editingDays.value,
                petsitter_id: petsitter.value?.user_id,
            }),
        });

        if (response.ok) {
            const data = await response.json();
            // Update the local petsitter data
            if (data.data && data.data.availability_days !== undefined) {
                (props.petsitter as any).availability_days =
                    data.data.availability_days;
            }
            isEditing.value = false;
            saveSuccess.value = true;
            setTimeout(() => {
                saveSuccess.value = false;
            }, 3000);
        } else {
            const errorData = await response.json().catch(() => ({}));
            if (errorData.errors) {
                const messages = Object.values(errorData.errors).flat();
                saveError.value = messages.join(' ');
            } else {
                saveError.value =
                    errorData.message || 'Erro ao guardar disponibilidade.';
            }
        }
    } catch {
        saveError.value = 'Erro de conexão. Tente novamente.';
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <Head :title="isOwnProfile ? 'O meu perfil' : 'Perfil do Petsitter'" />

    <!-- Conteúdo principal da página. -->
    <!-- Main content area of the page. -->
    <main class="min-vh-100 py-4 patudo-page patudo-page-shell">
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
                    <a class="nav-link text-dark rounded-pill px-4" href="/"
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
                    <a href="/messages" class="text-dark"
                    ><MessageCircle :size="25"
                    /></a>
                    <button
                        type="button"
                        class="text-dark bg-transparent border-0 p-0 position-relative"
                        @click="showNotifications = true"
                    >
                        <Bell :size="25" />
                        <span
                            v-if="unreadCount > 0"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            style="font-size: 0.6rem"
                        >
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </span>
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
                        class="btn btn-outline-danger rounded-pill d-flex align-items-center gap-2 px-3"
                    >
                        <LogOut :size="16" />
                        <span>Sair</span>
                    </Link>
                </div>
            </nav>

            <h1 class="text-center fw-bold display-5 mt-5 pt-4 mb-4">
                {{ isOwnProfile ? 'O meu perfil' : 'Perfil do Petsitter' }}
            </h1>

            <!-- No profile message -->
            <section
                v-if="!hasProfile"
                class="card bg-white border border-primary rounded-4 p-4 text-center"
            >
                <p class="mb-0">Ainda não tem um perfil de petsitter.</p>
            </section>

            <template v-else>
                <!-- Secção de informações principais do perfil: nome, localização, tags de serviço, experiência, rating e data de membro. -->
                <!-- Main profile information section: name, location, service tags, experience, rating, and member since date. -->
                <section
                    class="card bg-white border border-primary rounded-4 p-3 p-md-4 mb-3 position-relative"
                >
                    <button
                        v-if="canEdit"
                        type="button"
                        class="btn btn-primary btn-sm d-flex align-items-center gap-2 position-absolute top-0 end-0 mt-3 me-3 px-4"
                        @click="showEditProfileModal = true"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"
                            />
                            <path
                                d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z"
                            />
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
                                        v-if="displayAvatar"
                                        :src="displayAvatar"
                                        alt="Foto de perfil"
                                        class="profile-avatar-img"
                                    />
                                    <UserRound
                                        v-else
                                        :size="64"
                                        class="text-primary"
                                    />
                                </div>

                                <div>
                                    <h2 class="h5 fw-bold mb-0">
                                        {{ displayName }}
                                    </h2>
                                    <p class="text-muted small mb-0">
                                        {{ displayLocation }}
                                    </p>
                                    <p
                                        v-if="displayPhone"
                                        class="text-muted small mb-0"
                                    >
                                        {{ displayPhone }}
                                    </p>

                                    <div class="d-flex flex-wrap gap-3 my-2">
                                        <span
                                            v-for="tag in serviceTags"
                                            :key="tag"
                                            class="tag-pill rounded-pill py-1 small"
                                        >
                                            {{ tag }}
                                        </span>
                                    </div>

                                    <p class="text-muted small mb-0">
                                        {{ displayExperience }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="border-start border-secondary ps-4 py-2">
                                <p class="fs-5 text-muted mb-1">
                                    Rating {{ formatRating(displayRating) }}
                                </p>
                                <p class="text-muted mb-2">
                                    Avaliações {{ displayReviewsCount }}
                                </p>
                                <p class="text-muted mb-0">
                                    Membro desde:<br />
                                    {{ memberSinceLabel }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Secção de biografia do petsitter. -->
                <!-- Petsitter biography section. -->
                <section
                    class="card bg-white border border-primary rounded-4 p-3 px-md-4 mb-3 about-card"
                >
                    <h2 class="h5 fw-semibold mb-3">Sobre mim</h2>
                    <p class="text-muted fs-5 mb-0 ps-md-2">
                        {{ displayBiography }}
                    </p>
                </section>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-lg-6">
                        <!-- Secção de serviços e preços do petsitter. -->
                        <!-- Petsitter services and pricing section. -->
                        <section
                            class="card bg-white border border-primary rounded-4 p-3 px-md-4 info-card"
                        >
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                <h2 class="h5 fw-semibold mb-0">Serviços e preços</h2>
                                <button
                                    v-if="isOwnProfile"
                                    type="button"
                                    class="btn btn-primary btn-sm"
                                    @click="beginCreateService"
                                >
                                    Adicionar serviço
                                </button>
                            </div>

                            <p v-if="servicesLoading" class="small text-muted">A carregar serviços...</p>
                            <p v-if="servicesError" class="small text-danger" role="alert">{{ servicesError }}</p>

                            <div
                                v-for="service in services"
                                :key="service.id"
                                class="border-bottom pb-2 mb-2 small text-muted"
                            >
                                <div class="d-flex align-items-center justify-content-between gap-2">
                                    <span>{{ serviceTypeLabel(service.type) }}</span>
                                    <span class="fw-semibold">{{ formatServicePrice(service.price) }}</span>
                                </div>
                                <div v-if="isOwnProfile" class="d-flex justify-content-end gap-2 mt-2">
                                    <button
                                        type="button"
                                        class="btn btn-outline-primary btn-sm"
                                        @click="beginEditService(service)"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-outline-danger btn-sm"
                                        :disabled="deletingServiceId === service.id"
                                        @click="deleteService(service)"
                                    >
                                        {{ deletingServiceId === service.id ? 'A apagar...' : 'Apagar' }}
                                    </button>
                                </div>
                            </div>

                            <p v-if="!servicesLoading && !services.length" class="small text-muted mb-0">
                                Ainda não foram adicionados serviços.
                            </p>

                            <form v-if="serviceFormOpen" class="border-top pt-3 mt-3" @submit.prevent="saveService">
                                <h3 class="h6 fw-semibold">{{ editingServiceId === null ? 'Novo serviço' : 'Editar serviço' }}</h3>
                                <div class="mb-2">
                                    <label for="service-type" class="form-label small">Serviço</label>
                                    <input
                                        id="service-type"
                                        v-model="serviceForm.type"
                                        type="text"
                                        maxlength="50"
                                        placeholder="Ex.: Passeio, Banho e tosquia"
                                        class="form-control form-control-sm"
                                        :class="{ 'is-invalid': serviceFieldErrors.type }"
                                        :aria-invalid="!!serviceFieldErrors.type"
                                    />
                                    <div v-if="serviceFieldErrors.type" class="invalid-feedback d-block" role="alert">
                                        {{ serviceFieldErrors.type }}
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="service-price" class="form-label small">Preço (€)</label>
                                    <input
                                        id="service-price"
                                        v-model="serviceForm.price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="form-control form-control-sm"
                                        :class="{ 'is-invalid': serviceFieldErrors.price }"
                                        :aria-invalid="!!serviceFieldErrors.price"
                                    />
                                    <div v-if="serviceFieldErrors.price" class="invalid-feedback d-block" role="alert">
                                        {{ serviceFieldErrors.price }}
                                    </div>
                                </div>
                                <p v-if="serviceFormError" class="small text-danger" role="alert">{{ serviceFormError }}</p>
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" :disabled="savingService" @click="cancelServiceForm">
                                        Cancelar
                                    </button>
                                    <button type="submit" class="btn btn-primary btn-sm" :disabled="savingService">
                                        {{ savingService ? 'A guardar...' : 'Guardar' }}
                                    </button>
                                </div>
                            </form>
                        </section>
                    </div>

                    <div class="col-12 col-lg-6">
                        <!-- Secção de disponibilidade do petsitter com modo de visualização e edição. -->
                        <!-- Petsitter availability section with view and edit modes. -->
                        <section
                            class="card bg-white border border-primary rounded-4 p-3 px-md-4 info-card"
                        >
                            <div
                                class="d-flex align-items-center justify-content-between mb-3"
                            >
                                <h2 class="h5 fw-semibold mb-0">
                                    Disponibilidade
                                </h2>
                                <button
                                    v-if="canEdit && !isEditing"
                                    type="button"
                                    class="btn btn-primary d-flex align-items-center gap-1"
                                    @click="startEditing"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="14"
                                        height="14"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path
                                            d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"
                                        />
                                        <path
                                            d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z"
                                        />
                                    </svg>
                                    Editar
                                </button>
                            </div>

                            <!-- View mode: quadrados com visto / cruz -->
                            <div v-if="!isEditing" class="days-grid">
                                <div
                                    v-for="day in DAY_OPTIONS"
                                    :key="day.value"
                                    class="day-box"
                                    :class="
                                        currentAvailabilityDays.includes(
                                            day.value,
                                        )
                                            ? 'day-on'
                                            : 'day-off'
                                    "
                                >
                                    <span>{{ day.label.slice(0, 3) }}</span>
                                    <svg
                                        v-if="
                                            currentAvailabilityDays.includes(
                                                day.value,
                                            )
                                        "
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="M3 12.5l6 6L21 6" />
                                    </svg>
                                    <svg
                                        v-else
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="M4 4l16 16M20 4L4 20" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Edit mode: os mesmos quadrados, clicáveis -->
                            <template v-else-if="canEdit">
                                <div class="days-grid">
                                    <button
                                        v-for="day in DAY_OPTIONS"
                                        :key="day.value"
                                        type="button"
                                        class="day-box day-btn"
                                        :class="
                                            editingDays.includes(day.value)
                                                ? 'day-on'
                                                : 'day-off'
                                        "
                                        @click="toggleDay(day.value)"
                                    >
                                        <span>{{ day.label.slice(0, 3) }}</span>
                                        <svg
                                            v-if="editingDays.includes(day.value)"
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="18"
                                            height="18"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path d="M3 12.5l6 6L21 6" />
                                        </svg>
                                        <svg
                                            v-else
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="18"
                                            height="18"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path d="M4 4l16 16M20 4L4 20" />
                                        </svg>
                                    </button>
                                </div>

                                        <div class="d-flex gap-2 mt-3">
                                            <button
                                                type="button"
                                                class="btn btn-aceitar btn-sm rounded-pill"
                                                :disabled="saving"
                                                @click="saveAvailability"
                                            >
                                                {{
                                                    saving
                                                        ? 'A guardar...'
                                                        : 'Guardar'
                                                }}
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-outline-danger btn-sm rounded-pill"
                                                :disabled="saving"
                                                @click="cancelEditing"
                                            >
                                                Cancelar
                                            </button>
                                        </div>

                                <div
                                    v-if="saveError"
                                    class="alert alert-danger mt-2 py-2 small"
                                >
                                    {{ saveError }}
                                </div>
                            </template>

                            <div
                                v-if="saveSuccess"
                                class="alert alert-success mt-2 py-2 small"
                            >
                                Disponibilidade guardada com sucesso!
                            </div>
                        </section>
                    </div>
                </div>

                <!-- Secção de publicações do petsitter com lista de posts e botão para criar novo. -->
                <!-- Petsitter posts section with post list and create new button. -->
                <h1 class="text-center fw-bold display-5 mt-5 mb-4">
                    Publicações
                </h1>

                <p v-if="postsLoading" class="text-center">
                    A carregar publicações...
                </p>
                <div
                    v-else-if="postsError"
                    class="alert alert-danger py-2 small text-center"
                >
                    {{ postsError }}
                </div>
                <p
                    v-else-if="posts.length === 0 && !canPost"
                    class="text-center text-muted"
                >
                    Ainda não há publicações.
                </p>

                <div class="row justify-content-center mb-4">
                    <div class="col-12 col-lg-10">
                        <div class="d-grid gap-3">
                            <article
                                v-for="post in posts"
                                :key="post.id"
                                class="post-card card bg-white border border-primary rounded-4 position-relative"
                            >
                                <div
                                    v-if="post.photos && post.photos.length > 0"
                                    class="post-img-wrap"
                                >
                                    <img
                                        :src="post.photos[0]"
                                        alt="Imagem da publicação"
                                        class="post-img"
                                    />
                                </div>

                                <div class="post-body">
                                    <h3 v-if="post.title" class="post-title">
                                        {{ post.title }}
                                    </h3>
                                    <p class="post-text text-muted">
                                        {{ post.description }}
                                    </p>
                                    <small class="post-date">{{
                                            formatDateTime(post.created_at)
                                        }}</small>
                                </div>

                                <div class="post-actions">
                                    <button
                                        v-if="canEdit"
                                        type="button"
                                        class="btn btn-link text-dark p-0"
                                        @click="editPost(post.id)"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="40"
                                            height="40"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.25"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path
                                                d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"
                                            />
                                            <path
                                                d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        v-if="canEdit"
                                        type="button"
                                        class="btn btn-link text-dark p-0"
                                        @click="deletePost(post.id)"
                                    >
                                        <Trash2 :size="40" :stroke-width="1.25" />
                                    </button>
                                </div>

                            </article>

                            <article
                                v-if="canPost"
                                class="add-post-card card bg-white border border-primary rounded-4 p-4 text-center d-flex flex-column align-items-center justify-content-center gap-2"
                                @click="showNewPostModal = true"
                            >
                                <div class="add-post-circle"></div>
                                <span class="small">Adicionar nova publicação</span>
                            </article>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Edit Profile Modal -->
        <ModalEditPetsitterProfile
            v-if="canEdit"
            :show="showEditProfileModal"
            :petsitter="petsitter as PetsitterProfileType | null"
            @close="showEditProfileModal = false"
        />

        <!-- New Post Modal -->
        <ModalNewPost
            v-if="canPost"
            :show="showNewPostModal"
            @close="showNewPostModal = false"
            @created="onPostCreated"
        />

        <!-- Notifications Modal -->
        <ModalNotifications
            :show="showNotifications"
            @close="showNotifications = false"
        />

        <!-- Delete Post Modal -->
        <ModalDeletePost
            :show="showDeletePostModal"
            :post-title="deletingPostId !== null ? posts.find(p => p.id === deletingPostId)?.title : undefined"
            :deleting="deletingPost"
            @close="cancelDeletePost"
            @confirmed="confirmDeletePost"
        />

        <!-- Edit Post Modal -->
        <ModalEditPost
            :show="showEditPostModal"
            :post="editingPostId !== null ? posts.find(p => p.id === editingPostId) ?? null : null"
            @close="cancelEditPost"
            @updated="onPostUpdated"
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
    min-height: 170px;
}

.info-card {
    min-height: 135px;
}

/* Linha do preço: o valor fica no fim da linha. */
/* Price row: the value sits at the end of the row. */
.price-row {
    display: flex;
    justify-content: space-between;
}

/* Quadrados dos dias da semana (Seg a Dom). */
/* Weekday squares (Mon to Sun). */
.days-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.4rem;
}

.day-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    width: 100%;
    height: 72px;
    padding: 0;
    border: 0;
    border-radius: 0.75rem;
    color: #fff;
    font-size: 0.8rem;
    font-weight: 600;
    line-height: 1.2;
}

.day-on {
    background-color: #587257;
}

.day-off {
    background-color: #c4491f;
}

.day-btn {
    cursor: pointer;
}

.btn-aceitar {
    color: #ffffff;
    background-color: var(--patudo-verde, #4f6f52);
    border: 1px solid var(--patudo-verde, #4f6f52);
    --bs-btn-padding-y: 0.45rem;
    --bs-btn-padding-x: 1.6rem;
    --bs-btn-font-size: 0.8rem;
    --bs-btn-font-weight: 600;
    border-radius: 25px;
    line-height: normal;
}

.btn-aceitar:hover,
.btn-aceitar:focus {
    color: #ffffff;
    background-color: #3f5a42;
    border-color: #3f5a42;
}

/* Cartão da publicação — medidas tiradas do mockup. */
/* Post card — measurements taken from the mockup. */
.post-card {
    flex-direction: row;
    gap: 14px;
    padding: 10px 21px;
}

.post-img-wrap {
    flex: 0 0 32%;
    align-self: flex-start;
    aspect-ratio: 1 / 1;
}

.post-img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 1rem;
    background-color: #837877;
}

.post-body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    padding: 24px 140px 5px 0;
}

.post-body:first-child {
    padding-left: 14px;
}

.post-title {
    margin: 0 0 9px;
    font-size: 1.9rem;
    font-weight: 600;
    line-height: 1.2;
}

.post-text {
    max-width: 422px;
    margin: 0;
    font-size: 1.13rem;
    line-height: 1.31;
}

.post-date {
    margin-top: auto;
    font-size: 1.03rem;
    line-height: 1.5;
    word-spacing: 0.3em;
    color: #2f1d13;
}

.post-actions {
    position: absolute;
    top: 13px;
    right: 20px;
    display: flex;
    gap: 23px;
}

.post-heart {
    position: absolute;
    right: 19px;
    bottom: 10px;
    line-height: 1;
}

@media (max-width: 767.98px) {
    .post-card {
        flex-direction: column;
        padding: 10px;
    }

    .post-img-wrap {
        flex-basis: auto;
        width: 100%;
        aspect-ratio: 16 / 10;
    }

    .post-body {
        padding: 8px 8px 40px;
    }

    .post-actions {
        top: 18px;
        right: 18px;
        padding: 2px 8px;
        border-radius: 0.75rem;
        background-color: rgba(255, 255, 255, 0.9);
    }
}

.add-post-card {
    min-height: 320px;
    cursor: pointer;
}

/* Círculo cor de pêssego com o sinal + (mockup). */
/* Peach circle with the + sign (mockup). */
.add-post-circle {
    position: relative;
    width: 110px;
    height: 110px;
    border-radius: 50%;
    background-color: #f8d8a9;
}

.add-post-circle::before,
.add-post-circle::after {
    content: '';
    position: absolute;
    background-color: #fff;
}

.add-post-circle::before {
    top: 50%;
    left: 13%;
    right: 13%;
    height: 1px;
}

.add-post-circle::after {
    left: 50%;
    top: 13%;
    bottom: 13%;
    width: 1px;
}
</style>
