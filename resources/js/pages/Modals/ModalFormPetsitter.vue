<script setup lang="ts">
defineProps<{
    show: boolean;
}>();

const emit = defineEmits(['close']);
</script>

<template>
    <div
        v-if="show"
        class="modal fade show d-block"
        tabindex="-1"
        style="background-color: rgba(0, 0, 0, 0.5); position: fixed; inset: 0; z-index: 10000000;"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-4 rounded-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="modal-title fw-bold">Título do Modal</h5>
                    <button
                        type="button"
                        class="btn-close"
                        @click="emit('close')"
                    ></button>
                </div>

                <div class="modal-body">
                    <p>Conteúdo do modal...</p>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button
                        type="button"
                        class="btn btn-secondary rounded-pill"
                        @click="emit('close')"
                    >
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
