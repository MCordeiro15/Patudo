<script setup lang="ts">
import { ref } from 'vue';
import type { AnimalFormErrors } from '@/types';
import InputError from '@/components/InputError.vue';

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'saved'): void;
}>();

const props = defineProps<{
    show: boolean;
}>();

// Form state
const name = ref('');
const species = ref('');
const breed = ref('');
const size = ref('');
const specialNeeds = ref('');

// UI state
const errors = ref<AnimalFormErrors>({});
const isLoading = ref(false);
const generalError = ref('');

const sizeOptions = [
    { value: 'small', label: 'Pequeno' },
    { value: 'medium', label: 'Médio' },
    { value: 'large', label: 'Grande' },
];

const resetForm = () => {
    name.value = '';
    species.value = '';
    breed.value = '';
    size.value = '';
    specialNeeds.value = '';
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
    isLoading.value = true;

    try {
        // Get CSRF token
        await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin',
        });

        const xsrfToken = document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1];

        const body: Record<string, string | null> = {
            name: name.value.trim(),
            species: species.value.trim(),
            breed: breed.value.trim(),
            size: size.value,
            special_needs: specialNeeds.value.trim() || null,
        };

        const response = await fetch('/api/animals', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken ?? '',
            },
            body: JSON.stringify(body),
        });

        if (response.ok) {
            // Success — emit event so parent can refresh the list
            emit('saved');
            close();
        } else {
            const errorData = await response.json().catch(() => ({}));

            if (errorData.errors) {
                // Validation errors (422)
                errors.value = errorData.errors;
            } else {
                generalError.value = errorData.message ?? 'Erro ao adicionar animal.';
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
        <template v-if="show">
            <!-- Backdrop -->
            <div class="modal-backdrop fade show"></div>

            <!-- Modal -->
            <div
                class="modal fade show d-block"
                tabindex="-1"
                role="dialog"
                @keydown.escape="close"
            >
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content rounded-4 border border-primary">
                    <!-- Header -->
                    <div class="modal-header border-bottom border-primary">
                        <h5 class="modal-title fw-bold">Adicionar Animal</h5>
                        <button
                            type="button"
                            class="btn-close"
                            @click="close"
                            aria-label="Fechar"
                        ></button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body">
                        <form @submit.prevent="submit">
                            <!-- General error -->
                            <div
                                v-if="generalError"
                                class="alert alert-danger py-2 small mb-3"
                                role="alert"
                            >
                                {{ generalError }}
                            </div>

                            <!-- Name -->
                            <div class="mb-3">
                                <label for="animal-name" class="form-label fw-semibold small">
                                    Nome *
                                </label>
                                <input
                                    id="animal-name"
                                    v-model="name"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.name }"
                                    placeholder="Ex: Rex"
                                    required
                                />
                                <InputError :message="errors.name?.[0]" />
                            </div>

                            <!-- Species -->
                            <div class="mb-3">
                                <label for="animal-species" class="form-label fw-semibold small">
                                    Espécie *
                                </label>
                                <input
                                    id="animal-species"
                                    v-model="species"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.species }"
                                    placeholder="Ex: Cão, Gato"
                                    required
                                />
                                <InputError :message="errors.species?.[0]" />
                            </div>

                            <!-- Breed -->
                            <div class="mb-3">
                                <label for="animal-breed" class="form-label fw-semibold small">
                                    Raça *
                                </label>
                                <input
                                    id="animal-breed"
                                    v-model="breed"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.breed }"
                                    placeholder="Ex: Labrador, Persa"
                                    required
                                />
                                <InputError :message="errors.breed?.[0]" />
                            </div>

                            <!-- Size -->
                            <div class="mb-3">
                                <label for="animal-size" class="form-label fw-semibold small">
                                    Porte *
                                </label>
                                <select
                                    id="animal-size"
                                    v-model="size"
                                    class="form-select"
                                    :class="{ 'is-invalid': errors.size }"
                                    required
                                >
                                    <option value="" disabled>Seleciona o porte</option>
                                    <option
                                        v-for="option in sizeOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                                <InputError :message="errors.size?.[0]" />
                            </div>

                            <!-- Special Needs -->
                            <div class="mb-3">
                                <label for="animal-special-needs" class="form-label fw-semibold small">
                                    Necessidades especiais
                                    <span class="text-muted fw-normal">(opcional)</span>
                                </label>
                                <textarea
                                    id="animal-special-needs"
                                    v-model="specialNeeds"
                                    class="form-control"
                                    rows="2"
                                    placeholder="Ex: Medicação diária, dieta especial"
                                ></textarea>
                                <InputError :message="errors.special_needs?.[0]" />
                            </div>

                            <!-- Actions -->
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary rounded-pill"
                                    @click="close"
                                    :disabled="isLoading"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    class="btn btn-primary rounded-pill"
                                    :disabled="isLoading"
                                >
                                    {{ isLoading ? 'A guardar...' : 'Adicionar' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        </template>
    </Teleport>
</template>
