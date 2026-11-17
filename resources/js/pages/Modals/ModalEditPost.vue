<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import type { Post, PostFormErrors } from '@/types';

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'updated', post: Post): void;
}>();

const props = defineProps<{
    show: boolean;
    post: Post | null;
}>();

const title = ref('');
const description = ref('');
const photo = ref<File | null>(null);
const photoInput = ref<HTMLInputElement | null>(null);
const photoPreview = ref<string | null>(null);
const removePhoto = ref(false);
const errors = ref<PostFormErrors>({});
const generalError = ref('');
const isLoading = ref(false);

const reset = () => {
    title.value = '';
    description.value = '';
    photo.value = null;
    photoPreview.value = null;
    removePhoto.value = false;
    errors.value = {};
    generalError.value = '';
};

const close = () => {
    reset();
    emit('close');
};

watch(
    () => props.show,
    (open) => {
        if (open && props.post) {
            title.value = props.post.title ?? '';
            description.value = props.post.description ?? '';
            photo.value = null;
            photoPreview.value = props.post.photos?.[0] ?? null;
            removePhoto.value = false;
            errors.value = {};
            generalError.value = '';
        } else if (!open) {
            reset();
        }
    },
);

const onFilesChange = (event: Event) => {
    photo.value = (event.target as HTMLInputElement).files?.[0] ?? null;
    photoPreview.value = photo.value ? URL.createObjectURL(photo.value) : null;
};

const choosePhoto = () => photoInput.value?.click();

const removePhotoAction = () => {
    removePhoto.value = true;
    photo.value = null;
    photoPreview.value = null;
};

const restorePhoto = () => {
    removePhoto.value = false;
    if (props.post) {
        photoPreview.value = props.post.photos?.[0] ?? null;
    }
};

const titleLength = computed(() => title.value.length);
const descriptionLength = computed(() => description.value.length);

