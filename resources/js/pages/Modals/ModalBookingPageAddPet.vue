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
const species = ref<'Cão' | 'Gato' | ''>('');
const breed = ref('');
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

    if (!['image/jpeg', 'image/png'].includes(file.type) || file.size > 5 * 1024 * 1024) {
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
        const xsrfToken = document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '';

        const body = new FormData();
        body.append('name', name.value.trim());
        body.append('species', species.value);
        body.append('breed', breed.value.trim());
        body.append('age', String(Number(age.value)));
        body.append('size', 'medium');
        if (notes.value.trim()) body.append('special_needs', notes.value.trim());
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
            generalError.value = payload.message ?? 'Não foi possível guardar o animal.';
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
    <div v-if="show" class="reservation-add-pet-backdrop" @click.self="close" @keydown.escape="close">
        <section
            class="reservation-add-pet-card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="reservation-add-pet-title"
        >
            <h2 id="reservation-add-pet-title" class="modal-heading">Adicionar animal</h2>

            <form @submit.prevent="submit" novalidate class="panel-box">
                <div v-if="generalError" class="alert alert-danger py-1 small mb-2" role="alert">
                    {{ generalError }}
                </div>

                <div class="photo-row mb-2">
                    <label for="reservation-pet-photo" class="photo-circle" title="Escolher fotografia">
                        <img v-if="photoPreview" :src="photoPreview" alt="Pré-visualização do animal" />
                        <svg v-else viewBox="0 0 80 80" class="dog-illustration" aria-hidden="true">
                            <circle cx="40" cy="40" r="38" fill="#f5d0a9" />
                            <ellipse cx="22" cy="36" rx="8" ry="15" transform="rotate(-14 22 36)" fill="#c68642" stroke="#231712" stroke-width="2.5" />
                            <ellipse cx="58" cy="36" rx="8" ry="15" transform="rotate(14 58 36)" fill="#c68642" stroke="#231712" stroke-width="2.5" />
                            <ellipse cx="40" cy="44" rx="18" ry="16" fill="#e0a96d" stroke="#231712" stroke-width="2.5" />
                            <ellipse cx="40" cy="49" rx="9" ry="7" fill="#fff3e0" />
                            <circle cx="34" cy="41" r="2.3" fill="#231712" />
                            <circle cx="46" cy="41" r="2.3" fill="#231712" />
                            <ellipse cx="40" cy="46.5" rx="3.2" ry="2.2" fill="#231712" />
                            <path d="M27 58 Q40 65 53 58 L55 72 L25 72 Z" fill="#e0a96d" stroke="#231712" stroke-width="2.5" />
                            <path d="M28 58 Q40 64 52 58" fill="none" stroke="#2563eb" stroke-width="4" />
                        </svg>
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
                    <label for="reservation-pet-name" class="field-label">Nome *</label>
                    <input
                        id="reservation-pet-name"
                        v-model="name"
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
                                :class="{ 'species-option--selected': species === 'Cão' }"
                            >
                                <span>Cão</span>
                                <input v-model="species" type="radio" value="Cão" required />
                            </label>
                        </div>
                        <div class="col-6">
                            <label
                                class="species-option"
                                :class="{ 'species-option--selected': species === 'Gato' }"
                            >
                                <span>Gato</span>
                                <input v-model="species" type="radio" value="Gato" required />
                            </label>
                        </div>
                    </div>
                    <InputError :message="errors.species?.[0]" />
                </fieldset>

                <div class="row g-2 mb-2">
                    <div class="col-7">
                        <label for="reservation-pet-breed" class="field-label">Raça</label>
                        <input
                            id="reservation-pet-breed"
                            v-model="breed"
                            class="pet-control"
                            :class="{ 'is-invalid': errors.breed }"
                            maxlength="255"
                            required
                        />
                        <InputError :message="errors.breed?.[0]" />
                    </div>
                    <div class="col-5">
                        <label for="reservation-pet-age" class="field-label">Idade (anos)</label>
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
                        Notas para o cuidador <span class="fw-normal">(opcional)</span>
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
                        class="btn-cancel-pill"
                        :disabled="submitting"
                        @click="close"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="btn-save-pill"
                        :disabled="!isValid || submitting"
                    >
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
    color: #231209;
    border: 1.5px solid #c2410c;
    border-radius: 9px;
    padding: 0.35rem 1rem;
    font-size: 0.85rem;
    font-weight: 600;
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

.btn-cancel-pill {
    background: #ffffff;
    color: #231209;
    border: 1.5px solid #231209;
    border-radius: 14px;
    padding: 0.38rem 1.1rem;
    font-size: 0.86rem;
    font-weight: 700;
    cursor: pointer;
}

.btn-save-pill {
    background: #c2410c;
    color: #ffffff;
    border: 1.5px solid #c2410c;
    border-radius: 14px;
    padding: 0.38rem 1.15rem;
    font-size: 0.86rem;
    font-weight: 700;
    cursor: pointer;
}

.btn-save-pill:hover:not(:disabled) {
    background: var(--patudo-laranjaVivo);
    border-color: var(--patudo-laranjaVivo);
}

.btn-save-pill:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}
</style>
