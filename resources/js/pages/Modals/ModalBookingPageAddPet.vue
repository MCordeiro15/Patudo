<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import type { Animal, AnimalFormErrors } from '@/types';
import InputError from '@/components/InputError.vue';

const props = defineProps<{ show: boolean }>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'created', animal: Animal): void;
}>();

const name = ref('');
const sanitizeName = (value: string) => value.replace(/[^\p{L} ]/gu, '');
const species = ref<'Cão' | 'Gato' | ''>('');
const breed = ref('');
const sanitizeBreed = (value: string) => value.replace(/\p{N}/gu, '');
const age = ref<string>('');
const notes = ref('');
const photoFile = ref<File | null>(null);
const photoPreview = ref<string | null>(null);
const photoInput = ref<HTMLInputElement | null>(null);
const errors = ref<AnimalFormErrors>({});
const generalError = ref('');
const submitting = ref(false);

const isValid = computed(
    () =>
        name.value.trim().length > 0 &&
        species.value !== '' &&
        breed.value.trim().length > 0 &&
        age.value !== '',
);

const handleNameInput = (event: Event) => {
    const input = event.target as HTMLInputElement;
    name.value = sanitizeName(input.value);
    input.value = name.value;
};

const handleBreedInput = (event: Event) => {
    const input = event.target as HTMLInputElement;
    breed.value = sanitizeBreed(input.value);
    input.value = breed.value;
};

const clearPreview = () => {
    if (photoPreview.value) URL.revokeObjectURL(photoPreview.value);
    photoPreview.value = null;
};

const resetForm = () => {
    name.value = '';
    species.value = '';
    breed.value = '';
    age.value = '';
    notes.value = '';
    photoFile.value = null;
    if (photoInput.value) photoInput.value.value = '';
    clearPreview();
    errors.value = {};
    generalError.value = '';
};

const close = () => {
    if (submitting.value) return;
    resetForm();
    emit('close');
};

const handlePhotoChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;

    if (
        !['image/jpeg', 'image/png'].includes(file.type) ||
        file.size > 5 * 1024 * 1024
    ) {
        generalError.value = 'Seleciona uma imagem JPG ou PNG até 5 MB.';
        if (photoInput.value) photoInput.value.value = '';
        return;
    }

    clearPreview();
    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
    generalError.value = '';
};

const submit = async () => {
    if (!isValid.value) return;

    errors.value = {};
    generalError.value = '';
    submitting.value = true;

    try {
        await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });
        const xsrfToken =
            document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '';

        const body = new FormData();
        body.append('name', name.value.trim());
        body.append('species', species.value);
        body.append('breed', breed.value.trim());
        body.append('age', String(Number(age.value)));
        body.append('size', 'medium');
        if (notes.value.trim())
            body.append('special_needs', notes.value.trim());
        if (photoFile.value) body.append('photo', photoFile.value);

        const response = await fetch('/api/animals', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(xsrfToken),
            },
            body,
        });

        const payload = await response.json().catch(() => ({}));

        if (response.ok) {
            emit('created', payload.data as Animal);
            resetForm();
            emit('close');
        } else if (payload.errors) {
            errors.value = payload.errors;
        } else {
            generalError.value =
                payload.message ?? 'Não foi possível guardar o animal.';
        }
    } catch {
        generalError.value = 'Erro de conexão. Tente novamente.';
    } finally {
        submitting.value = false;
    }
};

watch(
    () => props.show,
    (open) => {
        if (!open) resetForm();
    },
);

onBeforeUnmount(clearPreview);
</script>