const submit = async () => {
    if (!props.post) return;
    errors.value = {};
    generalError.value = '';
    isLoading.value = true;

    try {
        await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });

        const xsrfToken = document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1];

        const form = new FormData();
        form.append('title', title.value.trim());
        form.append('description', description.value.trim());
        if (removePhoto.value) form.append('remove_photo', '1');
        if (photo.value) form.append('photos[]', photo.value);

        const response = await fetch(`/api/posts/${props.post.id}`, {
            method: 'PUT',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(xsrfToken ?? ''),
            },
            body: form,
        });

        const data = await response.json().catch(() => ({}));

        if (response.ok) {
            emit('updated', data.data ?? data);
            close();
        } else if (response.status === 422 && data.errors) {
            errors.value = data.errors;
        } else {
            generalError.value = data.message ?? 'Erro ao atualizar a publicação.';
        }
    } catch {
        generalError.value = 'Erro de conexão. Tente novamente.';
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div v-if="show" class="patudo-modal-backdrop">
        <div class="modal-backdrop fade show"></div>

        <div
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            @keydown.escape="close"
        >
            <div class="modal-dialog modal-dialog-centered patudo-new-post-dialog" role="document">
                <div class="modal-content patudo-new-post-content">
                    <div class="modal-header border-0 pt-3 pt-md-4">
                        <h2 class="modal-title patudo-new-post-title">Editar publicação</h2>
                        <button
                            type="button"
                            class="patudo-close-btn"
                            aria-label="Fechar"
                            @click="close"
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M18 6 6 18M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="modal-body pt-3 pb-2">
                            <div v-if="generalError" class="alert alert-danger py-2 small mb-3" role="alert">
                                {{ generalError }}
                            </div>

                            <input
                                ref="photoInput"
                                type="file"
                                accept="image/png,image/jpeg"
                                class="d-none"
                                @change="onFilesChange"
                            />
                            <div class="patudo-photo-section mb-4">
                                <button v-if="!removePhoto && photoPreview" type="button" class="patudo-photo-upload" @click="choosePhoto">
                                    <img :src="photoPreview" alt="Pré-visualização da fotografia" class="patudo-photo-preview" />
                                </button>
                                <button v-else-if="!removePhoto" type="button" class="patudo-photo-upload" @click="choosePhoto">
                                    <svg class="patudo-camera-icon" viewBox="0 0 64 64" aria-hidden="true">
                                        <path d="M18 19h8l4-6h8l4 6h5a7 7 0 0 1 7 7v23a7 7 0 0 1-7 7H17a7 7 0 0 1-7-7V26a7 7 0 0 1 7-7Z" />
                                        <circle cx="32" cy="37" r="11" />
                                    </svg>
                                    <span>Alterar foto</span>
                                </button>
                                <div v-else class="patudo-photo-removed">
                                    <svg class="patudo-trash-icon" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z" />
                                    </svg>
                                    <span>Foto removida</span>
                                    <button type="button" class="patudo-restore-btn" @click="restorePhoto">Restaurar</button>
                                </div>
                            </div>
                            <div v-if="!removePhoto && post?.photos && post.photos.length > 0" class="mb-2">
                                <button type="button" class="patudo-remove-photo-btn" @click="removePhotoAction">
                                    <svg class="patudo-small-trash" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z" />
                                    </svg>
                                    Remover foto
                                </button>
                            </div>
                            <InputError :message="errors.photos?.[0]" />

                            <div class="mb-4">
                                <label for="edit-post-title" class="form-label patudo-field-label">Título</label>
                                <input
                                    id="edit-post-title"
                                    v-model="title"
                                    type="text"
                                    maxlength="50"
                                    class="form-control patudo-post-input"
                                    :class="{ 'is-invalid': errors.title }"
                                    placeholder="Dê um título à tua publicação..."
                                    required
                                />
                                <div class="patudo-counter">{{ titleLength }}/50</div>
                                <InputError :message="errors.title?.[0]" />
                            </div>

                            <div>
                                <label for="edit-post-description" class="form-label patudo-field-label">Descrição</label>
                                <textarea
                                    id="edit-post-description"
                                    v-model="description"
                                    rows="4"
                                    maxlength="300"
                                    class="form-control patudo-post-textarea"
                                    :class="{ 'is-invalid': errors.description }"
                                    placeholder="Conta como correu o dia com o pet ou partilha novidades..."
                                    required
                                ></textarea>
                                <div class="patudo-counter">{{ descriptionLength }}/300</div>
                                <InputError :message="errors.description?.[0]" />
                            </div>
                        </div>

                        <div class="modal-footer border-0 pb-3 pb-md-4 pt-2 justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-primary patudo-action-button" @click="close">
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary patudo-action-button" :disabled="isLoading">
                                {{ isLoading ? 'A guardar...' : 'Guardar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
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

.patudo-new-post-dialog { max-width: 480px; }

.patudo-new-post-content {
    position: relative;
    background: var(--patudo-branco);
    border: 1px solid var(--patudo-terracota);
    border-radius: 18px;
    color: var(--patudo-castanhoDark);
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.patudo-new-post-title { font-size: 1.25rem; font-weight: 700; }

.patudo-close-btn {
    position: absolute;
    top: 1.25rem;
    right: 1.25rem;
    width: 30px;
    height: 30px;
    padding: 4px;
    border: 0;
    background: transparent;
}
.patudo-close-btn svg { width: 100%; height: 100%; fill: none; stroke: currentColor; stroke-linecap: round; stroke-width: 1.5; }

.patudo-new-post-content .modal-header,
.patudo-new-post-content .modal-body,
.patudo-new-post-content .modal-footer { padding-inline: 1.75rem !important; }

.patudo-photo-upload {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 180px;
    overflow: hidden;
    border: 1.5px solid var(--patudo-terracota);
    border-radius: 14px;
    background: #ffffff;
    color: #000000;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
}

.patudo-camera-icon { width: 62px; height: 62px; fill: none; stroke: currentColor; stroke-linejoin: round; stroke-width: 3; }
.patudo-photo-preview { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }

.patudo-photo-removed {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    min-height: 180px;
    border: 1.5px dashed #ccc;
    border-radius: 14px;
    background: #f9f9f9;
    color: #999;
    font-size: 0.9rem;
    font-weight: 600;
}
.patudo-trash-icon { width: 48px; height: 48px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.5; }
.patudo-restore-btn {
    margin-top: 0.25rem;
    padding: 0.25rem 0.75rem;
    border: 1px solid var(--patudo-terracota);
    border-radius: 8px;
    background: transparent;
    color: var(--patudo-terracota);
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
}
.patudo-restore-btn:hover { background: var(--patudo-terracota); color: #fff; }

.patudo-remove-photo-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0;
    border: none;
    background: transparent;
    color: #c0392b;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
}
.patudo-remove-photo-btn:hover { text-decoration: underline; }
.patudo-small-trash { width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.5; }

.patudo-field-label { margin-bottom: 0.25rem; font-size: 0.88rem; font-weight: 700; }

.patudo-post-input,
.patudo-post-textarea {
    border: 1px solid var(--patudo-terracota);
    background-color: var(--bs-white);
    color: var(--patudo-castanhoDark);
    min-height: 0;
    padding: 0.65rem 0.95rem;
    font-size: 0.86rem;
}
.patudo-post-input { border-radius: 9999px; }
.patudo-post-textarea { border-radius: 14px; resize: none; }
.patudo-post-input:focus,
.patudo-post-textarea:focus { border-color: var(--patudo-terracota); box-shadow: 0 0 0 0.2rem rgb(194 65 12 / 15%); }
.patudo-counter { margin-top: 0.1rem; text-align: right; font-size: 0.75rem; font-weight: 700; color: #000000; }
.patudo-action-button { width: 100px; height: 34px; border-radius: 13px; padding: 0 0.5rem; font-size: 0.88rem; font-weight: 600; line-height: 1; }
.btn-outline-primary.patudo-action-button { background-color: var(--bs-white); }

@media (max-width: 576px) {
    .patudo-new-post-dialog { max-width: calc(100% - 1rem); }
}
</style>
