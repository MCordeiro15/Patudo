<script setup lang="ts">
import { ref, onBeforeUnmount, watch } from 'vue';
import type { Animal, AnimalFormErrors } from '@/types';
import InputError from '@/components/InputError.vue';

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'saved', animal?: Animal): void;
}>();

const props = defineProps<{
    show: boolean;
    animal?: Animal | null;
}>();

const name = ref('');
const sanitizeName = (value: string) => value.replace(/[^\p{L} ]/gu, '');
const species = ref('');
const breed = ref('');
const sanitizeBreed = (value: string) => value.replace(/\p{N}/gu, '');
const size = ref('');
const specialNeeds = ref('');
const aggression = ref('');
const medication = ref('');
const sex = ref('');
const weight = ref('');
const age = ref('');
const photoFile = ref<File | null>(null);
const photoPreview = ref<string | null>(null);
const photoInput = ref<HTMLInputElement | null>(null);

const errors = ref<AnimalFormErrors>({});
const isLoading = ref(false);
const generalError = ref('');

const sizeOptions = [
    { value: 'small', label: 'Pequeno' },
    { value: 'medium', label: 'Médio' },
    { value: 'large', label: 'Grande' },
];

const aggressionOptions = [
    { value: 'nenhuma', label: 'Nenhuma' },
    { value: 'baixa', label: 'Baixa' },
    { value: 'media', label: 'Média' },
    { value: 'alta', label: 'Alta' },
];

const sexOptions = [
    { value: 'macho', label: 'Macho' },
    { value: 'femea', label: 'Fêmea' },
];

const clearPhotoPreview = () => {
    if (photoPreview.value?.startsWith('blob:')) {
        URL.revokeObjectURL(photoPreview.value);
    }
    photoPreview.value = null;
};

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

const removePhoto = () => {
    clearPhotoPreview();
    photoFile.value = null;

    if (photoInput.value) {
        photoInput.value.value = '';
    }
};

const handlePhotoChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) return;

    const allowedTypes = ['image/jpeg', 'image/png'];

    if (!allowedTypes.includes(file.type)) {
        generalError.value = 'Seleciona uma imagem JPG ou PNG.';
        target.value = '';
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        generalError.value = 'A fotografia não pode ultrapassar 5 MB.';
        target.value = '';
        return;
    }

    clearPhotoPreview();

    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);

    generalError.value = '';
};

const resetForm = () => {
    name.value = '';
    species.value = '';
    breed.value = '';
    size.value = '';
    specialNeeds.value = '';
    aggression.value = '';
    medication.value = '';
    sex.value = '';
    weight.value = '';
    age.value = '';

    clearPhotoPreview();
    photoFile.value = null;

    if (photoInput.value) {
        photoInput.value.value = '';
    }

    errors.value = {};
    generalError.value = '';
};

const fillForm = (animal?: Animal | null) => {
    resetForm();

    if (!animal) return;

    name.value = animal.name;
    species.value = animal.species;
    breed.value = animal.breed;
    size.value = animal.size;
    specialNeeds.value = animal.special_needs ?? '';
    aggression.value = animal.aggression ?? '';
    medication.value = animal.medication ?? '';
    sex.value = animal.sex ?? '';
    weight.value = animal.weight !== null ? String(animal.weight) : '';
    age.value = animal.age !== null ? String(animal.age) : '';
    photoPreview.value = animal.photo;
};

watch(
    () => [props.show, props.animal] as const,
    ([show, animal]) => {
        if (show) fillForm(animal);
    },
);

const close = () => {
    resetForm();
    emit('close');
};

onBeforeUnmount(() => {
    clearPhotoPreview();
});

