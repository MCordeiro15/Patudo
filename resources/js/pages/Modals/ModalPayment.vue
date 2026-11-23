<!-- Modal de confirmação de pagamento da reserva (cartão de crédito). -->
<!-- Booking payment confirmation modal (credit card). -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        show: boolean;
        // Descrição do serviço, ex: "Serviço de Pet Sitting - Petsitter João"
        serviceLabel?: string;
        amount?: number;
        processing?: boolean;
    }>(),
    {
        serviceLabel: 'Serviço de Pet Sitting',
        amount: 0,
        processing: false,
    },
);

// 'paid' não leva dados do cartão: o pai decide o que fazer (chamar API, redirecionar...).
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'paid'): void;
}>();

const cardNumber = ref('');
const expiry = ref('');
const cvv = ref('');
const submitted = ref(false);

watch(
    () => props.show,
    (visible) => {
        if (visible) {
            cardNumber.value = '';
            expiry.value = '';
            cvv.value = '';
            submitted.value = false;
        }
    },
);

const formattedAmount = computed(() => `${props.amount.toFixed(2).replace('.', ',')} €`);

const onCardInput = (e: Event) => {
    const digits = (e.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 16);
    cardNumber.value = digits.replace(/(.{4})/g, '$1 ').trim();
};

const onExpiryInput = (e: Event) => {
    const digits = (e.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 4);
    expiry.value = digits.length > 2 ? `${digits.slice(0, 2)}/${digits.slice(2)}` : digits;
};

const onCvvInput = (e: Event) => {
    cvv.value = (e.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 4);
};

const cardValid = computed(() => cardNumber.value.replace(/\s/g, '').length === 16);
const expiryValid = computed(() => {
    const match = expiry.value.match(/^(\d{2})\/(\d{2})$/);
    return !!match && Number(match[1]) >= 1 && Number(match[1]) <= 12;
});
const cvvValid = computed(() => cvv.value.length >= 3);

const close = () => emit('close');

const pay = () => {
    submitted.value = true;
    if (cardValid.value && expiryValid.value && cvvValid.value) emit('paid');
};
</script>

<template>
    <div v-if="show" class="patudo-modal-backdrop" @click.self="close" @keydown.escape="close">
        <div class="payment-modal" role="dialog" aria-modal="true">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 fw-bold mb-0">Confirmação de Pagamento</h2>
                <button type="button" class="btn-close" aria-label="Fechar" @click="close"></button>
            </div>

            <form class="payment-box" novalidate @submit.prevent="pay">
                <p class="fw-bold small mb-2">{{ serviceLabel }}</p>

                <div class="d-flex justify-content-between align-items-center pb-2 box-divider">
                    <span class="small">Montante a pagar:</span>
                    <span class="fw-bold fs-5">{{ formattedAmount }}</span>
                </div>

                <p class="fw-bold small mt-2 mb-2">Cartão de Crédito</p>

                <label class="field-label" for="card-number">Número do Cartão *</label>
                <input
                    id="card-number"
                    :value="cardNumber"
                    type="text"
                    inputmode="numeric"
                    autocomplete="cc-number"
                    class="field-input mb-2"
                    :class="{ invalid: submitted && !cardValid }"
                    @input="onCardInput"
                />

                <div class="d-flex gap-3">
                    <div class="flex-fill">
                        <label class="field-label" for="card-expiry">Data de Validade *</label>
                        <input
                            id="card-expiry"
                            :value="expiry"
                            type="text"
                            inputmode="numeric"
                            placeholder="MM/AA"
                            autocomplete="cc-exp"
                            class="field-input"
                            :class="{ invalid: submitted && !expiryValid }"
                            @input="onExpiryInput"
                        />
                    </div>
                    <div class="flex-fill">
                        <label class="field-label" for="card-cvv">CVV *</label>
                        <input
                            id="card-cvv"
                            :value="cvv"
                            type="password"
                            inputmode="numeric"
                            autocomplete="cc-csc"
                            class="field-input"
                            :class="{ invalid: submitted && !cvvValid }"
                            @input="onCvvInput"
                        />
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-outline-primary btn-light border rounded-pill px-3" :disabled="processing" @click="close">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary btn-pay rounded-pill px-4" :disabled="processing">
                        {{ processing ? 'A pagar...' : 'Pagar' }}
                    </button>
                </div>
            </form>
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

.payment-modal {
    background-color: #fffaf3;
    border-radius: 1rem;
    padding: 1rem;
    width: 100%;
    max-width: 440px;
}

.payment-box {
    background-color: #fed7aa;
    border: 1px solid var(--patudo-terracota, #c2410c);
    border-radius: 0.75rem;
    padding: 0.75rem 0.9rem 0.9rem;
}

.box-divider {
    border-bottom: 1px solid var(--patudo-terracota, #c2410c);
}

.field-label {
    display: block;
    font-size: 0.7rem;
    margin-bottom: 0.15rem;
}

.field-input {
    width: 100%;
    background-color: #fff;
    border: 1px solid var(--patudo-terracota, #c2410c);
    border-radius: 0.25rem;
    height: 1.8rem;
    padding: 0 0.5rem;
    font-size: 0.85rem;
}

.field-input:focus {
    outline: 2px solid rgba(194, 65, 12, 0.35);
}

.field-input.invalid {
    border-color: #dc3545;
    outline: 1px solid #dc3545;
}

.btn-pay {
    background-color: var(--patudo-terracota, #c2410c);
    color: #fff;
    border: none;
}

.btn-pay:hover {
    color: #fff;
    opacity: 0.9;
}
</style>
