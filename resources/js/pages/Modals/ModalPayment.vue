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
const cardNumberTouched = ref(false);
const expiryTouched = ref(false);
const cvvTouched = ref(false);

watch(
    () => props.show,
    (visible) => {
        if (visible) {
            cardNumber.value = '';
            expiry.value = '';
            cvv.value = '';
            submitted.value = false;
            cardNumberTouched.value = false;
            expiryTouched.value = false;
            cvvTouched.value = false;
        }
    },
);

const formattedAmount = computed(() => `${props.amount.toFixed(2).replace('.', ',')} €`);

const onCardInput = (e: Event) => {
    cardNumberTouched.value = true;
    const input = e.target as HTMLInputElement;
    const digits = input.value.replace(/\D/g, '').slice(0, 16);
    cardNumber.value = digits.replace(/(.{4})/g, '$1 ').trim();
    input.value = cardNumber.value;
};

const onExpiryInput = (e: Event) => {
    expiryTouched.value = true;
    const input = e.target as HTMLInputElement;
    const digits = input.value.replace(/\D/g, '').slice(0, 4);
    expiry.value = digits.length > 2 ? `${digits.slice(0, 2)}/${digits.slice(2)}` : digits;
    input.value = expiry.value;
};

const onCvvInput = (e: Event) => {
    cvvTouched.value = true;
    const input = e.target as HTMLInputElement;
    cvv.value = input.value.replace(/\D/g, '').slice(0, 3);
    input.value = cvv.value;
};

const cardError = computed(() => {
    const digits = cardNumber.value.replace(/\s/g, '');
    if (!digits) return 'Introduza o número do cartão.';
    return /^\d{16}$/.test(digits) ? '' : 'O número do cartão deve conter 16 algarismos.';
});
const cardValid = computed(() => !cardError.value);

const expiryError = computed(() => {
    const match = expiry.value.match(/^(\d{2})\/(\d{2})$/);
    if (!match) return 'Introduza a validade no formato MM/AA.';

    const month = Number(match[1]);
    if (month < 1 || month > 12) return 'O mês deve estar entre 01 e 12.';

    const year = 2000 + Number(match[2]);
    const now = new Date();
    if (year < now.getFullYear() || (year === now.getFullYear() && month < now.getMonth() + 1)) {
        return 'O cartão está caducado.';
    }

    return '';
});
const expiryValid = computed(() => !expiryError.value);

const cvvError = computed(() => {
    if (!cvv.value) return 'Introduza o CVV.';
    return /^\d{3}$/.test(cvv.value) ? '' : 'O CVV deve conter exatamente 3 algarismos.';
});
const cvvValid = computed(() => !cvvError.value);
const formValid = computed(() => cardValid.value && expiryValid.value && cvvValid.value);

const close = () => emit('close');

const pay = () => {
    submitted.value = true;
    if (formValid.value) emit('paid');
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
                    maxlength="19"
                    autocomplete="cc-number"
                    class="field-input mb-2"
                    :class="{ invalid: (submitted || cardNumberTouched) && !cardValid }"
                    :aria-invalid="(submitted || cardNumberTouched) && !cardValid"
                    :aria-describedby="(submitted || cardNumberTouched) && !cardValid ? 'card-number-error' : undefined"
                    @input="onCardInput"
                    @blur="cardNumberTouched = true"
                />
                <p v-if="(submitted || cardNumberTouched) && !cardValid" id="card-number-error" class="field-error" role="alert">
                    {{ cardError }}
                </p>

                <div class="d-flex gap-3">
                    <div class="flex-fill">
                        <label class="field-label" for="card-expiry">Data de Validade *</label>
                        <input
                            id="card-expiry"
                            :value="expiry"
                            type="text"
                            inputmode="numeric"
                            maxlength="5"
                            placeholder="MM/AA"
                            autocomplete="cc-exp"
                            class="field-input"
                            :class="{ invalid: (submitted || expiryTouched) && !expiryValid }"
                            :aria-invalid="(submitted || expiryTouched) && !expiryValid"
                            :aria-describedby="(submitted || expiryTouched) && !expiryValid ? 'card-expiry-error' : undefined"
                            @input="onExpiryInput"
                            @blur="expiryTouched = true"
                        />
                        <p v-if="(submitted || expiryTouched) && !expiryValid" id="card-expiry-error" class="field-error" role="alert">
                            {{ expiryError }}
                        </p>
                    </div>
                    <div class="flex-fill">
                        <label class="field-label" for="card-cvv">CVV *</label>
                        <input
                            id="card-cvv"
                            :value="cvv"
                            type="password"
                            inputmode="numeric"
                            maxlength="3"
                            autocomplete="cc-csc"
                            class="field-input"
                            :class="{ invalid: (submitted || cvvTouched) && !cvvValid }"
                            :aria-invalid="(submitted || cvvTouched) && !cvvValid"
                            :aria-describedby="(submitted || cvvTouched) && !cvvValid ? 'card-cvv-error' : undefined"
                            @input="onCvvInput"
                            @blur="cvvTouched = true"
                        />
                        <p v-if="(submitted || cvvTouched) && !cvvValid" id="card-cvv-error" class="field-error" role="alert">
                            {{ cvvError }}
                        </p>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-outline-primary btn-light border rounded-pill px-3" :disabled="processing" @click="close">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary btn-pay rounded-pill px-4" :disabled="processing || !formValid">
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

.field-error {
    color: #dc3545;
    font-size: 0.7rem;
    margin: 0.25rem 0 0;
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
