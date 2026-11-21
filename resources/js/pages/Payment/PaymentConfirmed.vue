<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Bell, LogOut, MessageCircle, UserRound, Dog } from '@lucide/vue';
import { ref, onMounted } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import { useNotifications } from '@/composables/useNotifications';
import ModalNotifications from '@/pages/Modals/ModalNotifications.vue';

defineOptions({ layout: false });

// O id do pagamento vem do URL: /payment-confirmed?payment=ID
const paymentId = new URLSearchParams(window.location.search).get('payment');

function getXsrf(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

function apiFetch(url: string, options: RequestInit = {}) {
    return fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getXsrf(),
            ...options.headers,
        },
    });
}

type PaymentData = {
    id: number;
    reservation_id: number;
    amount: number;
    payment_method: string;
    created_at: string;
    pet_name?: string;
    pet_photo?: string | null;
    reservation_start?: string;
    reservation_end?: string;
};

// Mock até existir backend de pagamentos (/api/payments/{id})
const mockPayment: PaymentData = {
    id: 12345,
    reservation_id: 1001,
    amount: 0,
    payment_method: 'Cartão de crédito',
    created_at: new Date().toISOString(),
    pet_name: 'Nome do Pet',
    pet_photo: null,
    reservation_start: new Date().toISOString(),
    reservation_end: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString(),
};

const payment = ref<PaymentData>(mockPayment);
const showNotifications = ref(false);
const { unreadCount } = useNotifications();

const formatDate = (iso?: string) => (iso ? new Date(iso).toLocaleDateString('pt-PT') : '--');

const loadPayment = async () => {
    if (!paymentId) return;
    try {
        const res = await apiFetch(`/api/payments/${paymentId}`);
        if (res.ok) payment.value = (await res.json()).data;
    } catch {
        // mantém o mock
    }
};

onMounted(() => {
    void loadPayment();
});
</script>

