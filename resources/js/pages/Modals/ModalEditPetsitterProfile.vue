<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import type { PetsitterProfile } from '@/types';

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'saved'): void;
}>();

const props = defineProps<{
    show: boolean;
    petsitter: PetsitterProfile | null;
}>();

const profilePhotoFile = ref<File | null>(null);
const profilePhotoPreview = ref<string | null>(null);
const photoInput = ref<HTMLInputElement | null>(null);
const isLoading = ref(false);

const close = () => {
    emit('close');
};

const choosePhoto = () => photoInput.value?.click();

const onPhotoChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;

    profilePhotoFile.value = file;
    profilePhotoPreview.value = URL.createObjectURL(file);
};

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            profilePhotoFile.value = null;
            profilePhotoPreview.value = props.petsitter?.profile_photo ?? null;
        }
    },
);

const submit = async () => {
    if (!props.petsitter) return;
    isLoading.value = true;

    try {
        await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin',
        });

        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        const xsrfToken = match ? decodeURIComponent(match[1]) : '';

        const body = new FormData();
        body.append('_method', 'PUT');
        if (profilePhotoFile.value) body.append('profile_photo', profilePhotoFile.value);

        const response = await fetch(`/api/petsitters/${props.petsitter.id}`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken ?? '',
            },
            body,
        });

        if (response.ok) {
            emit('saved');
            router.reload({
                onSuccess: () => {
                    close();
                },
            });
        }
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <Teleport to="body">
        <template v-if="show">
            <div class="modal-backdrop fade show"></div>

            <div
                class="modal fade show d-block"
                tabindex="-1"
                role="dialog"
                @keydown.escape="close"
            >
                <div
                    class="modal-dialog modal-dialog-centered patudo-modal-dialog"
                    role="document"
                >
                    <div
                        class="modal-content patudo-modal-content"
                    >
                        <div class="modal-header border-0 px-4 pt-4">
                            <h2 class="modal-title fs-2 fw-semibold">
                                Editar Perfil
                            </h2>

                            <button
                                type="button"
                                class="btn-close fs-3"
                                aria-label="Fechar"
                                @click="close"
                            ></button>
                        </div>

                        <form @submit.prevent="submit">
                            <div class="modal-body px-4 pt-3 pb-4">
                                <div class="text-center mb-5">
                                    <div
                                        class="patudo-avatar-circle mb-2"
                                    >
                                        <img
                                            v-if="profilePhotoPreview"
                                            :src="profilePhotoPreview"
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

                                    <input
                                        ref="photoInput"
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        class="d-none"
                                        @change="onPhotoChange"
                                    />

                                    <div>
                                        <button
                                            type="button"
                                            class="btn-patudo-avatar-pill"
                                            @click="choosePhoto"
                                        >
                                            Alterar foto
                                        </button>
                                    </div>
                                </div>

                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label
                                            for="full-name"
                                            class="form-label fs-5"
                                        >
                                            Nome Completo
                                        </label>
                                        <input
                                            id="full-name"
                                            type="text"
                                            class="form-control rounded-pill border-danger"
                                        />
                                    </div>

                                    <div class="col-md-6">
                                        <label
                                            for="location"
                                            class="form-label fs-5"
                                        >
                                            Localização / Cidade
                                        </label>
                                        <input
                                            id="location"
                                            type="text"
                                            class="form-control rounded-pill border-danger"
                                        />
                                    </div>

                                    <div class="col-md-6">
                                        <label
                                            for="phone"
                                            class="form-label fs-5"
                                        >
                                            Telemóvel / Whatsapp
                                        </label>
                                        <input
                                            id="phone"
                                            type="tel"
                                            class="form-control rounded-pill border-danger"
                                        />
                                    </div>

                                    <div class="col-12">
                                        <label
                                            for="about"
                                            class="form-label fs-5"
                                        >
                                            Sobre mim
                                        </label>
                                        <textarea
                                            id="about"
                                            rows="3"
                                            class="form-control border-danger rounded-4"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer border-0 px-4 pb-3 patudo-profile-actions">
                                <button
                                    type="button"
                                    class="btn patudo-delete-button"
                                >
                                    <svg class="patudo-trash-icon" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M3 6h18M9 6V4h6v2m-9 0 1 14h10l1-14M10 11v5m4-5v5" />
                                    </svg>
                                    Eliminar Conta
                                </button>

                                <div class="patudo-save-actions">
                                    <button
                                        type="button"
                                        class="btn patudo-cancel-button"
                                        @click="close"
                                    >
                                        Cancelar
                                    </button>

                                    <button
                                        type="submit"
                                        class="btn patudo-save-button"
                                        :disabled="isLoading"
                                    >
                                        Guardar Alterações
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>
    </Teleport>
