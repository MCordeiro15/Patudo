<script setup lang="ts">
import { ref } from 'vue';

defineProps<{
    show: boolean;
    petsitterId?: number | string;
}>();

const emit = defineEmits(['close', 'submit']);

const reasons = [
    'Comportamento inadequado',
    'Informação falsa no perfil',
    'Não cumpriu a reserva',
    'Maus tratos a animais',
    'Assédio ou linguagem ofensiva',
    'Outro',
];

const selectedReason = ref<string>('Assédio ou linguagem ofensiva');
const description = ref<string>('');

const handleClose = () => {
    emit('close');
};

const handleSubmit = () => {
    if (!selectedReason.value) return;

    emit('submit', {
        reason: selectedReason.value,
        description: description.value.trim(),
    });
    emit('close');
};
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="patudo-modal-backdrop" @click.self="handleClose">
            <div class="patudo-modal-card" role="dialog" aria-modal="true">
                <button type="button" class="patudo-close-btn" aria-label="Fechar" @click="handleClose">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                        <path d="M18 6L6 18M6 6l12 12" />
                    </svg>
                </button>

                <div class="mb-3">
                    <h2 class="patudo-modal-title">Denunciar pet sitter</h2>
                    <p class="patudo-modal-subtitle">
                        Ajuda-nos a manter a comunidade Patudo segura.
                    </p>
                </div>

                <form @submit.prevent="handleSubmit">
                     <div class="mb-3">
                        <label class="patudo-section-label d-block mb-2">
                            Motivo da denúncia
                        </label>

                        <div class="d-flex flex-column gap-1 ps-2">
                            <label v-for="reason in reasons" :key="reason" class="patudo-radio-option">
                                <input v-model="selectedReason" type="radio" name="report_reason" :value="reason" class="patudo-radio-input"/>
                                <span class="patudo-radio-text">{{ reason }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="report-description" class="patudo-section-label d-block mb-2">
                            Descrição (opcional)
                        </label>
                        <textarea id="report-description" v-model="description" rows="3" class="patudo-textarea" placeholder="Descreve brevemente o que aconteceu..."></textarea>
                    </div>

                    <div class="patudo-modal-actions">
                        <button type="button" class="btn-patudo-outline" @click="handleClose">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-patudo-solid" :disabled="!selectedReason">
                            Enviar denúncia
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
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

.patudo-modal-card {
    position: relative;
    width: 100%;
    max-width: 480px;
    background-color: #fff8f1;
    border: 1.5px solid #c4450c;
    border-radius: 18px;
    padding: 1.5rem 1.75rem;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.patudo-close-btn {
    position: absolute;
    top: 1.25rem;
    right: 1.25rem;
    background: transparent;
    border: none;
    color: #2b211a;
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.15s ease;
}

.patudo-close-btn:hover {
    opacity: 0.65;
}

.patudo-modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #231810;
    margin: 0 0 0.25rem 0;
}

.patudo-modal-subtitle {
    font-size: 0.85rem;
    color: #8c7a6b;
    margin: 0;
}

.patudo-section-label {
    font-size: 0.88rem;
    font-weight: 700;
    color: #231810;
}

.patudo-radio-option {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    cursor: pointer;
    user-select: none;
    padding: 2px 0;
}

.patudo-radio-input {
    appearance: none;
    -webkit-appearance: none;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    border: 1.5px solid #9ca3af;
    background-color: #ffffff;
    display: grid;
    place-content: center;
    margin: 0;
    cursor: pointer;
    flex-shrink: 0;
    transition: border-color 0.15s ease;
}

.patudo-radio-input::before {
    content: "";
    width: 8px;
    height: 8px;
    border-radius: 50%;
    transform: scale(0);
    transition: transform 0.12s ease-in-out;
    background-color: #dc2626;
}

.patudo-radio-input:checked {
    border-color: #dc2626;
}

.patudo-radio-input:checked::before {
    transform: scale(1);
}

.patudo-radio-text {
    font-size: 0.86rem;
    color: #5c4938;
}

.patudo-textarea {
    width: 100%;
    background-color: #ffffff;
    border: none;
    border-radius: 14px;
    padding: 0.75rem 1rem;
    font-size: 0.86rem;
    color: #231810;
    resize: none;
    outline: none;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
}

.patudo-textarea::placeholder {
    color: #9e8e80;
}

.patudo-textarea:focus {
    box-shadow: 0 0 0 2px rgba(196, 69, 12, 0.25);
}

.patudo-modal-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 0.65rem;
    margin-top: 1.25rem;
}

.btn-patudo-outline {
    background-color: #ffffff;
    color: var(--patudo-terracota);
    border: 1px solid var(--patudo-terracota);
    border-radius: 12px;
    padding: 0.55rem 1.75rem;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.btn-patudo-outline:hover {
    background-color: #fdf2ea;
}

.btn-patudo-solid {
    background-color: #c4450c;
    color: #ffffff;
    border: 1px solid #c4450c;
    border-radius: 12px;
    padding: 0.55rem 1.35rem;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.btn-patudo-solid:hover:not(:disabled) {
    background-color: #a83908;
}

.btn-patudo-solid:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
