<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { LogOut, UserRound, Dog } from '@lucide/vue';
import { ref, onMounted } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import AdminNavigationMenu from '@/components/AdminNavigationMenu.vue';
import HeaderNotificationsChat from '@/components/HeaderNotificationsChat.vue';

defineOptions({ layout: false });

// O id da reserva paga vem do URL: /payment-confirmed?booking=ID
// (o pagamento é fictício e fica na própria reserva, por isso lê-se a reserva)
const bookingId = new URLSearchParams(window.location.search).get('booking');

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

// Valores vazios até a reserva carregar (sem dados inventados)
const emptyPayment: PaymentData = {
    id: 0,
    reservation_id: 0,
    amount: 0,
    payment_method: '',
    created_at: '',
    pet_name: '',
    pet_photo: null,
    reservation_start: '',
    reservation_end: '',
};

// Horas de entrada/saída da reserva, para "Datas e horário"
const entranceHour = ref<string | null>(null);
const exitHour = ref<string | null>(null);
const loadError = ref('');

const payment = ref<PaymentData>(emptyPayment);

const formatDate = (iso?: string) => (iso ? new Date(iso).toLocaleDateString('pt-PT') : '--');

// Datas da reserva vêm como "2026-11-23" (sem hora). new Date() lia-as em UTC e, nos Açores,
// mostrava o dia anterior; por isso formata-se o texto diretamente.
const formatDay = (day?: string) => {
    const match = day?.match(/^(\d{4})-(\d{2})-(\d{2})/);
    return match ? `${match[3]}/${match[2]}/${match[1]}` : formatDate(day);
};

// "23/11/2026 10:00"
const withHour = (day?: string, hour?: string | null) => (hour ? `${formatDay(day)} ${hour.slice(0, 5)}` : formatDay(day));

type BookingAnimal = { name: string; photo?: string | null };

// Lê a reserva (GET /api/bookings/{id}: só o dono ou o petsitter a podem ver) e preenche os detalhes
const loadPayment = async () => {
    if (!bookingId) {
        loadError.value = 'Reserva não indicada.';
        return;
    }
    try {
        const res = await apiFetch(`/api/bookings/${bookingId}`);
        if (!res.ok) {
            loadError.value = 'Não foi possível carregar a reserva.';
            return;
        }
        const booking = (await res.json()).data;
        const animals: BookingAnimal[] = booking.animals ?? [];

        payment.value = {
            id: booking.id,
            reservation_id: booking.id,
            amount: Number(booking.total_price ?? 0),
            payment_method: 'Cartão de crédito',
            created_at: booking.created_at,
            pet_name: animals.map((a) => a.name).join(', '),
            pet_photo: animals[0]?.photo ?? null,
            reservation_start: booking.start_date,
            reservation_end: booking.end_date,
        };
        entranceHour.value = booking.entrance_hour ?? null;
        exitHour.value = booking.exit_hour ?? null;
    } catch {
        loadError.value = 'Erro de ligação. Tenta novamente.';
    }
};

onMounted(() => {
    void loadPayment();
});
</script>

<template>
    <Head title="Pagamento Confirmado" />

    <main class="min-vh-100 py-4 patudo-page patudo-page-shell">
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
                    <AdminNavigationMenu />
                </div>

                <div class="d-flex align-items-center gap-3">
                    <HeaderNotificationsChat />
                    <a href="/profile" class="text-dark" aria-label="Perfil">
                        <UserRound :size="25" />
                    </a>
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
            <!-- só aparece se a reserva não carregar -->
            <p v-if="loadError" class="text-center text-danger small mb-4">{{ loadError }}</p>

            <section class="details-card mx-auto">
                <div class="px-4 pt-4 pb-3">
                    <h2 class="fs-2 fw-medium mb-4">Detalhes da transição</h2>

                    <div class="detail-row">
                        <span>id da reserva:</span>
                        <span class="value-pill">{{ payment.reservation_id ? `#${payment.reservation_id}` : '' }}</span>
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
                        <span class="value-pill">{{ payment.payment_method }}</span>
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
                        <small class="fs-6">
                            {{ withHour(payment.reservation_start, entranceHour) }} - {{ withHour(payment.reservation_end, exitHour) }}
                        </small>
                    </div>
                </div>
            </section>

            <div class="text-center mt-5">
                <!-- as reservas do dono estão em /reservations (/orders é a página do petsitter) -->
                <a href="/reservations" class="btn btn-primary rounded-pill px-5 py-2">Ir para meus pedidos</a>
            </div>
        </div>

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
    /* o texto cabe dentro da pastilha, que tem altura fixa */
    font-size: 0.8rem;
    line-height: 1.1rem;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
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
