<script setup lang="ts">
defineProps<{
    show: boolean;
}>();

const emit = defineEmits(['close']);
</script>

<template>
    <div
        v-if="show"
        class="patudo-petsitter-overlay"
        @click.self="emit('close')"
    >
        <div
            class="patudo-petsitter-card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="petsitter-form-title"
        >
            <button
                type="button"
                class="patudo-close-btn"
                aria-label="Fechar"
                @click="emit('close')"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12" />
                </svg>
            </button>

            <header class="patudo-petsitter-header">
                <h2 id="petsitter-form-title">Quero ser petsitter</h2>
            </header>

            <form @submit.prevent>
                <section class="patudo-form-section">
                    <h3>Informações</h3>

                    <div class="patudo-grid patudo-grid-two">
                        <div class="patudo-field patudo-field-wide">
                            <label for="petsitter-name">Nome Completo *</label>
                            <input
                                id="petsitter-name"
                                type="text"
                                placeholder="Insira o seu nome completo"
                            />
                        </div>

                        <div class="patudo-field">
                            <label for="petsitter-sex">Sexo</label>
                            <select id="petsitter-sex">
                                <option value="" selected>Selecionar</option>
                                <option>Masculino</option>
                                <option>Feminino</option>
                                <option>Outro</option>
                            </select>
                        </div>

                        <div class="patudo-field patudo-field-wide">
                            <label for="petsitter-service"
                                >Tipo de serviço *</label
                            >
                            <select id="petsitter-service">
                                <option value="" selected>Selecionar</option>
                                <option>Pet sitting</option>
                                <option>Passeio</option>
                            </select>
                        </div>

                        <div class="patudo-field">
                            <label for="petsitter-age">Idade *</label>
                            <input
                                id="petsitter-age"
                                type="number"
                                min="18"
                                placeholder="Insira a sua idade"
                            />
                        </div>
                    </div>

                    <div class="patudo-grid patudo-grid-three">
                        <div class="patudo-field">
                            <label for="petsitter-location"
                                >Localização *</label
                            >
                            <input
                                id="petsitter-location"
                                type="text"
                                placeholder="Insira a sua cidade"
                            />
                        </div>
                        <div class="patudo-field">
                            <label for="petsitter-postcode"
                                >Código Postal *</label
                            >
                            <input
                                id="petsitter-postcode"
                                type="text"
                                placeholder="Insira o seu código postal"
                            />
                        </div>
                        <div class="patudo-field">
                            <label for="petsitter-availability"
                                >Horário Disponível *</label
                            >
                            <input id="petsitter-availability" type="date" />
                        </div>
                    </div>
                </section>

                <section class="patudo-form-section">
                    <h3>Preferências e Condições</h3>

                    <div
                        class="patudo-grid patudo-grid-three patudo-preferences"
                    >
                        <fieldset class="patudo-fieldset">
                            <legend>Portes Aceites *</legend>
                            <label class="patudo-check"
                                ><input type="checkbox" /> Porte Pequeno</label
                            >
                            <label class="patudo-check"
                                ><input type="checkbox" /> Porte Médio</label
                            >
                            <label class="patudo-check"
                                ><input type="checkbox" /> Porte Grande</label
                            >
                        </fieldset>

                        <div class="patudo-field">
                            <label for="petsitter-animal-type"
                                >Tipos de Animais *</label
                            >
                            <select id="petsitter-animal-type">
                                <option value="" selected>Nenhuma</option>
                                <option>Cão</option>
                                <option>Gato</option>
                                <option>Ave</option>
                            </select>
                        </div>

                        <div class="patudo-field">
                            <label for="petsitter-price"
                                >Preço por hora € *</label
                            >
                            <select id="petsitter-price">
                                <option value="" selected>Selecionar</option>
                                <option>5 €</option>
                                <option>10 €</option>
                                <option>15 €</option>
                            </select>
                        </div>
                    </div>
                </section>

                <section class="patudo-form-section">
                    <div class="patudo-field">
                        <label for="petsitter-about">Sobre ti *</label>
                        <textarea
                            id="petsitter-about"
                            rows="3"
                            maxlength="500"
                            placeholder="A tua experiência com animais, qualificações ou rotinas dos teus serviços"
                        ></textarea>
                        <span class="patudo-counter">0/500</span>
                    </div>
                </section>

                <section class="patudo-form-section patudo-document-section">
                    <label class="patudo-document-label"
                        >Verificação de Identidade *</label
                    >
                    <label class="patudo-document-upload">
                        <input
                            type="file"
                            accept="image/png,image/jpeg,application/pdf"
                            class="d-none"
                        />
                        <span class="patudo-upload-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                        </span>
                        <span>
                            <strong>Submete o teu Cartão de Cidadão</strong>
                            <small>JPG, PNG ou PDF, até 10 MB.</small>
                        </span>
                    </label>
                </section>

                <footer class="patudo-petsitter-footer">
                    <span>* Campos obrigatórios</span>
                    <div class="patudo-footer-actions">
                        <button
                            type="button"
                            class="btn patudo-cancel-button"
                            @click="emit('close')"
                        >
                            Cancelar
                        </button>
                        <button type="submit" class="btn patudo-submit-button">
                            Submeter
                        </button>
                    </div>
                </footer>
            </form>
        </div>
    </div>
</template>