<template>
    <div
        v-if="show"
        class="reservation-add-pet-backdrop"
        @click.self="close"
        @keydown.escape="close"
    >
        <section
            class="reservation-add-pet-card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="reservation-add-pet-title"
        >
            <h2 id="reservation-add-pet-title" class="modal-heading">
                Adicionar animal
            </h2>

            <form @submit.prevent="submit" novalidate class="panel-box">
                <div
                    v-if="generalError"
                    class="alert alert-danger py-1 small mb-2"
                    role="alert"
                >
                    {{ generalError }}
                </div>

                <div class="photo-row mb-2">
                    <label
                        for="reservation-pet-photo"
                        class="photo-circle"
                        title="Escolher fotografia"
                    >
                        <div class="col-12">
                            <div class="patudo-photo-box">
                                <img
                                    v-if="pet?.photos?.[0]"
                                    :src="pet.photos[0]"
                                    :alt="`Foto de ${pet?.name || 'animal'}`"
                                    class="patudo-pet-photo"
                                />
                                <svg
                                    v-else
                                    viewBox="0 0 100 100"
                                    class="patudo-dog-placeholder"
                                    aria-hidden="true"
                                >
                                    <circle
                                        cx="50"
                                        cy="50"
                                        r="46"
                                        fill="#fbe2c4"
                                        stroke="#231810"
                                        stroke-width="1.2"
                                    />
                                    <path
                                        d="M30 22 C20 22, 18 42, 26 48 C30 42, 34 32, 34 25 Z"
                                        fill="#d9934c"
                                        stroke="#231810"
                                        stroke-width="1.5"
                                    />
                                    <path
                                        d="M70 22 C80 22, 82 42, 74 48 C70 42, 66 32, 66 25 Z"
                                        fill="#d9934c"
                                        stroke="#231810"
                                        stroke-width="1.5"
                                    />
                                    <path
                                        d="M72 78 C84 74, 88 58, 82 52 C78 58, 76 66, 70 70 Z"
                                        fill="#e5a967"
                                        stroke="#231810"
                                        stroke-width="1.5"
                                    />
                                    <path
                                        d="M32 54 C28 68, 28 84, 34 90 L66 90 C72 84, 72 68, 68 54 Z"
                                        fill="#e5a967"
                                        stroke="#231810"
                                        stroke-width="1.5"
                                    />
                                    <path
                                        d="M41 58 C39 70, 41 84, 44 90 L56 90 C59 84, 61 70, 59 58 Z"
                                        fill="#f7dec2"
                                    />
                                    <path
                                        d="M36 66 L36 90 M43 72 L43 90 M64 66 L64 90 M57 72 L57 90"
                                        stroke="#231810"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                    />
                                    <path
                                        d="M36 52 Q50 58 64 52"
                                        fill="none"
                                        stroke="#1d70b8"
                                        stroke-width="4"
                                        stroke-linecap="round"
                                    />
                                    <circle
                                        cx="50"
                                        cy="56"
                                        r="2.5"
                                        fill="#d1d5db"
                                        stroke="#231810"
                                        stroke-width="1"
                                    />
                                    <path
                                        d="M32 32 C32 18, 68 18, 68 32 C68 46, 60 52, 50 52 C40 52, 32 46, 32 32 Z"
                                        fill="#e5a967"
                                        stroke="#231810"
                                        stroke-width="1.5"
                                    />
                                    <ellipse
                                        cx="50"
                                        cy="42"
                                        rx="10"
                                        ry="7.5"
                                        fill="#f7dec2"
                                    />
                                    <path
                                        d="M39 32 Q42 29 45 32"
                                        fill="none"
                                        stroke="#231810"
                                        stroke-width="1.6"
                                        stroke-linecap="round"
                                    />
                                    <path
                                        d="M55 32 Q58 29 61 32"
                                        fill="none"
                                        stroke="#231810"
                                        stroke-width="1.6"
                                        stroke-linecap="round"
                                    />
                                    <ellipse
                                        cx="50"
                                        cy="39"
                                        rx="3.5"
                                        ry="2.5"
                                        fill="#231810"
                                    />
                                    <path
                                        d="M47 45 C47 50, 53 50, 53 45 Z"
                                        fill="#ef4444"
                                        stroke="#231810"
                                        stroke-width="1"
                                    />
                                </svg>
                            </div>
                        </div>
                    </label>

                    <label for="reservation-pet-photo" class="add-photo-btn">
                        Adicionar foto <span class="fw-normal">(opcional)</span>
                    </label>

                    <input
                        id="reservation-pet-photo"
                        ref="photoInput"
                        class="d-none"
                        type="file"
                        accept="image/jpeg,image/png"
                        @change="handlePhotoChange"
                    />
                </div>
                <InputError :message="errors.photo?.[0]" />

                <div class="mb-2">
                    <label for="reservation-pet-name" class="field-label"
                        >Nome *</label
                    >
                    <input
                        id="reservation-pet-name"
                        :value="name"
                        @input="handleNameInput"
                        class="pet-control"
                        :class="{ 'is-invalid': errors.name }"
                        maxlength="255"
                        required
                    />
                    <InputError :message="errors.name?.[0]" />
                </div>

                <fieldset class="mb-2">
                    <legend class="field-label">Espécie *</legend>
                    <div class="row g-2">
                        <div class="col-6">
                            <label
                                class="species-option"
                                :class="{
                                    'species-option--selected':
                                        species === 'Cão',
                                }"
                            >
                                <span>Cão</span>
                                <input
                                    v-model="species"
                                    type="radio"
                                    value="Cão"
                                    required
                                />
                            </label>
                        </div>
                        <div class="col-6">
                            <label
                                class="species-option"
                                :class="{
                                    'species-option--selected':
                                        species === 'Gato',
                                }"
                            >
                                <span>Gato</span>
                                <input
                                    v-model="species"
                                    type="radio"
                                    value="Gato"
                                    required
                                />
                            </label>
                        </div>
                    </div>
                    <InputError :message="errors.species?.[0]" />
                </fieldset>

                <div class="row g-2 mb-2">
                    <div class="col-7">
                        <label for="reservation-pet-breed" class="field-label"
                            >Raça</label
                        >
                        <input
                            id="reservation-pet-breed"
                            :value="breed"
                            @input="handleBreedInput"
                            class="pet-control"
                            :class="{ 'is-invalid': errors.breed }"
                            maxlength="255"
                            required
                        />
                        <InputError :message="errors.breed?.[0]" />
                    </div>
                    <div class="col-5">
                        <label for="reservation-pet-age" class="field-label"
                            >Idade (anos)</label
                        >
                        <input
                            id="reservation-pet-age"
                            v-model="age"
                            type="number"
                            min="0"
                            max="99"
                            class="pet-control"
                            :class="{ 'is-invalid': errors.age }"
                            required
                        />
                        <InputError :message="errors.age?.[0]" />
                    </div>
                </div>

                <div class="mb-3">
                    <label for="reservation-pet-notes" class="field-label">
                        Notas para o cuidador
                        <span class="fw-normal">(opcional)</span>
                    </label>
                    <textarea
                        id="reservation-pet-notes"
                        v-model="notes"
                        rows="2"
                        maxlength="1000"
                        class="pet-control pet-textarea"
                        :class="{ 'is-invalid': errors.special_needs }"
                        placeholder="Alergias, medicação, hábitos..."
                    />
                    <InputError :message="errors.special_needs?.[0]" />
                </div>

                <footer class="d-flex justify-content-end gap-2">
                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        :disabled="submitting"
                        @click="close"
                    >
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        {{ submitting ? 'A guardar...' : 'Guardar animal' }}
                    </button>
                </footer>
            </form>
        </section>
    </div>
