<script setup lang="ts">
import { computed, ref, watch } from 'vue';

export interface ReviewPetsitterInfo {
    id?: number | string;
    name?: string;
    avatar_url?: string | null;
    service_summary?: string;
}

export interface ReviewPayload {
    petsitter_id?: number | string;
    rating: number;
    tags: string[];
    comment: string;
}

const props = defineProps<{
    show: boolean;
    petsitter?: ReviewPetsitterInfo;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit', payload: ReviewPayload): void;
}>();

const rating = ref(4);
const hoverRating = ref(0);
const selectedTags = ref<string[]>([]);
const comment = ref('');
const isLoading = ref(false);
const errorMessage = ref('');

const ratingLabels: Record<number, string> = {
    1: 'Má',
    2: 'Razoável',
    3: 'Suficiente',
    4: 'Boa',
    5: 'Excelente',
};

const currentRatingLabel = computed(() => {
    const active = hoverRating.value || rating.value;
    return ratingLabels[active] || '';
});

const highlightOptions = [
    { id: 'pro', label: 'Bom profissional' },
    { id: 'punctual', label: 'Pontual e disponível' },
    { id: 'meds', label: 'Seguiu a medicação / rotinas' },
    { id: 'communication', label: 'Boa comunicação' },
    { id: 'care', label: 'Cuidadoso com o animal' },
    { id: 'clean', label: 'Espaço limpo e seguro' },
];

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            rating.value = 4;
            hoverRating.value = 0;
            selectedTags.value = [];
            comment.value = '';
            errorMessage.value = '';
        }
    }
);

const toggleTag = (label: string) => {
    const index = selectedTags.value.indexOf(label);
    if (index >= 0) {
        selectedTags.value.splice(index, 1);
    } else {
        selectedTags.value.push(label);
    }
};

const handleClose = () => {
    emit('close');
};