<template>
    <Head title="Pagamento Confirmado" />

    <main class="min-vh-100 py-4 patudo-page">
        <div class="container">

            <nav
                class="navbar navbar-expand-lg bg-white border border-primary rounded-4 px-3 px-md-4"
            >
                <a
                    class="navbar-brand fw-bold fs-3 d-flex align-items-center gap-2"
                    href="/"
                >
                    <img
                        :src="logoPatudo"
                        alt="Patudo"
                        width="70"
                        height="70"
                    />
                    <span>Patudo</span>
                </a>

                <div class="nav nav-pills border rounded-pill p-1 mx-auto">
                    <a class="nav-link rounded-pill px-4" href="/">Início</a>
                    <a class="nav-link rounded-pill px-4" href="/orders">Pedidos</a>
                    <a class="nav-link rounded-pill px-4" href="/favorites">Favoritos</a>
                    <a class="nav-link active rounded-pill px-4" href="/reservations">Reservas</a>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <a href="/messages" class="text-dark"
                        ><MessageCircle :size="25"
                    /></a>
                    <button type="button" class="text-dark bg-transparent border-0 p-0 position-relative" @click="showNotifications = true">
                        <Bell :size="25" />
                        <span v-if="unreadCount > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </span>
                    </button>
                    <button
                        type="button"
                        class="text-dark p-0 border-0 bg-none"
                    >
                        <UserRound :size="25" />
                    </button>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="btn btn-outline-danger rounded-pill d-flex align-items-center gap-2 px-3"
                    >
                        <LogOut :size="16" />
                        <span>Sair</span>
                    </Link>
                </div>
            </nav>

            <h1 class="text-center fw-medium confirmed-title mt-5 mb-4">Pagamento Confirmado</h1>

            <div class="text-center mb-4">
                <svg class="paw" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <ellipse cx="38" cy="82" rx="15" ry="22" transform="rotate(-25 38 82)" />
                    <ellipse cx="78" cy="38" rx="16" ry="25" transform="rotate(-8 78 38)" />
                    <ellipse cx="122" cy="38" rx="16" ry="25" transform="rotate(8 122 38)" />
                    <ellipse cx="162" cy="82" rx="15" ry="22" transform="rotate(25 162 82)" />
                    <circle cx="100" cy="135" r="55" />
                    <path d="M70 135 L92 157 L132 112" fill="none" stroke="#000" stroke-width="16" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>

            <p class="text-center fw-medium fs-5 mb-4">O pagamento foi aprovado com sucesso!</p>

            <section class="details-card mx-auto">
                <div class="px-4 pt-4 pb-3">
                    <h2 class="fs-2 fw-medium mb-4">Detalhes da transição</h2>

                    <div class="detail-row">
                        <span>id da reserva:</span>
                        <span class="value-pill"></span>
                    </div>
                    <div class="detail-row">
                        <span>Valor pago:</span>
                        <span>{{ payment.amount.toFixed(2).replace('.', ',') }}€</span>
                    </div>
                    <div class="detail-row">
                        <span>Data:</span>
                        <span>{{ formatDate(payment.created_at) }}</span>
                    </div>
                    <div class="detail-row">
                        <span>Forma de pagamento:</span>
                        <span class="value-pill"></span>
                    </div>
                </div>

                <div class="divider px-4 py-3">
                    <p class="mb-3">Agradecemos a tua confiança!</p>
                    <p class="mb-0">Aguarda a resposta do Pet Sitter para saber se a reserva foi aceite.</p>
                </div>

                <div class="divider px-4 py-3 d-flex align-items-center gap-3">
                    <div class="pet-avatar rounded-circle border border-dark overflow-hidden d-flex align-items-center justify-content-center flex-shrink-0">
                        <img v-if="payment.pet_photo" :src="payment.pet_photo" :alt="payment.pet_name" class="w-100 h-100 object-fit-cover" />
                        <Dog v-else :size="26" class="text-primary" />
                    </div>
                    <div class="lh-sm">
                        <p class="mb-1 fw-medium">{{ payment.pet_name }}</p>
                        <small class="fs-6">{{ formatDate(payment.reservation_start) }} - {{ formatDate(payment.reservation_end) }}</small>
                    </div>
                </div>
            </section>

            <div class="text-center mt-5">
                <a href="/orders" class="btn btn-primary rounded-pill px-5 py-2">Ir para meus pedidos</a>
            </div>
        </div>

        <ModalNotifications
            :show="showNotifications"
            @close="showNotifications = false"
            @mark-as-read="unreadCount = Math.max(0, unreadCount - 1)"
        />
    </main>
</template>

<style scoped>
.patudo-page {
    background-color: #fffaf3;
}

.nav-pills .nav-link.active {
    background-color: var(--patudo-terracota, #c2410c) !important;
    color: #ffffff !important;
}

.nav-pills .nav-link:not(.active) {
    color: #212529;
}

.nav-pills .nav-link:not(.active):hover {
    color: var(--patudo-terracota, #c2410c) !important;
}

.confirmed-title {
    font-size: 2.75rem;
}

.paw {
    width: 190px;
    height: 190px;
    fill: var(--patudo-terracota, #c2410c);
}

.details-card {
    max-width: 700px;
    background-color: #fed7aa;
    border: 1px solid var(--patudo-terracota, #c2410c);
    border-radius: 2rem;
    overflow: hidden;
    font-weight: 500;
    font-size: 1.15rem;
}

.detail-row {
    display: grid;
    grid-template-columns: 55% 45%;
    align-items: center;
    margin-bottom: 0.6rem;
}

.value-pill {
    display: inline-block;
    background-color: #fff;
    border: 1px solid #ccc;
    border-radius: 999px;
    padding: 0 0.75rem;
    height: 1.1rem;
    width: 100%;
    max-width: 210px;
}

.divider {
    border-top: 1px solid var(--patudo-terracota, #c2410c);
}

.pet-avatar {
    width: 48px;
    height: 48px;
    background-color: #fffaf3;
}
</style>
