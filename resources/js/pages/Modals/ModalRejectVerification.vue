<!-- Modal de recusa de verificação de um cuidador (admin). -->
<!-- Modal to reject a caregiver verification (admin). -->
<script setup lang="ts">
import { X } from '@lucide/vue';
import { ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        show: boolean;
        name?: string;
        requestedLabel?: string;
        avatarUrl?: string | null;
        processing?: boolean;
        error?: string;
    }>(),
    {
        name: 'Cuidador',
        requestedLabel: '',
        avatarUrl: null,
        processing: false,
        error: '',
    },
);

// Motivos e mensagem ainda não são guardados no servidor: o pai decide o que fazer com eles.
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'confirm', payload: { reasons: string[]; message: string }): void;
}>();

const reasonOptions = [
    'Documento ilegível',
    'A identidade não confere',
    'Fotografias inadequadas',
    'Contactos externos na descrição',
    'Outro motivo',
];

const reasons = ref<string[]>([]);
const message = ref('');
const submitted = ref(false);

watch(
    () => props.show,
    (visible) => {
        if (visible) {
            reasons.value = [];
            message.value = '';
            submitted.value = false;
        }
    },
);

const close = () => {
    if (!props.processing) emit('close');
};

const confirm = () => {
    submitted.value = true;
    if (reasons.value.length === 0) return;
    emit('confirm', {
        reasons: [...reasons.value],
        message: message.value.trim(),
    });
};
</script>

<template>
    <div
        v-if="show"
        class="patudo-modal-backdrop"
        @click.self="close"
        @keydown.escape="close"
    >
        <div class="reject-modal" role="dialog" aria-modal="true">
            <div class="modal-head">
                <h2>Recusar verificação</h2>
                <button
                    type="button"
                    class="close-btn"
                    aria-label="Fechar"
                    @click="close"
                >
                    <X :size="26" />
                </button>
            </div>

            <div class="who">
                <img v-if="avatarUrl" :src="avatarUrl" alt="" class="avatar" />
                <div v-else class="avatar"></div>
                <div class="who-text">
                    <strong>{{ name }}</strong>
                    <small>{{ requestedLabel }} · Cuidador</small>
                </div>
            </div>

            <h3>Motivo da recusa (obrigatório)</h3>
            <label v-for="reason in reasonOptions" :key="reason" class="check">
                <input v-model="reasons" type="checkbox" :value="reason" />
                <span>{{ reason }}</span>
            </label>
            <p v-if="submitted && reasons.length === 0" class="field-error">
                Seleciona pelo menos um motivo.
            </p>

            <label class="msg-label" for="reject-message"
                >Mensagem para o cuidador (opcional)</label
            >
            <textarea
                id="reject-message"
                v-model="message"
                class="msg-input"
                rows="3"
                placeholder="Explica o que deve corrigir..."
            ></textarea>
            <p class="hint">
                O cuidador recebe esta mensagem e pode voltar a enviar
                documentos.
            </p>

            <p v-if="error" class="field-error">{{ error }}</p>

            <div class="actions">
                <button
                    type="button"
                    class="btn btn-outline-primary"
                    :disabled="processing"
                    @click="close"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    class="btn btn-outline-danger"
                    :disabled="processing"
                    @click="confirm"
                >
                    {{ processing ? 'A recusar...' : 'Recusar verificação' }}
                </button>
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

.reject-modal {
    background-color: #fffaf3;
    border: 1.5px solid #f3b78a;
    border-radius: 1rem;
    padding: 1.25rem 1.5rem;
    width: 100%;
    max-width: 440px;
    color: #2e1d14;
}

.modal-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 12px;
    border-bottom: 1.5px solid #f3b78a;
}

.modal-head h2 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
}

.close-btn {
    background: none;
    border: 0;
    padding: 0;
    color: #2e1d14;
}

.who {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 16px 0 14px;
}

.avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #fed7aa;
    border: 1.5px solid #2e1d14;
    object-fit: cover;
    flex-shrink: 0;
}

.who-text {
    display: flex;
    flex-direction: column;
}

.who-text strong {
    font-size: 0.85rem;
}

.who-text small {
    font-size: 0.65rem;
    color: #7a6657;
}

h3 {
    font-size: 0.9rem;
    font-weight: 700;
    margin: 0 0 10px;
}

.check {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    margin-bottom: 10px;
}

.check input {
    width: 16px;
    height: 16px;
    accent-color: #c2410c;
}

.field-error {
    color: #dc3545;
    font-size: 0.7rem;
    margin: 0 0 8px;
}

.msg-label {
    display: block;
    font-size: 0.65rem;
    color: #7a6657;
    margin: 14px 0 4px;
}

.msg-input {
    width: 100%;
    background-color: #fff;
    border: 1.5px solid #f3b78a;
    border-radius: 10px;
    padding: 8px 10px;
    font-size: 0.75rem;
    resize: none;
}

.msg-input:focus {
    outline: 2px solid rgba(194, 65, 12, 0.35);
}

.hint {
    font-size: 0.6rem;
    color: #7a6657;
    margin: 6px 0 0;
}

.actions {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-top: 18px;
    padding-top: 16px;
    border-top: 1.5px solid #f3b78a;
}

.actions button {
    flex: 1;
    border-radius: 999px;
    padding: 9px 16px;
    font-size: 0.8rem;
    font-weight: 700;
}

.actions button:disabled {
    opacity: 0.6;
}
</style>
