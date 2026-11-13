<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { nextTick, ref, watch } from 'vue';
import type { PetsitterFormErrors, PetsitterProfile } from '@/types';
import InputError from '@/components/InputError.vue';

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'saved'): void;
}>();

const props = defineProps<{
    show: boolean;
    petsitter:
        | (PetsitterProfile & {
        name?: string;
        phone?: string | null;
        avatar_url?: string | null;
        photo_url?: string | null;
        user?: {
            name?: string;
            phone?: string | null;
            avatar_url?: string | null;
        };
    })
        | null;
}>();

// Form state
const name = ref('');
const phone = ref('');
const biography = ref('');
const experience = ref('');
const serviceLocation = ref('');
const pricePerDay = ref(0);
const age = ref(0);
const sex = ref('');
const acceptedSizes = ref<string[]>([]);
const acceptedAnimalTypes = ref<string[]>([]);

// Photo state
const avatarFile = ref<File | null>(null);
const avatarPreview = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

// UI state
const errors = ref<
    PetsitterFormErrors & {
    name?: string[];
    phone?: string[];
    avatar?: string[];
    photo?: string[];
}
>({});
const isLoading = ref(false);
const generalError = ref('');
const successMessage = ref('');

const sizeOptions = [
    { value: 'small', label: 'Pequeno' },
    { value: 'medium', label: 'Médio' },
    { value: 'large', label: 'Grande' },
];

const animalTypeOptions = [
    { value: 'dog', label: 'Cão' },
    { value: 'cat', label: 'Gato' },
    { value: 'other', label: 'Outro' },
];

const sexOptions = [
    { value: 'male', label: 'Masculino' },
    { value: 'female', label: 'Feminino' },
    { value: 'other', label: 'Outro' },
];

const populateForm = () => {
    if (!props.petsitter) return;
    name.value = props.petsitter.name ?? props.petsitter.user?.name ?? '';
    // Remove o prefixo +351 e formata para apenas 9 dígitos
    const rawPhone = props.petsitter.phone ?? props.petsitter.user?.phone ?? '';
    if (rawPhone) {
        phone.value = rawPhone.replace(/\D/g, '').replace(/^351/, '').slice(-9);
    } else {
        phone.value = '';
    }
    biography.value = props.petsitter.biography ?? '';
    experience.value = props.petsitter.experience ?? '';
    serviceLocation.value = props.petsitter.service_location ?? '';
    pricePerDay.value = props.petsitter.price_per_day ?? 0;
    age.value = props.petsitter.age ?? 0;
    sex.value = props.petsitter.sex ?? '';
    acceptedSizes.value = props.petsitter.accepted_sizes
        ? [...props.petsitter.accepted_sizes]
        : [];
    acceptedAnimalTypes.value = props.petsitter.accepted_animal_types
        ? [...props.petsitter.accepted_animal_types]
        : [];
    avatarFile.value = null;
    avatarPreview.value =
        props.petsitter.avatar_url ??
        props.petsitter.photo_url ??
        props.petsitter.user?.avatar_url ??
        null;
};

const resetForm = () => {
    errors.value = {};
    generalError.value = '';
    successMessage.value = '';
    avatarFile.value = null;
};

const triggerFileInput = () => {
    fileInputRef.value?.click();
};

const handleAvatarChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    avatarFile.value = file;
    avatarPreview.value = URL.createObjectURL(file);
};

const close = () => {
    resetForm();
    emit('close');
};

watch(
    () => props.show,
    async (newVal) => {
        if (newVal) {
            resetForm();
            await nextTick();
            populateForm();
        }
    },
);

const toggleArrayValue = (
    array: string[],
    value: string,
    setter: (val: string[]) => void,
) => {
    const index = array.indexOf(value);
    if (index >= 0) {
        setter(array.filter((v) => v !== value));
    } else {
        setter([...array, value]);
    }
};

