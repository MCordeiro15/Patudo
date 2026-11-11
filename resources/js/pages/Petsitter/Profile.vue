<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Bell,
    CirclePlus,
    Heart,
    MessageCircle,
    PenLine,
    Pencil,
    Save,
    Trash2,
    UserRound,
} from '@lucide/vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import { computed, ref } from 'vue';
import ModalEditPetsitterProfile from '@/pages/Modals/ModalEditPetsitterProfile.vue';
import type { PetsitterProfile as PetsitterProfileType } from '@/types';

type PetsitterPost = {
    id: number;
    description: string;
    photos: string[];
    createdAt: string | null;
};

type PetsitterProfile = {
    user_id: number;
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
    availabilityDays: string[] | null;
    posts: PetsitterPost[];
};

const DAY_OPTIONS: Array<{ value: string; label: string }> = [
    { value: 'monday', label: 'Segunda' },
    { value: 'tuesday', label: 'Terça' },
    { value: 'wednesday', label: 'Quarta' },
    { value: 'thursday', label: 'Quinta' },
    { value: 'friday', label: 'Sexta' },
    { value: 'saturday', label: 'Sábado' },
    { value: 'sunday', label: 'Domingo' },
];

const props = defineProps<{
    petsitter?: PetsitterProfile | null;
}>();

// Reactive availability days for editing
const editingDays = ref<string[]>([]);
const isEditing = ref(false);
const saving = ref(false);
const saveError = ref<string | null>(null);
const saveSuccess = ref(false);

// Edit profile modal state
const showEditProfileModal = ref(false);

const petsitter = computed(() => props.petsitter);