const submit = async () => {
    errors.value = {};
    generalError.value = '';
    isLoading.value = true;

    try {
        await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin',
        });

        const xsrfToken = document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1];

        const fields: Record<string, string | number | null> = {
            name: name.value.trim(),
            species: species.value.trim(),
            breed: breed.value.trim(),
            size: size.value,
            special_needs: specialNeeds.value.trim() || null,
            aggression: aggression.value || null,
            medication: medication.value.trim() || null,
            sex: sex.value || null,
            weight: weight.value ? parseFloat(weight.value) : null,
            age: age.value !== '' ? Number(age.value) : null,
        };

        // FormData (e não JSON) para poder enviar a fotografia.
        const body = new FormData();
        for (const [key, value] of Object.entries(fields)) {
            body.append(key, value === null ? '' : String(value));
        }
        if (photoFile.value) {
            body.append('photo', photoFile.value);
        } else if (props.animal) {
            body.append('photo', photoPreview.value ?? '');
        }
        if (props.animal) body.append('_method', 'PUT');

        const response = await fetch(
            props.animal ? `/api/animals/${props.animal.id}` : '/api/animals',
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(xsrfToken ?? ''),
                },
                body,
            },
        );

        if (response.ok) {
            const result = await response.json();
            emit('saved', result.data);
            close();
        } else {
            const errorData = await response.json().catch(() => ({}));

            if (errorData.errors) {
                errors.value = errorData.errors;
            } else {
                generalError.value =
                    errorData.message ?? 'Erro ao adicionar animal.';
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
    <div
        v-if="show"
        class="patudo-modal-backdrop position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center p-3"
        @click.self="close"
        @keydown.escape="close"
    >
        <div
            class="patudo-modal modal-content w-100 border border-primary shadow overflow-hidden"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-add-animal-title"
        >
            <div
                class="d-flex justify-content-between align-items-center flex-shrink-0 px-4 py-3 border-bottom border-primary"
            >
                <h2 id="modal-add-animal-title" class="h5 fw-bold mb-0">
                    {{ animal ? 'Editar animal' : 'Adicionar animal' }}
                </h2>

                <button
                    type="button"
                    class="btn border-0 p-1 text-body"
                    aria-label="Fechar"
                    @click="close"
                >
                    <svg
                        width="30"
                        height="30"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                    >
                        <path d="M18 6L6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form
                class="d-flex flex-column flex-grow-1 min-h-0"
                @submit.prevent="submit"
            >
                <div class="flex-grow-1 min-h-0 overflow-y-auto px-4 py-3">
                    <div
                        v-if="generalError"
                        class="alert alert-danger rounded-3 py-2 small mb-3"
                        role="alert"
                    >
                        {{ generalError }}
                    </div>

                    <div class="mb-3">
                        <span class="d-block fw-bold small mb-2"> Fotos </span>

                        <div class="d-flex align-items-center gap-3">
                            <label
                                for="animal-photo"
                                class="animal-photo-upload d-flex align-items-center justify-content-center flex-shrink-0"
                            >
                                <img
                                    v-if="photoPreview"
                                    :src="photoPreview"
                                    alt="Fotografia do animal"
                                    class="animal-photo-image"
                                />

                                <svg
                                    v-else
                                    width="25"
                                    height="25"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                >
                                    <path d="M12 5v14M5 12h14" />
                                </svg>
                            </label>

                            <div>
                                <p class="small text-secondary mb-1">
                                    {{
                                        photoPreview
                                            ? 'Fotografia selecionada.'
                                            : 'Adiciona fotografias do teu animal.'
                                    }}
                                </p>

                                <p class="small text-secondary mb-0">
                                    Até 5 fotos. A primeira é a foto
                                    principal.<br />
                                    JPG ou PNG, até 5 MB cada.
                                </p>

                                <button
                                    v-if="photoPreview"
                                    type="button"
                                    class="animal-photo-remove mt-1"
                                    @click="removePhoto"
                                >
                                    Remover fotografia
                                </button>
                            </div>
                        </div>

                        <input
                            id="animal-photo"
                            ref="photoInput"
                            type="file"
                            accept="image/png, image/jpeg"
                            class="d-none"
                            @change="handlePhotoChange"
                        />

                        <InputError :message="errors.photo?.[0]" />
                    </div>

                    <span class="d-block fw-bold small mb-2">
                        Informações
                    </span>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-8">
                            <label
                                for="animal-name"
                                class="form-label fw-bold small mb-1"
                            >
                                Nome do animal *
                            </label>

                            <input
                                id="animal-name"
                                :value="name"
                                type="text"
                                @input="handleNameInput"
                                class="form-control patudo-field rounded-3"
                                :class="{ 'is-invalid': errors.name }"
                                placeholder="Ex.: Max"
                                autocomplete="off"
                                required
                                aria-required="true"
                            />

                            <InputError :message="errors.name?.[0]" />
                        </div>

                        <div class="col-12 col-md-4">
                            <label
                                for="animal-species"
                                class="form-label fw-bold small mb-1"
                            >
                                Tipo de animal *
                            </label>

                            <select
                                id="animal-species"
                                v-model="species"
                                class="form-select patudo-field rounded-3"
                                :class="[
                                    { 'is-invalid': errors.species },
                                    species ? 'text-body' : 'text-secondary',
                                ]"
                                required
                                aria-required="true"
                            >
                                <option value="" disabled>Selecionar</option>
                                <option value="Cão" class="text-body">
                                    Cão
                                </option>
                                <option value="Gato" class="text-body">
                                    Gato
                                </option>
                                <option value="Outro" class="text-body">
                                    Outro
                                </option>
                            </select>

                            <InputError :message="errors.species?.[0]" />
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-4">
                            <label
                                for="animal-breed"
                                class="form-label fw-bold small mb-1"
                            >
                                Raça
                            </label>

                            <input
                                id="animal-breed"
                                :value="breed"
                                type="text"
                                @input="handleBreedInput"
                                class="form-control patudo-field rounded-3"
                                :class="{ 'is-invalid': errors.breed }"
                                placeholder="Ex.: Labrador"
                                autocomplete="off"
                            />

                            <InputError :message="errors.breed?.[0]" />
                        </div>

                        <div class="col-12 col-md-4">
                            <label
                                for="animal-sex"
                                class="form-label fw-bold small mb-1"
                            >
                                Sexo
                            </label>

                            <select
                                id="animal-sex"
                                v-model="sex"
                                class="form-select patudo-field rounded-3"
                                :class="[
                                    { 'is-invalid': errors.sex },
                                    sex ? 'text-body' : 'text-secondary',
                                ]"
                            >
                                <option value="" disabled>Selecionar</option>

                                <option
                                    v-for="option in sexOptions"
                                    :key="option.value"
                                    :value="option.value"
                                    class="text-body"
                                >
                                    {{ option.label }}
                                </option>
                            </select>

                            <InputError :message="errors.sex?.[0]" />
                        </div>

                        <div class="col-12 col-md-4">
                            <label
                                for="animal-age"
                                class="form-label fw-bold small mb-1"
                            >
                                Idade
                            </label>

                            <input
                                id="animal-age"
                                v-model="age"
                                type="number"
                                min="0"
                                max="40"
                                step="1"
                                class="form-control patudo-field rounded-3"
                                placeholder="Ex.: 3"
                                autocomplete="off"
                            />
                            <InputError :message="errors.age?.[0]" />
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-4">
                            <label
                                for="animal-size"
                                class="form-label fw-bold small mb-1"
                            >
                                Porte
                            </label>

                            <select
                                id="animal-size"
                                v-model="size"
                                class="form-select patudo-field rounded-3"
                                :class="[
                                    { 'is-invalid': errors.size },
                                    size ? 'text-body' : 'text-secondary',
                                ]"
                            >
                                <option value="" disabled>Selecionar</option>

                                <option
                                    v-for="option in sizeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                    class="text-body"
                                >
                                    {{ option.label }}
                                </option>
                            </select>

                            <InputError :message="errors.size?.[0]" />
                        </div>

                        <div class="col-12 col-md-4">
                            <label
                                for="animal-weight"
                                class="form-label fw-bold small mb-1"
                            >
                                Peso (kg)
                            </label>

                            <input
                                id="animal-weight"
                                v-model="weight"
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control patudo-field rounded-3"
                                :class="{ 'is-invalid': errors.weight }"
                                placeholder="Ex.: 9"
                            />

                            <InputError :message="errors.weight?.[0]" />
                        </div>

                        <div class="col-12 col-md-4">
                            <label
                                for="animal-aggression"
                                class="form-label fw-bold small mb-1"
                            >
                                Agressividade
                            </label>

                            <select
                                id="animal-aggression"
                                v-model="aggression"
                                class="form-select patudo-field rounded-3"
                                :class="[
                                    { 'is-invalid': errors.aggression },
                                    aggression ? 'text-body' : 'text-secondary',
                                ]"
                            >
                                <option value="" disabled>Selecionar</option>

                                <option
                                    v-for="option in aggressionOptions"
                                    :key="option.value"
                                    :value="option.value"
                                    class="text-body"
                                >
                                    {{ option.label }}
                                </option>
                            </select>

                            <InputError :message="errors.aggression?.[0]" />
                        </div>
                    </div>

                    <div class="mb-1">
                        <label
                            for="animal-special-needs"
                            class="form-label fw-bold small mb-1"
                        >
                            Sobre o animal/Cuidados a ter
                        </label>

                        <textarea
                            id="animal-special-needs"
                            v-model="specialNeeds"
                            rows="7"
                            maxlength="300"
                            class="form-control patudo-field patudo-textarea rounded-4 bg-white"
                            placeholder="Personalidade, hábitos, gostos e preferências, necessidades especiais, medicação ou cuidados específicos..."
                        ></textarea>

                        <div class="text-end small text-secondary mt-1">
                            {{ specialNeeds.length }}/300
                        </div>

                        <InputError :message="errors.special_needs?.[0]" />
                    </div>
                </div>

                <div
                    class="d-flex flex-wrap justify-content-between align-items-center gap-2 flex-shrink-0 px-4 py-3 border-top border-primary"
                >
                    <span class="patudo-note text-secondary">
                        * Campos obrigatórios
                    </span>

                    <div class="d-flex align-items-center gap-2">
                        <button
                            type="button"
                            class="btn btn-outline-primary rounded-pill flex-fill"
                            :disabled="isLoading"
                            @click="close"
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="btn btn-primary rounded-pill px-4 fw-semibold"
                            :disabled="isLoading"
                        >
                            {{
                                isLoading
                                    ? 'A guardar...'
                                    : animal
                                      ? 'Guardar alterações'
                                      : 'Guardar animal'
                            }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.patudo-modal-backdrop {
    background-color: rgba(0, 0, 0, 0.45);
    z-index: 10000000;
}

.patudo-modal {
    max-width: 640px;
    max-height: 92vh;
}

.min-h-0 {
    min-height: 0;
}

.animal-photo-upload {
    width: 56px;
    height: 56px;
    border: 1.5px dashed var(--patudo-terracota);
    border-radius: 11px;
    background-color: #fff;
    color: var(--patudo-terracota);
    cursor: pointer;
    overflow: hidden;
    transition: background-color 0.2s ease;
}

.animal-photo-upload:hover {
    background-color: #fbf2ec;
}

.animal-photo-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.animal-photo-remove {
    border: none;
    padding: 0;
    background: transparent;
    color: var(--patudo-terracota);
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
}

.patudo-note {
    font-size: 0.7rem;
}

.patudo-field {
    border: 1.5px solid var(--patudo-terracota);
    background-color: #ffffff;
    font-size: 0.9rem;
    padding-top: 0.6rem;
    padding-bottom: 0.6rem;
    padding-left: 0.95rem;
}

.patudo-field:focus {
    border-color: var(--patudo-terracota);
    box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25);
}

.patudo-field.is-invalid {
    border-color: var(--bs-danger);
}

.patudo-textarea {
    resize: none;
    padding: 0.95rem;
}
</style>
