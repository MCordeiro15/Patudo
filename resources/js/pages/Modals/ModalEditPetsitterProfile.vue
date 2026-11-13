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
    petsitter: PetsitterProfile | null;
}>();

const biography = ref('');
const experience = ref('');
const serviceLocation = ref('');
const pricePerDay = ref(0);
const age = ref(0);
const sex = ref('');
const acceptedSizes = ref<string[]>([]);
const acceptedAnimalTypes = ref<string[]>([]);

const errors = ref<PetsitterFormErrors>({});
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
    { value: 'bird', label: 'Ave' },
    { value: 'other', label: 'Outro' },
];

const sexOptions = [
    { value: 'male', label: 'Masculino' },
    { value: 'female', label: 'Feminino' },
    { value: 'other', label: 'Outro' },
];

const populateForm = () => {
    if (!props.petsitter) return;
    biography.value = props.petsitter.biography ?? '';
    experience.value = props.petsitter.experience ?? '';
    serviceLocation.value = props.petsitter.service_location ?? '';
    pricePerDay.value = props.petsitter.price_per_day ?? 0;
    age.value = props.petsitter.age ?? 0;
    sex.value = props.petsitter.sex ?? '';
    acceptedSizes.value = props.petsitter.accepted_sizes ? [...props.petsitter.accepted_sizes] : [];
    acceptedAnimalTypes.value = props.petsitter.accepted_animal_types
        ? [...props.petsitter.accepted_animal_types]
        : [];
};

const resetForm = () => {
    errors.value = {};
    generalError.value = '';
    successMessage.value = '';
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

        const body: Record<string, string | number | string[] | null> = {
            biography: biography.value.trim() || null,
            experience: experience.value.trim(),
            service_location: serviceLocation.value.trim(),
            price_per_day: pricePerDay.value,
            age: age.value,
            sex: sex.value || null,
            accepted_sizes: acceptedSizes.value.length > 0 ? acceptedSizes.value : null,
            accepted_animal_types:
                acceptedAnimalTypes.value.length > 0 ? acceptedAnimalTypes.value : null,
        };

        const response = await fetch(`/api/petsitters/${props.petsitter.id}`, {
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
                generalError.value = errorData.message ?? 'Erro ao atualizar perfil.';
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
            <div class="modal-backdrop fade show"></div>

            <div class="modal fade show d-block" tabindex="-1" role="dialog" @keydown.escape="close">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
                    <div class="modal-content rounded-4 border border-primary">
                        <div class="modal-header border-bottom border-primary">
                            <h5 class="modal-title fw-bold">Editar Perfil de Petsitter</h5>
                            <button type="button" class="btn-close" @click="close" aria-label="Fechar"></button>
                        </div>

                        <div class="modal-body">
                            <form @submit.prevent="submit">
                                <div v-if="generalError" class="alert alert-danger py-2 small mb-3" role="alert">
                                    {{ generalError }}
                                </div>

                                <div v-if="successMessage" class="alert alert-success py-2 small mb-3" role="alert">
                                    {{ successMessage }}
                                </div>

                                <div class="mb-3">
                                    <label for="petsitter-biography" class="form-label fw-semibold small">
                                        Biografia
                                    </label>
                                    <textarea id="petsitter-biography" v-model="biography" rows="3" class="form-control" :class="{ 'is-invalid': errors.biography }" placeholder="Conte um pouco sobre si..."></textarea>
                                    <InputError :message="errors.biography?.[0]" />
                                </div>

                                <div class="mb-3">
                                    <label for="petsitter-experience" class="form-label fw-semibold small">
                                        Experiência *
                                    </label>
                                    <textarea id="petsitter-experience" v-model="experience" rows="2" class="form-control" :class="{ 'is-invalid': errors.experience }" placeholder="Descreva a sua experiência com animais..." required></textarea>
                                    <InputError :message="errors.experience?.[0]" />
                                </div>

                                <div class="mb-3">
                                    <label for="petsitter-service-location" class="form-label fw-semibold small">
                                        Localização de serviço *
                                    </label>
                                    <input id="petsitter-service-location" v-model="serviceLocation" type="text" class="form-control" :class="{ 'is-invalid': errors.service_location }" placeholder="Ex: Ponta Delgada" required/>
                                    <InputError :message="errors.service_location?.[0]" />
                                </div>

                                <div class="mb-3">
                                    <label for="petsitter-price" class="form-label fw-semibold small">
                                        Preço por dia (€) *
                                    </label>
                                    <input id="petsitter-price" v-model.number="pricePerDay" type="number" step="0.01" min="0" class="form-control" :class="{ 'is-invalid': errors.price_per_day }" required/>
                                    <InputError :message="errors.price_per_day?.[0]" />
                                </div>

                                <div class="mb-3">
                                    <label for="petsitter-age" class="form-label fw-semibold small">
                                        Idade *
                                    </label>
                                    <input id="petsitter-age" v-model.number="age" type="number" min="18" max="99" class="form-control" :class="{ 'is-invalid': errors.age }" required/>
                                    <InputError :message="errors.age?.[0]" />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Sexo</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <label v-for="option in sexOptions" :key="option.value" class="form-check d-flex align-items-center">
                                            <input class="form-check-input" type="radio" name="petsitter-sex" :value="option.value" v-model="sex"/>
                                            <span class="form-check-label">{{ option.label }}</span>
                                        </label>
                                    </div>
                                    <InputError :message="errors.sex?.[0]" />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Portes aceites</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <label v-for="option in sizeOptions" :key="option.value" class="form-check d-flex align-items-center">
                                            <input class="form-check-input" type="checkbox" :value="option.value" :checked="acceptedSizes.includes(option.value)" @change="
                                                    toggleArrayValue(
                                                        acceptedSizes,
                                                        option.value,
                                                        (val) => { acceptedSizes = val },
                                                    )
                                                "
                                            />
                                            <span class="form-check-label">{{ option.label }}</span>
                                        </label>
                                    </div>
                                    <InputError :message="errors.accepted_sizes?.[0]" />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Tipos de animal aceites</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <label v-for="option in animalTypeOptions" :key="option.value" class="form-check d-flex align-items-center">
                                            <input class="form-check-input" type="checkbox" :value="option.value" :checked="acceptedAnimalTypes.includes(option.value)" @change="
                                                    toggleArrayValue(
                                                        acceptedAnimalTypes,
                                                        option.value,
                                                        (val) => { acceptedAnimalTypes = val },
                                                    )
                                                "
                                            />
                                            <span class="form-check-label">{{ option.label }}</span>
                                        </label>
                                    </div>
                                    <InputError :message="errors.accepted_animal_types?.[0]" />
                                </div>
                            </form>
                        </div>

                        <div class="modal-footer border-top border-primary">
                            <button type="button" class="btn btn-outline-secondary rounded-pill" @click="close">
                                Cancelar
                            </button>
                            <button type="button" class="btn btn-primary rounded-pill" :disabled="isLoading" @click="submit">
                                <span v-if="isLoading" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                {{ isLoading ? 'A guardar...' : 'Guardar alterações' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Teleport>
</template>