</template>

<style scoped>
.reservation-add-pet-backdrop {
    position: fixed;
    inset: 0;
    z-index: 10000000;
    display: grid;
    place-items: center;
    padding: 0.75rem;
    background: rgba(0, 0, 0, 0.45);
}

.reservation-add-pet-card {
    width: min(100%, 380px);
    padding: 1.1rem 1rem;
    background: #fffaf3;
    border-radius: 22px;
    box-shadow: 0 1rem 2.25rem rgba(0, 0, 0, 0.22);
    color: #231209;
}

.modal-heading {
    font-size: 1.35rem;
    font-weight: 700;
    color: #231209;
    margin-bottom: 0.7rem;
}

.panel-box {
    background: #fed7aa;
    border: 1.5px solid #c2410c;
    border-radius: 18px;
    padding: 0.9rem;
}

.photo-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.photo-circle {
    width: 56px;
    height: 56px;
    flex-shrink: 0;
    border-radius: 50%;
    overflow: hidden;
    cursor: pointer;
    display: grid;
    place-items: center;
    background: #f5d0a9;
}

.photo-circle img,
.dog-illustration {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.add-photo-btn {
    background: #ffffff;
    color: var(--patudo-castanhoDark);
    border: 1.5px solid #c2410c;
    border-radius: 9px;
    padding: 0.35rem 1rem;
    font-size: 0.85rem;
    font-weight: 400;
    cursor: pointer;
}

.field-label {
    display: block;
    font-size: 0.84rem;
    font-weight: 700;
    color: #231209;
    margin-bottom: 0.25rem;
}

.pet-control {
    width: 100%;
    background: #ffffff;
    border: 1.5px solid #c2410c;
    border-radius: 9px;
    padding: 0.38rem 0.65rem;
    font-size: 0.88rem;
    color: #231209;
}

.pet-control:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(194, 65, 12, 0.22);
}

.pet-textarea {
    resize: vertical;
    min-height: 58px;
}

.species-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    background: #ffffff;
    border: 1.5px solid #c2410c;
    border-radius: 9px;
    padding: 0.4rem 0.7rem;
    font-size: 0.88rem;
    font-weight: 700;
    color: #231209;
    cursor: pointer;
}

.species-option--selected {
    background: #e5e5e5;
}

.species-option input[type='radio'] {
    accent-color: #18181b;
    width: 15px;
    height: 15px;
    margin: 0;
}
</style>