const handleSubmit = () => {
    if (rating.value < 1) {
        errorMessage.value = 'Por favor, selecione uma classificação de 1 a 5 estrelas.';
        return;
    }

    isLoading.value = true;
    emit('submit', {
        petsitter_id: props.petsitter?.id,
        rating: rating.value,
        tags: [...selectedTags.value],
        comment: comment.value.trim(),
    });
    isLoading.value = false;
    emit('close');
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="patudo-modal-backdrop"
            @click.self="handleClose"
            @keydown.escape="handleClose"
        >
            <div class="patudo-modal-card" role="dialog" aria-modal="true">
                <!-- Cabeçalho com linha divisória a toda a largura -->
                <div class="patudo-modal-header">
                    <h2 class="patudo-modal-title">
                        Avalie a sua experiência com este Petsitter
                    </h2>
                    <button
                        type="button"
                        class="patudo-close-btn"
                        aria-label="Fechar"
                        @click="handleClose"
                    >
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                            <path d="M18 6L6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Corpo -->
                <form class="patudo-modal-body" @submit.prevent="handleSubmit">
                    <!-- Pílula de resumo do Petsitter e Serviço -->
                    <div class="patudo-sitter-pill mb-3">
                        <div class="patudo-sitter-left">
                            <div class="patudo-sitter-avatar">
                                <img
                                    v-if="petsitter?.avatar_url"
                                    :src="petsitter.avatar_url"
                                    alt="Avatar do Petsitter"
                                    class="patudo-avatar-img"
                                />
                                <svg
                                    v-else
                                    viewBox="0 0 100 100"
                                    class="patudo-avatar-svg"
                                    aria-hidden="true"
                                >
                                    <circle cx="50" cy="50" r="48" fill="#ffffff" />
                                    <path d="M35 72 C35 66, 65 66, 65 72 L68 85 L32 85 Z" fill="#f3c69f" />
                                    <path d="M27 52 C27 25, 73 25, 73 52 L76 64 L24 64 Z" fill="#f25c66" />
                                    <path d="M34 42 C34 30, 66 30, 66 42 C66 57, 58 66, 50 66 C42 66, 34 57, 34 42 Z" fill="#fbd3ae" />
                                    <path d="M32 42 C32 24, 68 24, 68 42 C60 39, 42 44, 32 42 Z" fill="#f25c66" />
                                </svg>
                            </div>
                            <span class="patudo-sitter-name">
                                {{ petsitter?.name || 'Nome do Pet Sitter' }}
                            </span>
                        </div>

                        <div class="patudo-sitter-divider"></div>

                        <div class="patudo-sitter-service">
                            {{ petsitter?.service_summary || 'Cuidado de cães · 12/11 – 15/11/2026' }}
                        </div>
                    </div>

                    <!-- Classificação por Estrelas -->
                    <div class="mb-3">
                        <label class="patudo-section-label d-block mb-1">
                            Como foi a tua experiência? *
                        </label>
                        <div class="d-flex align-items-center gap-2">
                            <div
                                class="patudo-stars-group"
                                @mouseleave="hoverRating = 0"
                            >
                                <button
                                    v-for="star in 5"
                                    :key="star"
                                    type="button"
                                    class="patudo-star-btn"
                                    :aria-label="`${star} estrelas`"
                                    @mouseenter="hoverRating = star"
                                    @click="rating = star"
                                >
                                    <!-- Estrela Preenchida -->
                                    <svg
                                        v-if="star <= (hoverRating || rating)"
                                        width="30"
                                        height="30"
                                        viewBox="0 0 24 24"
                                        fill="#ECC94B"
                                        stroke="#ECC94B"
                                        stroke-width="1.5"
                                        stroke-linejoin="round"
                                    >
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                    </svg>
                                    <!-- Estrela Vazia com contorno escuro -->
                                    <svg
                                        v-else
                                        width="30"
                                        height="30"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="#1A120B"
                                        stroke-width="1.6"
                                        stroke-linejoin="round"
                                    >
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                    </svg>
                                </button>
                            </div>
                            <span class="patudo-rating-text">
                                {{ currentRatingLabel }}
                            </span>
                        </div>
                        <p v-if="errorMessage" class="patudo-error-text mt-1 mb-0">
                            {{ errorMessage }}
                        </p>
                    </div>

                    <!-- Checkboxes em 2 Colunas -->
                    <div class="mb-3">
                        <label class="patudo-section-label d-block mb-2">
                            Justifique assinalando ou não os campos seguintes:
                        </label>
                        <div class="row g-2">
                            <div
                                v-for="item in highlightOptions"
                                :key="item.id"
                                class="col-12 col-sm-6"
                            >
                                <label class="patudo-check-item">
                                    <input
                                        type="checkbox"
                                        class="d-none"
                                        :checked="selectedTags.includes(item.label)"
                                        @change="toggleTag(item.label)"
                                    />
                                    <span
                                        class="patudo-custom-checkbox"
                                        :class="{ 'is-checked': selectedTags.includes(item.label) }"
                                    >
                                        <svg
                                            v-if="selectedTags.includes(item.label)"
                                            width="12"
                                            height="12"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="#231810"
                                            stroke-width="2.5"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <polyline points="20 6 9 17 4 12" />
                                        </svg>
                                    </span>
                                    <span class="patudo-check-label">{{ item.label }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Sobre a experiência (Opcional) -->
                    <div class="mb-1">
                        <label for="review-comment" class="patudo-section-label d-block mb-1">
                            Sobre a experiência (Opcional)
                        </label>
                        <textarea
                            id="review-comment"
                            v-model="comment"
                            rows="3"
                            maxlength="500"
                            class="patudo-textarea"
                            placeholder="A tua experiência com o serviço do Petsitter, por exemplo"
                        ></textarea>
                        <div class="text-end">
                            <span class="patudo-char-counter">{{ comment.length }}/500</span>
                        </div>
                    </div>

                    <!-- Rodapé com linha divisória a toda a largura -->
                    <div class="patudo-modal-footer">
                        <span class="patudo-required-note">* Campos obrigatórios</span>
                        <div class="d-flex align-items-center gap-2">
                            <button
                                type="button"
                                class="btn-patudo-outline-pill"
                                @click="handleClose"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="btn-patudo-solid-pill"
                                :disabled="isLoading"
                            >
                                {{ isLoading ? 'A submeter...' : 'Submeter' }}
                            </button>
                        </div>
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
    overflow: hidden;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
    font-family: 'Plus Jakarta Sans', sans-serif;
}

/* Cabeçalho com linha divisória completa */
.patudo-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.15rem 1.5rem;
    border-bottom: 1.5px solid #c4450c;
}

