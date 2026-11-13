<!-- Página Vue: ModalPetDetails.vue -->
<!-- Vue page: ModalPetDetails.vue -->
<script setup lang="ts">
export interface PetDetails {
    id?: number | string;
    name?: string;
    type?: string;
    species?: string;
    breed?: string;
    age?: string;
    size?: string;
    sex?: string;
    weight?: string;
    care?: string;
    medication?: string;
    aggressiveness?: string;
    walks?: string;
    about?: string;
    photos?: string[];
}

defineProps<{
    show: boolean;
    pet?: PetDetails;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const handleClose = () => {
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
                <button
                    type="button"
                    class="patudo-close-btn"
                    aria-label="Fechar"
                    @click="handleClose"
                >
                    <svg
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.6"
                        stroke-linecap="round"
                    >
                        <path d="M18 6L6 18M6 6l12 12" />
                    </svg>
                </button>

                <div class="mb-3">
                    <span class="patudo-pet-tag d-block">Ficha Animal</span>
                    <h2 class="patudo-pet-name">
                        {{ pet?.name || 'Nome do animal' }}
                    </h2>
                </div>

                <div class="row g-3 mb-3">
                    <div v-for="index in [0, 1]" :key="index" class="col-6">
                        <div class="patudo-photo-box">
                            <img
                                v-if="pet?.photos?.[index]"
                                :src="pet.photos[index]"
                                :alt="`Foto ${index + 1} de ${pet?.name || 'animal'}`"
                                class="patudo-pet-photo"
                            />
                            <svg
                                v-else
                                viewBox="0 0 100 100"
                                class="patudo-dog-placeholder"
                                aria-hidden="true"
                            >
                                <circle
                                    cx="50"
                                    cy="50"
                                    r="46"
                                    fill="#fbe2c4"
                                    stroke="#231810"
                                    stroke-width="1.2"
                                />
                                <path
                                    d="M30 22 C20 22, 18 42, 26 48 C30 42, 34 32, 34 25 Z"
                                    fill="#d9934c"
                                    stroke="#231810"
                                    stroke-width="1.5"
                                />
                                <path
                                    d="M70 22 C80 22, 82 42, 74 48 C70 42, 66 32, 66 25 Z"
                                    fill="#d9934c"
                                    stroke="#231810"
                                    stroke-width="1.5"
                                />
                                <path
                                    d="M72 78 C84 74, 88 58, 82 52 C78 58, 76 66, 70 70 Z"
                                    fill="#e5a967"
                                    stroke="#231810"
                                    stroke-width="1.5"
                                />
                                <path
                                    d="M32 54 C28 68, 28 84, 34 90 L66 90 C72 84, 72 68, 68 54 Z"
                                    fill="#e5a967"
                                    stroke="#231810"
                                    stroke-width="1.5"
                                />
                                <path
                                    d="M41 58 C39 70, 41 84, 44 90 L56 90 C59 84, 61 70, 59 58 Z"
                                    fill="#f7dec2"
                                />
                                <path
                                    d="M36 66 L36 90 M43 72 L43 90 M64 66 L64 90 M57 72 L57 90"
                                    stroke="#231810"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                />
                                <path
                                    d="M36 52 Q50 58 64 52"
                                    fill="none"
                                    stroke="#1d70b8"
                                    stroke-width="4"
                                    stroke-linecap="round"
                                />
                                <circle
                                    cx="50"
                                    cy="56"
                                    r="2.5"
                                    fill="#d1d5db"
                                    stroke="#231810"
                                    stroke-width="1"
                                />
                                <path
                                    d="M32 32 C32 18, 68 18, 68 32 C68 46, 60 52, 50 52 C40 52, 32 46, 32 32 Z"
                                    fill="#e5a967"
                                    stroke="#231810"
                                    stroke-width="1.5"
                                />
                                <ellipse
                                    cx="50"
                                    cy="42"
                                    rx="10"
                                    ry="7.5"
                                    fill="#f7dec2"
                                />
                                <path
                                    d="M39 32 Q42 29 45 32"
                                    fill="none"
                                    stroke="#231810"
                                    stroke-width="1.6"
                                    stroke-linecap="round"
                                />
                                <path
                                    d="M55 32 Q58 29 61 32"
                                    fill="none"
                                    stroke="#231810"
                                    stroke-width="1.6"
                                    stroke-linecap="round"
                                />
                                <ellipse
                                    cx="50"
                                    cy="39"
                                    rx="3.5"
                                    ry="2.5"
                                    fill="#231810"
                                />
                                <path
                                    d="M47 45 C47 50, 53 50, 53 45 Z"
                                    fill="#ef4444"
                                    stroke="#231810"
                                    stroke-width="1"
                                />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="patudo-details-box">
                    <div class="row g-0">
                        <div class="col-5 patudo-col-divider pe-2">
                            <h3 class="patudo-box-heading">
                                Informações do animal
                            </h3>

                            <div class="row g-2">
                                <div class="col-6">
                                    <span class="patudo-info-label"
                                        >Tipo de animal</span
                                    >
                                    <span class="patudo-info-value">{{
                                        pet?.type || pet?.species || 'Cão'
                                    }}</span>
                                </div>
                                <div class="col-6">
                                    <span class="patudo-info-label">Raça</span>
                                    <span class="patudo-info-value">{{
                                        pet?.breed || '—'
                                    }}</span>
                                </div>
                                <div class="col-6">
                                    <span class="patudo-info-label">Idade</span>
                                    <span class="patudo-info-value">{{
                                        pet?.age || '—'
                                    }}</span>
                                </div>
                                <div class="col-6">
                                    <span class="patudo-info-label">Porte</span>
                                    <span class="patudo-info-value">{{
                                        pet?.size || '—'
                                    }}</span>
                                </div>
                                <div class="col-6">
                                    <span class="patudo-info-label">Sexo</span>
                                    <span class="patudo-info-value">{{
                                        pet?.sex || '—'
                                    }}</span>
                                </div>
                                <div class="col-6">
                                    <span class="patudo-info-label">Peso</span>
                                    <span class="patudo-info-value">{{
                                        pet?.weight || '—'
                                    }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-3 patudo-col-divider px-2">
                            <h3 class="patudo-box-heading">Cuidados</h3>

                            <div class="mb-2">
                                <span class="patudo-info-label">Cuidados</span>
                                <span class="patudo-info-value">{{
                                    pet?.care || 'nenhuns'
                                }}</span>
                            </div>

                            <div>
                                <span class="patudo-info-label">Medicação</span>
                                <span class="patudo-info-value">{{
                                    pet?.medication || 'nenhuma'
                                }}</span>
                            </div>
                        </div>

                        <div class="col-4 ps-2">
                            <h3 class="patudo-box-heading">
                                Comportamento e Rotina
                            </h3>

                            <div class="mb-2">
                                <span class="patudo-info-label"
                                    >Agressividade</span
                                >
                                <span class="patudo-info-value">{{
                                    pet?.aggressiveness || 'não'
                                }}</span>
                            </div>

                            <div>
                                <span class="patudo-info-label">Passeio</span>
                                <span class="patudo-info-value">{{
                                    pet?.walks || '1x por dia'
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="patudo-about-section mt-3">
                        <h3 class="patudo-about-heading">Sobre o animal</h3>
                        <p class="patudo-about-text">
                            {{ pet?.about || 'Sem descrição adicional.' }}
                        </p>
                    </div>
                </div>
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
    max-width: 580px;
    background-color: #fff8f1;
    border: 1.5px solid #c4450c;
    border-radius: 18px;
    padding: 1.4rem 1.6rem;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.patudo-close-btn {
    position: absolute;
    top: 1.25rem;
    right: 1.25rem;
    background: transparent;
    border: none;
    color: #231810;
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

.patudo-pet-tag {
    font-size: 0.85rem;
    font-weight: 700;
    color: #231810;
    margin-bottom: 0.15rem;
}

.patudo-pet-name {
    font-size: 1.35rem;
    font-weight: 700;
    color: #231810;
    margin: 0;
}

.patudo-photo-box {
    width: 100%;
    height: 112px;
    background-color: var(--patudo-branco);
    border: 1px solid var(--patudo-terracota);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.patudo-pet-photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.patudo-dog-placeholder {
    width: 66px;
    height: 66px;
}

.patudo-details-box {
    background-color: var(--patudo-branco);
    border: 1px solid var(--patudo-terracota);
    border-radius: 10px;
    padding: 0.9rem 1rem;
}

.patudo-col-divider {
    border-right: 1px solid #d98a6c;
}

.patudo-box-heading {
    font-size: 0.74rem;
    font-weight: 700;
    color: #231810;
    margin: 0 0 0.55rem 0;
    line-height: 1.2;
}

.patudo-info-label {
    display: block;
    font-size: 0.64rem;
    font-weight: 700;
    color: #231810;
    line-height: 1.25;
}

.patudo-info-value {
    display: block;
    font-size: 0.64rem;
    font-weight: 500;
    color: #3d3229;
    line-height: 1.3;
    word-break: break-word;
}

.patudo-about-section {
    padding-top: 0.25rem;
}

.patudo-about-heading {
    font-size: 0.78rem;
    font-weight: 700;
    color: #231810;
    padding-bottom: 0.3rem;
    margin-bottom: 0.45rem;
    border-bottom: 1px solid #d98a6c;
}

.patudo-about-text {
    font-size: 0.68rem;
    font-weight: 600;
    color: #231810;
    margin: 0;
    line-height: 1.4;
}
</style>
