<!-- Página Vue: ModalNewPost.vue -->
<!-- Vue page: ModalNewPost.vue -->
<script setup lang="ts">
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import type { Post, PostFormErrors } from '@/types';

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'created', post: Post): void;
}>();

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<{
    show: boolean;
}>();

const title = ref('');
const description = ref('');
const files = ref<File[]>([]);
const errors = ref<PostFormErrors>({});
const generalError = ref('');
const isLoading = ref(false);

const reset = () => {
    title.value = '';
    description.value = '';
    files.value = [];
    errors.value = {};
    generalError.value = '';
};

const close = () => {
    reset();
    emit('close');
};

// limpa o formulário de cada vez que o modal abre
watch(
    () => props.show,
    (open) => {
        if (open) reset();
    },
);

const onFilesChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    files.value = Array.from(input.files ?? []);
};

const submit = async () => {
    errors.value = {};
    generalError.value = '';
    isLoading.value = true;

    try {
        await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });

        const xsrfToken = document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1];

        // multipart/form-data: é preciso FormData por causa dos ficheiros
        const form = new FormData();
        form.append('title', title.value.trim());
        form.append('description', description.value.trim());
        files.value.forEach((file) => form.append('photos[]', file));

        const response = await fetch('/api/posts', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                // NÃO pôr Content-Type: o browser define-o com o boundary certo
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                // o cookie vem codificado, o header tem de ir descodificado
                'X-XSRF-TOKEN': decodeURIComponent(xsrfToken ?? ''),
            },
            body: form,
        });

        const data = await response.json().catch(() => ({}));

        if (response.ok) {
            emit('created', data.data ?? data);
            close();
        } else if (response.status === 422 && data.errors) {
            errors.value = data.errors;
        } else if (response.status === 403) {
            generalError.value = 'Só petsitters aprovados podem publicar.';
        } else {
            generalError.value = data.message ?? 'Erro ao criar a publicação.';
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

            <div
                class="modal fade show d-block"
                tabindex="-1"
                role="dialog"
                @keydown.escape="close"
            >
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content rounded-4 border border-primary">
                        <div class="modal-header border-bottom border-primary">
                            <h5 class="modal-title fw-bold">Nova publicação</h5>
                            <button
                                type="button"
                                class="btn-close"
                                aria-label="Fechar"
                                @click="close"
                            ></button>
                        </div>

                        <div class="modal-body">
                            <!-- Formulário para entrada de dados do utilizador. -->
                            <!-- Form for user data input. -->
                            <form @submit.prevent="submit">
                                <div
                                    v-if="generalError"
                                    class="alert alert-danger py-2 small mb-3"
                                    role="alert"
                                >
                                    {{ generalError }}
                                </div>

                                <div class="mb-3">
                                    <label
                                        for="post-title"
                                        class="form-label fw-semibold small"
                                    >
                                        Título *
                                    </label>
                                    <input
                                        id="post-title"
                                        v-model="title"
                                        type="text"
                                        maxlength="100"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.title }"
                                        placeholder="Dá um título à publicação"
                                        required
                                    />
                                    <InputError :message="errors.title?.[0]" />
                                </div>

                                <div class="mb-3">
                                    <label
                                        for="post-description"
                                        class="form-label fw-semibold small"
                                    >
                                        Descrição *
                                    </label>
                                    <textarea
                                        id="post-description"
                                        v-model="description"
                                        rows="4"
                                        maxlength="2000"
                                        class="form-control"
                                        :class="{
                                            'is-invalid': errors.description,
                                        }"
                                        placeholder="Escreve algo sobre a publicação..."
                                        required
                                    ></textarea>
                                    <InputError
                                        :message="errors.description?.[0]"
                                    />
                                </div>

                                <div class="mb-3">
                                    <label
                                        for="post-photos"
                                        class="form-label fw-semibold small"
                                    >
                                        Fotografias (até 5, jpg ou png, máx. 5
                                        MB cada)
                                    </label>
                                    <input
                                        id="post-photos"
                                        type="file"
                                        multiple
                                        accept="image/png,image/jpeg"
                                        class="form-control"
                                        :class="{ 'is-invalid': errors.photos }"
                                        @change="onFilesChange"
                                    />
                                    <InputError :message="errors.photos?.[0]" />
                                    <!-- erros por ficheiro: photos.0, photos.1, ... -->
                                    <template
                                        v-for="(msgs, key) in errors"
                                        :key="key"
                                    >
                                        <InputError
                                            v-if="
                                                String(key).startsWith(
                                                    'photos.',
                                                )
                                            "
                                            :message="msgs[0]"
                                        />
                                    </template>
                                </div>
                            </form>
                        </div>

                        <div class="modal-footer border-top border-primary">
                            <button
                                type="button"
                                class="btn btn-outline-secondary rounded-pill"
                                @click="close"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                class="btn btn-primary rounded-pill"
                                :disabled="isLoading"
                                @click="submit"
                            >
                                <span
                                    v-if="isLoading"
                                    class="spinner-border spinner-border-sm me-1"
                                    role="status"
                                ></span>
                                {{ isLoading ? 'A publicar...' : 'Publicar' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Teleport>
</template>
