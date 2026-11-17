<<script setup lang="ts">
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';

type FormErrors = Record<string, string[]>;

defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'saved'): void;
}>();

const name = ref('');
const sex = ref('');
const service = ref('');
const age = ref('');
const location = ref('');
const postcode = ref('');
const availability = ref('');
const sizes = ref<string[]>([]);
const animalType = ref('');
const price = ref('');
const about = ref('');
const documentFile = ref<File | null>(null);
const documentInput = ref<HTMLInputElement | null>(null);

const errors = ref<FormErrors>({});
const isLoading = ref(false);
const generalError = ref('');

const sexOptions = [
    { value: 'masculino', label: 'Masculino' },
    { value: 'feminino', label: 'Feminino' },
    { value: 'outro', label: 'Outro' },
];

const serviceOptions = [
    { value: 'pet-sitting', label: 'Pet sitting' },
    { value: 'passeio', label: 'Passeio' },
];

const sizeOptions = [
    { value: 'pequeno', label: 'Porte Pequeno' },
    { value: 'medio', label: 'Porte Médio' },
    { value: 'grande', label: 'Porte Grande' },
];

const animalOptions = [
    { value: 'cao', label: 'Cão' },
    { value: 'gato', label: 'Gato' },
    { value: 'ave', label: 'Ave' },
];

const priceOptions = [
    { value: '5', label: '5 €' },
    { value: '10', label: '10 €' },
    { value: '15', label: '15 €' },
];

const handleDocumentChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) return;

    const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];

    if (!allowedTypes.includes(file.type)) {
        generalError.value = 'Seleciona um ficheiro JPG, PNG ou PDF.';
        target.value = '';
        return;
    }

    if (file.size > 10 * 1024 * 1024) {
        generalError.value = 'O ficheiro não pode ultrapassar 10 MB.';
        target.value = '';
        return;
    }

    documentFile.value = file;
    generalError.value = '';
};

const removeDocument = () => {
    documentFile.value = null;

    if (documentInput.value) {
        documentInput.value.value = '';
    }
};

const resetForm = () => {
    name.value = '';
    sex.value = '';
    service.value = '';
    age.value = '';
    location.value = '';
    postcode.value = '';
    availability.value = '';
    sizes.value = [];
    animalType.value = '';
    price.value = '';
    about.value = '';

    removeDocument();

    errors.value = {};
    generalError.value = '';
};

const close = () => {
    resetForm();
    emit('close');
};

