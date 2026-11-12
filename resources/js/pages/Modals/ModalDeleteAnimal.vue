<script setup lang="ts">
import { ref } from 'vue';

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'confirmed'): void;
}>();

const props = defineProps<{
    show: boolean;
    animalName: string;
    deleting?: boolean;
}>();

const close = () => {
    emit('close');
};

const confirm = () => {
    emit('confirmed');
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
                <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                    <div class="modal-content rounded-4 border border-danger">
                        <!-- Header -->
                        <div class="modal-header border-bottom border-danger">
                            <h5 class="modal-title fw-bold text-danger">Remover Animal</h5>
                            <button
                                type="button"
                                class="btn-close"
                                @click="close"
                                aria-label="Fechar"
                            ></button>
                        </div>

                        <!-- Body -->
                        <div class="modal-body text-center py-4">
                            <p class="mb-0">
                                Tem a certeza que quer remover
                                <strong>{{ animalName }}</strong>?
                            </p>
                            <p class="text-muted small">Esta ação não pode ser desfeita.</p>
                        </div>

                        <!-- Footer -->
                        <div class="modal-footer border-top border-danger d-flex gap-2">
                            <button
                                type="button"
                                class="btn btn-outline-secondary rounded-pill flex-fill"
                                @click="close"
                                :disabled="deleting"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                class="btn btn-danger rounded-pill flex-fill"
                                @click="confirm"
                                :disabled="deleting"
                            >
                                {{ deleting ? 'A remover...' : 'Remover' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Teleport>
</template>