const currentAvailabilityDays = computed(() => {
    return petsitter.value?.availabilityDays ?? [];
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

const serviceTags = computed(() => {
    if (!petsitter.value) return ['Cuidador'];
    const sizeTags = petsitter.value.acceptedSizes.map(
        (size) => sizeLabelMap[size] ?? size,
    );
    const animalTags = petsitter.value.acceptedAnimalTypes.map(
        (type) => animalTypeLabelMap[type] ?? type,
    );
    const tags = [...sizeTags, ...animalTags];
    return tags.length > 0 ? tags : ['Cuidador'];
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
    if (!petsitter.value?.memberSince) {
        return '--';
    }
    return new Date(petsitter.value.memberSince).toLocaleDateString('pt-PT');
});

const displayName = computed(() => {
    return petsitter.value?.name ?? 'Petsitter';
});

const displayLocation = computed(() => {
    return petsitter.value?.location ?? '--';
});

const displayBiography = computed(() => {
    return petsitter.value?.biography ?? 'Este petsitter ainda não adicionou uma biografia.';
});

const displayExperience = computed(() => {
    return petsitter.value?.experience ?? '';
});

const displayPricePerDay = computed(() => {
    return petsitter.value?.pricePerDay ?? 0;
});

const displayRating = computed(() => {
    return petsitter.value?.rating ?? null;
});

const displayReviewsCount = computed(() => {
    return petsitter.value?.reviewsCount ?? 0;
});

const displayPosts = computed(() => {
    return petsitter.value?.posts ?? [];
});

const hasProfile = computed(() => petsitter.value !== null);

const startEditing = () => {
    editingDays.value = [...currentAvailabilityDays.value];
    isEditing.value = true;
    saveError.value = null;
    saveSuccess.value = false;
};

const cancelEditing = () => {
    isEditing.value = false;
    saveError.value = null;
    saveSuccess.value = false;
};

const toggleDay = (day: string) => {
    const index = editingDays.value.indexOf(day);
    if (index >= 0) {
        editingDays.value.splice(index, 1);
    } else {
        editingDays.value.push(day);
    }
};

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

        const response = await fetch('/api/petsitters/availability/days', {
            method: 'PUT',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ days: editingDays.value }),
        });

        if (response.ok) {
            const data = await response.json();
            // Update the local petsitter data
            if (data.data && data.data.availability_days !== undefined) {
                (props.petsitter as any).availabilityDays = data.data.availability_days;
            }
            isEditing.value = false;
            saveSuccess.value = true;
            setTimeout(() => { saveSuccess.value = false; }, 3000);
        } else {
            const errorData = await response.json().catch(() => ({}));
            if (errorData.errors) {
                const messages = Object.values(errorData.errors).flat();
                saveError.value = messages.join(' ');
            } else {
                saveError.value = errorData.message || 'Erro ao guardar disponibilidade.';
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
                    <a class="nav-link text-dark rounded-pill px-4" href="/"
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

            <div class="row justify-content-center mt-5 pt-4">
                <div class="col-12">
                    <h1 class="text-center fw-bold mb-4 pb-2">O meu perfil</h1>

                    <!-- No profile message -->
                    <section
                        v-if="!hasProfile"
                        class="card border border-warning rounded-4 p-4 text-center"
                    >
                        <p class="mb-0">Ainda não tem um perfil de petsitter.</p>
                    </section>

                    <template v-else>
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
                                                {{ displayName }}
                                            </h2>
                                            <p class="mb-2 text-muted small">
                                                {{ displayLocation }}
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
                                                {{ displayExperience }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <div
                                        class="border-start ps-3 d-grid gap-1 text-muted small"
                                    >
                                        <div>
                                            Rating
                                            <strong class="text-dark ms-1">
                                                {{ formatRating(displayRating) }}
                                            </strong>
                                        </div>
                                        <div>
                                            Avaliações {{ displayReviewsCount }}
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
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h3 class="h5 mb-0">Sobre mim</h3>
                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm rounded-pill"
                                    @click="showEditProfileModal = true"
                                >
                                    <Pencil :size="14" class="me-1" />
                                    Editar perfil
                                </button>
                            </div>
                            <p class="mb-0 text-muted">
                                {{ displayBiography }}
                            </p>
                        </section>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-lg-6">
                                <section
                                    class="card border border-primary rounded-4 p-3 p-md-4 h-100"
                                >
                                    <h3 class="h5 mb-3">Serviços e preços</h3>
                                    <div
                                        class="d-flex align-items-center justify-content-between"
                                    >
                                        <span>Dog Sitting (diário)</span>
                                        <strong>{{
                                            formatPrice(displayPricePerDay)
                                        }}</strong>
                                    </div>
                                </section>
                            </div>

                            <div class="col-12 col-lg-6">
                                <section
                                    class="card border border-primary rounded-4 p-3 p-md-4 h-100"
                                >
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h3 class="h5 mb-0">Disponibilidade</h3>
                                        <template v-if="!isEditing">
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary btn-sm rounded-pill"
                                                @click="startEditing"
                                            >
                                                <Pencil :size="14" class="me-1" />
                                                Editar
                                            </button>
                                        </template>
                                    </div>

                                    <!-- View mode: show day labels -->
                                    <template v-if="!isEditing">
                                        <div
                                            v-if="currentAvailabilityDays.length > 0"
                                            class="d-flex flex-wrap gap-2"
                                        >
                                            <span
                                                v-for="day in DAY_OPTIONS"
                                                :key="day.value"
                                                class="badge rounded-pill px-3 py-2"
                                                :class="
                                                    currentAvailabilityDays.includes(day.value)
                                                        ? 'text-bg-success'
                                                        : 'text-bg-light text-muted'
                                                "
                                            >
                                                {{ day.label }}
                                            </span>
                                        </div>
                                        <p v-else class="mb-0 text-muted small">
                                            Nenhum dia selecionado.
                                        </p>
                                    </template>

                                    <!-- Edit mode: checkboxes -->
                                    <template v-else>
                                        <p class="text-muted small mb-3">
                                            Selecione os dias em que está disponível:
                                        </p>
                                        <div class="d-flex flex-column gap-2">
                                            <label
                                                v-for="day in DAY_OPTIONS"
                                                :key="day.value"
                                                class="form-check d-flex align-items-center"
                                            >
                                                <input
                                                    class="form-check-input me-2"
                                                    type="checkbox"
                                                    :checked="editingDays.includes(day.value)"
                                                    @change="toggleDay(day.value)"
                                                />
                                                <span>{{ day.label }}</span>
                                            </label>
                                        </div>

                                        <div class="d-flex gap-2 mt-3">
                                            <button
                                                type="button"
                                                class="btn btn-success btn-sm rounded-pill"
                                                :disabled="saving"
                                                @click="saveAvailability"
                                            >
                                                <Save v-if="!saving" :size="14" class="me-1" />
                                                {{ saving ? 'A guardar...' : 'Guardar' }}
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary btn-sm rounded-pill"
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
                                        <div
                                            v-if="saveSuccess"
                                            class="alert alert-success mt-2 py-2 small"
                                        >
                                            Disponibilidade guardada com sucesso!
                                        </div>
                                    </template>
                                </section>
                            </div>
                        </div>

                        <section class="mt-5">
                            <h2 class="text-center fw-bold my-5">Publicações</h2>
                            <div class="d-grid gap-3">
                                <article
                                    v-for="post in displayPosts"
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
                    </template>
                </div>
            </div>
        </div>

        <!-- Edit Profile Modal -->
        <ModalEditPetsitterProfile
            :show="showEditProfileModal"
            :petsitter="petsitter as PetsitterProfileType | null"
            @close="showEditProfileModal = false"
        />
    </main>
</template>