const submit = async () => {
    errors.value = {};
    generalError.value = '';

    // O required nativo não serve para grupos de checkboxes nem para o ficheiro
    if (sizes.value.length === 0) {
        errors.value.sizes = ['Seleciona pelo menos um porte.'];
    }

    if (!documentFile.value) {
        errors.value.identity_document = [
            'Submete o teu Cartão de Cidadão.',
        ];
    }

    if (Object.keys(errors.value).length > 0) return;

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

        // multipart por causa do ficheiro (sem Content-Type: o browser define-o)
        const body = new FormData();
        body.append('name', name.value.trim());
        body.append('sex', sex.value);
        body.append('service', service.value);
        body.append('age', age.value);
        body.append('location', location.value.trim());
        body.append('postcode', postcode.value.trim());
        body.append('availability', availability.value);
        sizes.value.forEach((s) => body.append('sizes[]', s));
        body.append('animal_type', animalType.value);
        body.append('price_per_hour', price.value);
        body.append('about', about.value.trim());
        body.append('identity_document', documentFile.value as File);

        const response = await fetch('/api/petsitters', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(xsrfToken ?? ''),
            },
            body,
        });

        if (response.ok) {
            emit('saved');
            close();
        } else {
            const errorData = await response.json().catch(() => ({}));

            if (errorData.errors) {
                errors.value = errorData.errors;
            } else {
                generalError.value =
                    errorData.message ?? 'Erro ao submeter candidatura.';
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
            aria-labelledby="modal-petsitter-title"
        >
            <div
                class="d-flex justify-content-between align-items-center flex-shrink-0 px-4 py-3 border-bottom border-primary"
            >
                <h2 id="modal-petsitter-title" class="h5 fw-bold mb-0">
                    Quero ser petsitter
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

                    <span class="d-block fw-bold small mb-2">Informações</span>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-8">
                            <label
                                for="petsitter-name"
                                class="form-label fw-bold small mb-1"
                            >
                                Nome completo *
                            </label>

                            <input
                                id="petsitter-name"
                                v-model="name"
                                type="text"
                                class="form-control patudo-field rounded-3"
                                :class="{ 'is-invalid': errors.name }"
                                placeholder="Insira o seu nome completo"
                                autocomplete="name"
                                required
                                aria-required="true"
                            />

                            <InputError :message="errors.name?.[0]" />
                        </div>

                        <div class="col-12 col-md-4">
                            <label
                                for="petsitter-sex"
                                class="form-label fw-bold small mb-1"
                            >
                                Sexo
                            </label>

                            <select
                                id="petsitter-sex"
                                v-model="sex"
                                class="form-select patudo-field rounded-3"
                                :class="[
                                    { 'is-invalid': errors.sex },
                                    sex ? 'text-body' : 'text-secondary',
                                ]"
                            >
                                <option value="">Selecionar</option>

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
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-8">
                            <label
                                for="petsitter-service"
                                class="form-label fw-bold small mb-1"
                            >
                                Tipo de serviço *
                            </label>

                            <select
                                id="petsitter-service"
                                v-model="service"
                                class="form-select patudo-field rounded-3"
                                :class="[
                                    { 'is-invalid': errors.service },
                                    service ? 'text-body' : 'text-secondary',
                                ]"
                                required
                                aria-required="true"
                            >
                                <option value="" disabled>Selecionar</option>

                                <option
                                    v-for="option in serviceOptions"
                                    :key="option.value"
                                    :value="option.value"
                                    class="text-body"
                                >
                                    {{ option.label }}
                                </option>
                            </select>

                            <InputError :message="errors.service?.[0]" />
                        </div>

                        <div class="col-12 col-md-4">
                            <label
                                for="petsitter-age"
                                class="form-label fw-bold small mb-1"
                            >
                                Idade *
                            </label>

                            <input
                                id="petsitter-age"
                                v-model="age"
                                type="number"
                                min="18"
                                step="1"
                                class="form-control patudo-field rounded-3"
                                :class="{ 'is-invalid': errors.age }"
                                placeholder="Ex.: 25"
                                required
                                aria-required="true"
                            />

                            <InputError :message="errors.age?.[0]" />
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-4">
                            <label
                                for="petsitter-location"
                                class="form-label fw-bold small mb-1"
                            >
                                Localização *
                            </label>

                            <input
                                id="petsitter-location"
                                v-model="location"
                                type="text"
                                class="form-control patudo-field rounded-3"
                                :class="{ 'is-invalid': errors.location }"
                                placeholder="Insira a sua cidade"
                                autocomplete="address-level2"
                                required
                                aria-required="true"
                            />

                            <InputError :message="errors.location?.[0]" />
                        </div>

                        <div class="col-12 col-md-4">
                            <label
                                for="petsitter-postcode"
                                class="form-label fw-bold small mb-1"
                            >
                                Código postal *
                            </label>

                            <input
                                id="petsitter-postcode"
                                v-model="postcode"
                                type="text"
                                inputmode="numeric"
                                pattern="\d{4}-\d{3}"
                                title="Formato: 0000-000"
                                class="form-control patudo-field rounded-3"
                                :class="{ 'is-invalid': errors.postcode }"
                                placeholder="0000-000"
                                autocomplete="postal-code"
                                required
                                aria-required="true"
                            />

                            <InputError :message="errors.postcode?.[0]" />
                        </div>

                        <div class="col-12 col-md-4">
                            <label
                                for="petsitter-availability"
                                class="form-label fw-bold small mb-1"
                            >
                                Horário disponível *
                            </label>

                            <input
                                id="petsitter-availability"
                                v-model="availability"
                                type="date"
                                class="form-control patudo-field rounded-3"
                                :class="{ 'is-invalid': errors.availability }"
                                required
                                aria-required="true"
                            />

                            <InputError :message="errors.availability?.[0]" />
                        </div>
                    </div>

                    <span class="d-block fw-bold small mb-2">
                        Preferências e condições
                    </span>

                    <div class="row g-2 mb-3">
                        <fieldset class="col-12 col-md-4">
                            <legend class="fw-bold small mb-1">
                                Portes aceites *
                            </legend>

                            <div
                                v-for="option in sizeOptions"
                                :key="option.value"
                                class="form-check"
                            >
                                <input
                                    :id="`petsitter-size-${option.value}`"
                                    v-model="sizes"
                                    type="checkbox"
                                    class="form-check-input"
                                    :value="option.value"
                                />

                                <label
                                    :for="`petsitter-size-${option.value}`"
                                    class="form-check-label small"
                                >
                                    {{ option.label }}
                                </label>
                            </div>

                            <InputError
                                :message="(errors.sizes ?? errors['sizes.0'])?.[0]"
                            />
                        </fieldset>

                        <div class="col-12 col-md-4">
                            <label
                                for="petsitter-animal-type"
                                class="form-label fw-bold small mb-1"
                            >
                                Tipos de animais *
                            </label>

                            <select
                                id="petsitter-animal-type"
                                v-model="animalType"
                                class="form-select patudo-field rounded-3"
                                :class="[
                                    { 'is-invalid': errors.animal_type },
                                    animalType
                                        ? 'text-body'
                                        : 'text-secondary',
                                ]"
                                required
                                aria-required="true"
                            >
                                <option value="" disabled>Selecionar</option>

                                <option
                                    v-for="option in animalOptions"
                                    :key="option.value"
                                    :value="option.value"
                                    class="text-body"
                                >
                                    {{ option.label }}
                                </option>
                            </select>

                            <InputError :message="errors.animal_type?.[0]" />
                        </div>

                        <div class="col-12 col-md-4">
                            <label
                                for="petsitter-price"
                                class="form-label fw-bold small mb-1"
                            >
                                Preço por hora (€) *
                            </label>

                            <select
                                id="petsitter-price"
                                v-model="price"
                                class="form-select patudo-field rounded-3"
                                :class="[
                                    { 'is-invalid': errors.price_per_hour },
                                    price ? 'text-body' : 'text-secondary',
                                ]"
                                required
                                aria-required="true"
                            >
                                <option value="" disabled>Selecionar</option>

                                <option
                                    v-for="option in priceOptions"
                                    :key="option.value"
                                    :value="option.value"
                                    class="text-body"
                                >
                                    {{ option.label }}
                                </option>
                            </select>

                            <InputError :message="errors.price_per_hour?.[0]" />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label
                            for="petsitter-about"
                            class="form-label fw-bold small mb-1"
                        >
                            Sobre ti *
                        </label>

                        <textarea
                            id="petsitter-about"
                            v-model="about"
                            rows="4"
                            maxlength="500"
                            class="form-control patudo-field patudo-textarea rounded-4 bg-white"
                            :class="{ 'is-invalid': errors.about }"
                            placeholder="A tua experiência com animais, qualificações ou rotinas dos teus serviços"
                            required
                            aria-required="true"
                        ></textarea>

                        <div class="text-end small text-secondary mt-1">
                            {{ about.length }}/500
                        </div>

                        <InputError :message="errors.about?.[0]" />
                    </div>

                    <div class="mb-1">
                        <span class="d-block fw-bold small mb-2">
                            Verificação de identidade *
                        </span>

                        <div class="d-flex align-items-center gap-3">
                            <label
                                for="petsitter-document"
                                class="petsitter-upload d-flex align-items-center justify-content-center flex-shrink-0"
                            >
                                <svg
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

                            <div class="min-w-0">
                                <p class="small text-secondary mb-1 text-truncate">
                                    {{
                                        documentFile
                                            ? documentFile.name
                                            : 'Submete o teu Cartão de Cidadão'
                                    }}
                                </p>

                                <p class="small text-secondary mb-0">
                                    JPG, PNG ou PDF, até 10 MB.
                                </p>

                                <button
                                    v-if="documentFile"
                                    type="button"
                                    class="petsitter-remove mt-1"
                                    @click="removeDocument"
                                >
                                    Remover ficheiro
                                </button>
                            </div>
                        </div>

                        <input
                            id="petsitter-document"
                            ref="documentInput"
                            type="file"
                            accept="image/png, image/jpeg, application/pdf"
                            class="d-none"
                            @change="handleDocumentChange"
                        />

                        <InputError :message="errors.identity_document?.[0]" />
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
                            class="btn btn-outline-primary rounded-pill px-4 fw-semibold"
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
                            {{ isLoading ? 'A submeter...' : 'Submeter' }}
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

.min-w-0 {
    min-width: 0;
}

.petsitter-upload {
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

.petsitter-upload:hover {
    background-color: #fbf2ec;
}

.petsitter-remove {
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