<style scoped>
.patudo-petsitter-overlay {
    position: fixed;
    z-index: 10000000;
    inset: 0;
    overflow-y: auto;
    padding: 1rem;
    background: rgb(0 0 0 / 45%);
}

.patudo-petsitter-card {
    position: relative;
    width: 100%;
    max-width: 480px;
    margin: 0 auto;
    border: 1.5px solid var(--patudo-terracota);
    border-radius: 18px;
    background: var(--patudo-branco);
    color: var(--patudo-castanhoDark);
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.patudo-close-btn {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 28px;
    height: 28px;
    padding: 4px;
    border: 0;
    background: transparent;
    color: var(--patudo-castanhoDark);
}

.patudo-close-btn svg {
    width: 100%;
    height: 100%;
    fill: none;
    stroke: currentColor;
    stroke-linecap: round;
    stroke-width: 1.7;
}

.patudo-petsitter-header {
    padding: 1rem 1.25rem 0.75rem;
    border-bottom: 1px solid rgb(122 102 87 / 30%);
}

.patudo-petsitter-header h2,
.patudo-form-section h3 {
    margin: 0;
    font-weight: 700;
}

.patudo-petsitter-header h2 {
    font-size: 1.25rem;
}
.patudo-form-section {
    padding: 0.75rem 1.25rem 0;
}
.patudo-form-section h3 {
    margin-bottom: 0.65rem;
    font-size: 1rem;
}

.patudo-grid {
    display: grid;
    gap: 0.65rem;
}
.patudo-grid-two {
    grid-template-columns: 1.4fr 0.65fr;
}
.patudo-grid-three {
    grid-template-columns: repeat(3, 1fr);
    margin-top: 0.65rem;
}
.patudo-field {
    min-width: 0;
}

.patudo-field label,
.patudo-fieldset legend,
.patudo-document-label {
    display: block;
    margin-bottom: 0.25rem;
    font-size: 0.76rem;
    font-weight: 700;
}

.patudo-field input,
.patudo-field select,
.patudo-field textarea {
    width: 100%;
    border: 1px solid var(--patudo-terracota);
    border-radius: 8px;
    background: var(--bs-white);
    color: var(--patudo-castanhoDark);
    font: inherit;
    font-size: 0.7rem;
}

.patudo-field input,
.patudo-field select {
    height: 31px;
    padding: 0 0.6rem;
}

.patudo-field select {
    appearance: none;
    padding-right: 2rem;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232e1d14' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.65rem center;
    background-size: 0.85rem;
}
.patudo-field textarea {
    min-height: 70px;
    padding: 0.55rem 0.6rem;
    resize: none;
}
.patudo-field input::placeholder,
.patudo-field textarea::placeholder {
    color: var(--patudo-castanhoMid);
}

.patudo-fieldset {
    margin: 0;
    padding: 0;
    border: 0;
}
.patudo-check {
    display: flex !important;
    align-items: center;
    gap: 0.4rem;
    margin-bottom: 0.35rem;
    font-size: 0.7rem !important;
    font-weight: 500 !important;
}
.patudo-check input {
    width: 15px;
    height: 15px;
    accent-color: var(--patudo-terracota);
}
.patudo-counter {
    display: block;
    margin-top: 0.15rem;
    text-align: right;
    font-size: 0.65rem;
    font-weight: 700;
}

.patudo-document-section {
    padding-bottom: 0.75rem;
}
.patudo-document-upload {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    cursor: pointer;
}
.patudo-upload-icon {
    display: grid;
    width: 48px;
    height: 48px;
    flex: 0 0 48px;
    place-items: center;
    border: 2px dashed var(--patudo-terracota);
    border-radius: 10px;
    color: var(--patudo-terracota);
}

.patudo-upload-icon svg {
    width: 23px;
    height: 23px;
    fill: none;
    stroke: currentColor;
    stroke-linecap: round;
    stroke-width: 2;
}
.patudo-document-upload strong {
    display: block;
    font-size: 0.72rem;
    font-weight: 500;
}
.patudo-document-upload small {
    display: block;
    margin-top: 0.15rem;
    color: var(--patudo-castanhoMid);
    font-size: 0.6rem;
}

.patudo-petsitter-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.7rem 1.25rem;
    border-top: 1px solid rgb(122 102 87 / 30%);
    font-size: 0.65rem;
}
.patudo-footer-actions {
    display: flex;
    gap: 0.5rem;
}
.patudo-footer-actions .btn {
    height: 31px;
    padding: 0 0.9rem;
    border-radius: 8px;
    font-size: 0.72rem;
    font-weight: 600;
}
.patudo-cancel-button {
    border: 1px solid var(--patudo-terracota);
    background: var(--bs-white);
    color: var(--patudo-terracota);
}
.patudo-submit-button {
    border: 1px solid var(--patudo-laranjaVivo);
    background: var(--patudo-laranjaVivo);
    color: #ffffff;
}

@media (max-width: 520px) {
    .patudo-petsitter-overlay {
        padding: 0.5rem;
    }
    .patudo-grid-two,
    .patudo-grid-three {
        grid-template-columns: 1fr;
    }
    .patudo-grid-three {
        margin-top: 0.65rem;
    }
    .patudo-preferences {
        gap: 0.8rem;
    }
    .patudo-petsitter-footer {
        align-items: flex-start;
        flex-direction: column;
    }
    .patudo-footer-actions {
        align-self: flex-end;
    }
}
</style>