</template>

<style scoped>
.patudo-modal-dialog {
    width: calc(100% - 2rem);
    max-width: 480px;
    margin: 1rem auto;
}

.patudo-modal-content {
    background: var(--patudo-branco);
    border: 1.5px solid var(--patudo-terracota);
    border-radius: 18px;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
    color: var(--patudo-castanhoDark);
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.patudo-modal-content :deep(.modal-header),
.patudo-modal-content :deep(.modal-footer) {
    border-color: transparent !important;
}

.patudo-modal-content .modal-header,
.patudo-modal-content .modal-body,
.patudo-modal-content .modal-footer {
    padding-inline: 1.75rem !important;
}

.patudo-modal-content .modal-title {
    font-size: 1.25rem !important;
}

.patudo-modal-content .btn-close {
    width: 30px;
    height: 30px;
    padding: 4px;
    background-size: 18px;
}

.patudo-modal-content .text-center.mb-5 {
    margin-bottom: 1rem !important;
}

.patudo-modal-content .row {
    --bs-gutter-x: 0.75rem;
    --bs-gutter-y: 0.75rem;
}

.patudo-modal-content .form-label.fs-5 {
    margin-bottom: 0.25rem;
    font-size: 0.88rem !important;
}

.patudo-modal-content .form-control {
    min-height: 0;
    padding: 0.45rem 0.7rem;
    background-color: var(--bs-white);
    font-size: 0.78rem;
}

.patudo-avatar-circle {
    width: 70px;
    height: 70px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border: 1px solid var(--patudo-castanhoDark);
    border-radius: 50%;
    background: #ffffff;
}

.patudo-avatar-img,
.patudo-avatar-placeholder {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.btn-patudo-avatar-pill {
    border: 0;
    border-radius: 9999px;
    padding: 0.3rem 1rem;
    background: var(--patudo-bege);
    color: var(--patudo-castanhoDark);
    font-size: 0.76rem;
    font-weight: 600;
}

.btn-patudo-avatar-pill:hover {
    background: var(--patudo-laranjaVivo);
    color: #ffffff;
}

.patudo-profile-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    gap: 0.55rem;
}

.patudo-save-actions {
    display: flex;
    align-items: center;
    gap: 0.55rem;
}

.patudo-profile-actions .btn {
    border-radius: 8px;
    padding: 0.4rem 0.85rem;
    font-size: 0.76rem;
    font-weight: 600;
}

.patudo-delete-button {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    border-color: #e53243;
    background-color: #e53243;
    color: #ffffff;
}

.patudo-trash-icon {
    width: 15px;
    height: 15px;
    fill: none;
    stroke: currentColor;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-width: 2;
}

.patudo-cancel-button {
    border: 1px solid var(--patudo-castanhoMid);
    background-color: transparent;
    color: var(--patudo-castanhoMid);
}

.patudo-save-button {
    border-color: var(--patudo-laranjaVivo);
    background-color: var(--patudo-laranjaVivo);
    color: #ffffff;
}

@media (max-width: 576px) {
    .patudo-modal-dialog {
        width: calc(100% - 1rem);
    }

    .patudo-profile-actions {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