.patudo-modal-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #231810;
    margin: 0;
}

.patudo-close-btn {
    background: transparent;
    border: none;
    color: #231810;
    cursor: pointer;
    padding: 2px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.15s ease;
}

.patudo-close-btn:hover {
    opacity: 0.65;
}

.patudo-modal-body {
    padding: 1.15rem 1.5rem 0 1.5rem;
}

/* Pílula de resumo do Petsitter */
.patudo-sitter-pill {
    display: flex;
    align-items: center;
    background-color: #ffffff;
    border: 1px solid #c4450c;
    border-radius: 9999px;
    padding: 0.35rem 1rem;
    gap: 0.75rem;
}

.patudo-sitter-left {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.patudo-sitter-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 1px solid #c4450c;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #fff8f1;
}

.patudo-avatar-img,
.patudo-avatar-svg {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.patudo-sitter-name {
    font-size: 0.76rem;
    font-weight: 700;
    color: #231810;
}

.patudo-sitter-divider {
    width: 1px;
    height: 22px;
    background-color: #d98a6c;
    flex-shrink: 0;
}

.patudo-sitter-service {
    flex: 1;
    text-align: center;
    font-size: 0.74rem;
    color: #6e5239;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Estrelas */
.patudo-section-label {
    font-size: 0.82rem;
    font-weight: 700;
    color: #231810;
}

.patudo-stars-group {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
}

.patudo-star-btn {
    background: transparent;
    border: none;
    padding: 0;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    transition: transform 0.1s ease;
}

.patudo-star-btn:hover {
    transform: scale(1.08);
}

.patudo-rating-text {
    font-size: 0.85rem;
    font-weight: 700;
    color: #3d3229;
    margin-left: 0.35rem;
}

/* Checkboxes personalizadas */
.patudo-check-item {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    cursor: pointer;
    user-select: none;
}

.patudo-custom-checkbox {
    width: 16px;
    height: 16px;
    border: 1.4px solid #231810;
    border-radius: 4px;
    background-color: transparent;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background-color 0.15s ease;
}

.patudo-custom-checkbox.is-checked {
    background-color: #ffffff;
}

.patudo-check-label {
    font-size: 0.76rem;
    font-weight: 600;
    color: #5a4635;
}

/* Textarea e contador */
.patudo-textarea {
    width: 100%;
    background-color: #ffffff;
    border: 1.2px solid #c4450c;
    border-radius: 10px;
    padding: 0.55rem 0.8rem;
    font-size: 0.78rem;
    color: #231810;
    resize: none;
    outline: none;
}

.patudo-textarea::placeholder {
    color: #6e5239;
    font-weight: 600;
}

.patudo-textarea:focus {
    box-shadow: 0 0 0 2px rgba(196, 69, 12, 0.18);
}

.patudo-char-counter {
    font-size: 0.68rem;
    font-weight: 700;
    color: #6e5239;
}

.patudo-error-text {
    font-size: 0.74rem;
    color: #dc2626;
}

/* Rodapé com linha divisória a toda a largura */
.patudo-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 0.5rem -1.5rem 0 -1.5rem;
    padding: 0.85rem 1.5rem;
    border-top: 1.5px solid #c4450c;
}

.patudo-required-note {
    font-size: 0.7rem;
    color: #3d3229;
}

.btn-patudo-outline-pill {
    background-color: #ffffff;
    color: var(--patudo-terracota);
    border: 1.3px solid var(--patudo-terracota);
    border-radius: 9999px;
    padding: 0.38rem 1.15rem;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.btn-patudo-outline-pill:hover {
    background-color: #fdf2ea;
}

.btn-patudo-solid-pill {
    background-color: #c4450c;
    color: #ffffff;
    border: 1.3px solid #c4450c;
    border-radius: 9999px;
    padding: 0.38rem 1.6rem;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.btn-patudo-solid-pill:hover:not(:disabled) {
    background-color: #a83908;
}

.btn-patudo-solid-pill:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