const submit = async () => {
    if (!props.petsitter) return;
    errors.value = {};
    generalError.value = '';
    successMessage.value = '';
    isLoading.value = true;

    try {
        await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin',
        });

        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        const xsrfToken = match ? decodeURIComponent(match[1]) : '';

        let response: Response;

        if (avatarFile.value) {
            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('name', name.value.trim());
            if (phone.value.trim()) {
                formData.append('phone', phone.value.trim());
            }
            if (biography.value.trim()) {
                formData.append('biography', biography.value.trim());
            }
            formData.append('experience', experience.value.trim());
            formData.append('service_location', serviceLocation.value.trim());
            formData.append('price_per_day', String(pricePerDay.value));
            formData.append('age', String(age.value));
            if (sex.value) {
                formData.append('sex', sex.value);
            }
            acceptedSizes.value.forEach((size) =>
                formData.append('accepted_sizes[]', size),
            );
            acceptedAnimalTypes.value.forEach((type) =>
                formData.append('accepted_animal_types[]', type),
            );
            formData.append('avatar', avatarFile.value);
            formData.append('photo', avatarFile.value);

            response = await fetch(`/api/petsitters/${props.petsitter.id}`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': xsrfToken ?? '',
                },
                body: formData,
            });
        } else {
            const body: Record<string, string | number | string[] | null> = {
                name: name.value.trim(),
                phone: phone.value.trim() || null,
                biography: biography.value.trim() || null,
                experience: experience.value.trim(),
                service_location: serviceLocation.value.trim(),
                price_per_day: pricePerDay.value,
                age: age.value,
                sex: sex.value || null,
                accepted_sizes:
                    acceptedSizes.value.length > 0 ? acceptedSizes.value : null,
                accepted_animal_types:
                    acceptedAnimalTypes.value.length > 0
                        ? acceptedAnimalTypes.value
                        : null,
            };

            response = await fetch(`/api/petsitters/${props.petsitter.id}`, {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': xsrfToken ?? '',
                },
                body: JSON.stringify(body),
            });
        }

        if (response.ok) {
            successMessage.value = 'Perfil atualizado com sucesso!';
            emit('saved');
            router.reload({
                onSuccess: () => {
                    close();
                },
            });
        } else {
            const errorData = await response.json().catch(() => ({}));

            if (errorData.errors) {
                errors.value = errorData.errors;
            } else {
                generalError.value =
                    errorData.message ?? 'Erro ao atualizar perfil.';
            }
        }
    } catch {
        generalError.value = 'Erro de conexão. Tente novamente.';
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="patudo-modal-backdrop"
            @click.self="close"
            @keydown.escape="close"
        >
            <div class="patudo-modal-card" role="dialog" aria-modal="true">
                <!-- Botão Fechar (X) -->
                <button
                    type="button"
                    class="patudo-close-btn"
                    aria-label="Fechar"
                    @click="close"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                        <path d="M18 6L6 18M6 6l12 12" />
                    </svg>
                </button>

                <!-- Cabeçalho -->
                <div class="mb-3">
                    <h2 class="patudo-modal-title">Editar Perfil de Petsitter</h2>
                    <p class="patudo-modal-subtitle">
                        Atualiza os teus dados pessoais, experiência e preferências de cuidado.
                    </p>
                </div>

                <!-- Corpo com scroll suave -->
                <form class="patudo-modal-scroll-body" @submit.prevent="submit">
                    <!-- Erro geral -->
                    <div
                        v-if="generalError"
                        class="patudo-alert-error mb-3"
                        role="alert"
                    >
                        {{ generalError }}
                    </div>

                    <!-- Mensagem de sucesso -->
                    <div
                        v-if="successMessage"
                        class="patudo-alert-success mb-3"
                        role="alert"
                    >
                        {{ successMessage }}
                    </div>

                    <!-- Alterar Foto de Perfil -->
                    <div class="patudo-avatar-row mb-3">
                        <div class="patudo-avatar-circle" @click="triggerFileInput">
                            <img
                                v-if="avatarPreview"
                                :src="avatarPreview"
                                alt="Foto de perfil"
                                class="patudo-avatar-img"
                            />
                            <svg
                                v-else
                                viewBox="0 0 100 100"
                                class="patudo-avatar-placeholder"
                                aria-hidden="true"
                            >
                                <circle cx="50" cy="50" r="48" fill="#ffffff" />
                                <path d="M35 72 C35 66, 65 66, 65 72 L68 85 L32 85 Z" fill="#f3c69f" />
                                <path d="M27 52 C27 25, 73 25, 73 52 L76 64 L24 64 Z" fill="#f25c66" />
                                <path d="M34 42 C34 30, 66 30, 66 42 C66 57, 58 66, 50 66 C42 66, 34 57, 34 42 Z" fill="#fbd3ae" />
                                <path d="M32 42 C32 24, 68 24, 68 42 C60 39, 42 44, 32 42 Z" fill="#f25c66" />
                            </svg>
                        </div>

                        <div class="d-flex flex-column align-items-start gap-1">
                            <input
                                ref="fileInputRef"
                                type="file"
                                accept="image/*"
                                class="d-none"
                                @change="handleAvatarChange"
                            />
                            <button
                                type="button"
                                class="btn-patudo-photo"
                                @click="triggerFileInput"
                            >
                                Alterar foto
                            </button>
                            <span class="patudo-photo-hint">JPG ou PNG (máx. 2MB)</span>
                            <InputError :message="errors.avatar?.[0] || errors.photo?.[0]" />
                        </div>
                    </div>

                    <!-- Nome Completo -->
                    <div class="mb-3">
                        <label
                            for="petsitter-name"
                            class="patudo-section-label d-block mb-1"
                        >
                            Nome completo *
                        </label>
                        <input
                            id="petsitter-name"
                            v-model="name"
                            type="text"
                            class="patudo-input"
                            :class="{ 'is-invalid': errors.name }"
                            placeholder="Ex: Maria Santos"
                            required
                        />
                        <InputError :message="errors.name?.[0]" />
                    </div>

                    <!-- Telemóvel + Localização de serviço lado a lado -->
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-sm-6">
                            <label
                                for="petsitter-phone"
                                class="patudo-section-label d-block mb-1"
                            >
                                Telemóvel
                            </label>
                            <input
                                id="petsitter-phone"
                                v-model="phone"
                                type="tel"
                                inputmode="numeric"
                                maxlength="9"
                                pattern="[0-9]*"
                                @input="phone = phone.replace(/\D/g, '').slice(0, 9)"
                                class="patudo-input"
                                :class="{ 'is-invalid': errors.phone }"
                                placeholder="Ex: 912345678"
                            />
                            <InputError :message="errors.phone?.[0]" />
                        </div>

                        <div class="col-12 col-sm-6">
                            <label
                                for="petsitter-service-location"
                                class="patudo-section-label d-block mb-1"
                            >
                                Localização de serviço *
                            </label>
                            <input
                                id="petsitter-service-location"
                                v-model="serviceLocation"
                                type="text"
                                class="patudo-input"
                                :class="{ 'is-invalid': errors.service_location }"
                                placeholder="Ex: Ponta Delgada"
                                required
                            />
                            <InputError :message="errors.service_location?.[0]" />
                        </div>
                    </div>

                    <!-- Preço por dia + Idade lado a lado -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label
                                for="petsitter-price"
                                class="patudo-section-label d-block mb-1"
                            >
                                Preço por dia (€) *
                            </label>
                            <input
                                id="petsitter-price"
                                v-model.number="pricePerDay"
                                type="number"
                                step="0.01"
                                min="0"
                                class="patudo-input"
                                :class="{ 'is-invalid': errors.price_per_day }"
                                required
                            />
                            <InputError :message="errors.price_per_day?.[0]" />
                        </div>

                        <div class="col-6">
                            <label
                                for="petsitter-age"
                                class="patudo-section-label d-block mb-1"
                            >
                                Idade *
                            </label>
                            <input
                                id="petsitter-age"
                                v-model.number="age"
                                type="number"
                                min="18"
                                max="99"
                                class="patudo-input"
                                :class="{ 'is-invalid': errors.age }"
                                required
                            />
                            <InputError :message="errors.age?.[0]" />
                        </div>
                    </div>

                    <!-- Sexo -->
                    <div class="mb-3">
                        <label class="patudo-section-label d-block mb-1">
                            Sexo
                        </label>
                        <div class="d-flex flex-wrap gap-2">
                            <label
                                v-for="option in sexOptions"
                                :key="option.value"
                                class="patudo-chip-option"
                                :class="{ 'is-selected': sex === option.value }"
                            >
                                <input
                                    type="radio"
                                    name="petsitter-sex"
                                    :value="option.value"
                                    v-model="sex"
                                    class="d-none"
                                />
                                <span>{{ option.label }}</span>
                            </label>
                        </div>
                        <InputError :message="errors.sex?.[0]" />
                    </div>

                    <!-- Portes aceites -->
                    <div class="mb-3">
                        <label class="patudo-section-label d-block mb-1">
                            Portes aceites
                        </label>
                        <div class="d-flex flex-wrap gap-2">
                            <label
                                v-for="option in sizeOptions"
                                :key="option.value"
                                class="patudo-chip-option"
                                :class="{
                                    'is-selected': acceptedSizes.includes(option.value),
                                }"
                            >
                                <input
                                    type="checkbox"
                                    :value="option.value"
                                    :checked="acceptedSizes.includes(option.value)"
                                    class="d-none"
                                    @change="
                                        toggleArrayValue(
                                            acceptedSizes,
                                            option.value,
                                            (val) => {
                                                acceptedSizes = val;
                                            },
                                        )
                                    "
                                />
                                <span>{{ option.label }}</span>
                            </label>
                        </div>
                        <InputError :message="errors.accepted_sizes?.[0]" />
                    </div>

                    <!-- Tipos de animal aceites (sem Ave) -->
                    <div class="mb-3">
                        <label class="patudo-section-label d-block mb-1">
                            Tipos de animal aceites
                        </label>
                        <div class="d-flex flex-wrap gap-2">
                            <label
                                v-for="option in animalTypeOptions"
                                :key="option.value"
                                class="patudo-chip-option"
                                :class="{
                                    'is-selected': acceptedAnimalTypes.includes(option.value),
                                }"
                            >
                                <input
                                    type="checkbox"
                                    :value="option.value"
                                    :checked="acceptedAnimalTypes.includes(option.value)"
                                    class="d-none"
                                    @change="
                                        toggleArrayValue(
                                            acceptedAnimalTypes,
                                            option.value,
                                            (val) => {
                                                acceptedAnimalTypes = val;
                                            },
                                        )
                                    "
                                />
                                <span>{{ option.label }}</span>
                            </label>
                        </div>
                        <InputError :message="errors.accepted_animal_types?.[0]" />
                    </div>

                    <!-- Experiência -->
                    <div class="mb-3">
                        <label
                            for="petsitter-experience"
                            class="patudo-section-label d-block mb-1"
                        >
                            Experiência *
                        </label>
                        <textarea
                            id="petsitter-experience"
                            v-model="experience"
                            rows="2"
                            class="patudo-textarea"
                            :class="{ 'is-invalid': errors.experience }"
                            placeholder="Descreva a sua experiência com animais..."
                            required
                        ></textarea>
                        <InputError :message="errors.experience?.[0]" />
                    </div>

                    <!-- Biografia -->
                    <div class="mb-2">
                        <label
                            for="petsitter-biography"
                            class="patudo-section-label d-block mb-1"
                        >
                            Biografia <span class="patudo-optional">(opcional)</span>
                        </label>
                        <textarea
                            id="petsitter-biography"
                            v-model="biography"
                            rows="2"
                            class="patudo-textarea"
                            :class="{ 'is-invalid': errors.biography }"
                            placeholder="Conte um pouco sobre si..."
                        ></textarea>
                        <InputError :message="errors.biography?.[0]" />
                    </div>

                    <!-- Botões de Ação -->
                    <div class="patudo-modal-actions">
                        <button
                            type="button"
                            class="btn-patudo-outline"
                            :disabled="isLoading"
                            @click="close"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="btn-patudo-solid"
                            :disabled="isLoading"
                        >
                            {{ isLoading ? 'A guardar...' : 'Guardar alterações' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.patudo-modal-backdrop {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    z-index: 10000000;
}

.patudo-modal-card {
    position: relative;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    background-color: #fff8f1;
    border: 1.5px solid #c4450c;
    border-radius: 18px;
    padding: 1.5rem 1.75rem;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.patudo-modal-scroll-body {
    overflow-y: auto;
    padding-right: 0.25rem;
}

.patudo-modal-scroll-body::-webkit-scrollbar {
    width: 5px;
}

.patudo-modal-scroll-body::-webkit-scrollbar-thumb {
    background-color: #d98a6c;
    border-radius: 9999px;
}

.patudo-close-btn {
    position: absolute;
    top: 1.25rem;
    right: 1.25rem;
    background: transparent;
    border: none;
    color: #2b211a;
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.15s ease;
}

.patudo-close-btn:hover {
    opacity: 0.65;
}

.patudo-modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #231810;
    margin: 0 0 0.2rem 0;
}

.patudo-modal-subtitle {
    font-size: 0.84rem;
    color: #8c7a6b;
    margin: 0;
}

.patudo-avatar-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    background-color: #ffffff;
    border: 1px solid #e6d5c5;
    border-radius: 14px;
    padding: 0.65rem 0.9rem;
}

.patudo-avatar-circle {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    border: 1.5px solid #c4450c;
    overflow: hidden;
    flex-shrink: 0;
    cursor: pointer;
    background-color: #fff8f1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.patudo-avatar-img,
.patudo-avatar-placeholder {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.btn-patudo-photo {
    background-color: #fff8f1;
    color: #c4450c;
    border: 1px solid #c4450c;
    border-radius: 9999px;
    padding: 0.28rem 0.9rem;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-patudo-photo:hover {
    background-color: #c4450c;
    color: #ffffff;
}

.patudo-photo-hint {
    font-size: 0.72rem;
    color: #8c7a6b;
}

.patudo-section-label {
    font-size: 0.85rem;
    font-weight: 700;
    color: #231810;
}

.patudo-optional {
    font-weight: 400;
    color: #8c7a6b;
    font-size: 0.8rem;
}

.patudo-alert-error {
    background-color: #fee2e2;
    color: #991b1b;
    border-radius: 12px;
    padding: 0.55rem 0.85rem;
    font-size: 0.82rem;
}

.patudo-alert-success {
    background-color: #dcfce7;
    color: #166534;
    border-radius: 12px;
    padding: 0.55rem 0.85rem;
    font-size: 0.82rem;
}

.patudo-input,
.patudo-textarea {
    width: 100%;
    background-color: #ffffff;
    border: 1px solid #e6d5c5;
    border-radius: 12px;
    padding: 0.55rem 0.85rem;
    font-size: 0.85rem;
    color: #231810;
    outline: none;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
}

.patudo-textarea {
    resize: none;
}

.patudo-input::placeholder,
.patudo-textarea::placeholder {
    color: #9e8e80;
}

.patudo-input:focus,
.patudo-textarea:focus {
    border-color: #c4450c;
    box-shadow: 0 0 0 2px rgba(196, 69, 12, 0.2);
}

.patudo-input.is-invalid,
.patudo-textarea.is-invalid {
    border-color: #dc2626;
}

.patudo-chip-option {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background-color: #ffffff;
    border: 1px solid #d98a6c;
    color: #231810;
    border-radius: 9999px;
    padding: 0.32rem 0.9rem;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
}

.patudo-chip-option:hover {
    background-color: #fdf2ea;
}

.patudo-chip-option.is-selected {
    background-color: #c4450c;
    border-color: #c4450c;
    color: #ffffff;
}

.patudo-modal-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 0.65rem;
    margin-top: 1.15rem;
    padding-top: 0.75rem;
    border-top: 1px solid rgba(196, 69, 12, 0.15);
}

.btn-patudo-outline {
    background-color: #ffffff;
    color: #231810;
    border: 1px solid #c4450c;
    border-radius: 12px;
    padding: 0.5rem 1.35rem;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.btn-patudo-outline:hover:not(:disabled) {
    background-color: #fdf2ea;
}

.btn-patudo-solid {
    background-color: #c4450c;
    color: #ffffff;
    border: 1px solid #c4450c;
    border-radius: 12px;
    padding: 0.5rem 1.35rem;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.btn-patudo-solid:hover:not(:disabled) {
    background-color: #a83908;
}

.btn-patudo-outline:disabled,
.btn-patudo-solid:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
