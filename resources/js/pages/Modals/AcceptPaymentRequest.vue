<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { CalendarDays, Dog, UserRound, CheckCircle, XCircle, Clock } from '@lucide/vue';
import type { Animal } from '@/types';

const props = defineProps<{
    show: boolean;
    bookingId?: number | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'accepted'): void;
    (e: 'rejected'): void;
}>();

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

type BookingData = {
    id: number;
    user_id: number;
    petsitter_id: number;
    start_date: string;
    end_date: string;
    status: string;
    total_price: number;
    animals: Animal[];
    created_at: string;
};

type OwnerInfo = {
    name: string;
    avatar_url: string | null;
};

const booking = ref<BookingData | null>(null);
const owner = ref<OwnerInfo | null>(null);
const loading = ref(false);
const submitting = ref(false);
const feedback = ref('');

const numberOfDays = computed(() => {
    if (!booking.value) return 0;
    const start = new Date(booking.value.start_date);
    const end = new Date(booking.value.end_date);
    const diff = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1;
    return diff > 0 ? diff : 0;
});

const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-PT', { day: 'numeric', month: 'long', year: 'numeric' });
};

const loadBooking = async () => {
    if (!props.bookingId) return;
    loading.value = true;
    feedback.value = '';
    try {
        const res = await apiFetch(`/api/bookings/${props.bookingId}`);
        if (res.ok) {
            const data = (await res.json()).data;
            booking.value = data;
            const ownerRes = await apiFetch(`/api/users/${data.user_id}`);
            if (ownerRes.ok) {
                const userData = (await ownerRes.json()).data;
                owner.value = {
                    name: userData.name ?? 'Proprietário',
                    avatar_url: userData.avatar_url ?? null,
                };
            }
        } else {
            feedback.value = 'Não foi possível carregar a reserva.';
        }
    } catch {
        feedback.value = 'Erro de conexão. Tente novamente.';
    } finally {
        loading.value = false;
    }
};

const updateStatus = async (newStatus: 'approved' | 'rejected') => {
    if (!props.bookingId) return;
    submitting.value = true;
    feedback.value = '';
    try {
        const res = await apiFetch(`/api/bookings/${props.bookingId}/status`, {
            method: 'PATCH',
            body: JSON.stringify({ status: newStatus }),
        });
        if (res.ok) {
            feedback.value = newStatus === 'approved'
                ? 'Reserva confirmada com sucesso!'
                : 'Reserva rejeitada.';
            if (booking.value) booking.value.status = newStatus;
            setTimeout(() => {
                if (newStatus === 'approved') emit('accepted');
                else emit('rejected');
                handleClose();
            }, 1000);
        } else {
            feedback.value = 'Não foi possível atualizar o estado da reserva.';
        }
    } catch {
        feedback.value = 'Erro de conexão. Tente novamente.';
    } finally {
        submitting.value = false;
    }
};

const handleClose = () => {
    feedback.value = '';
    emit('close');
};

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            void loadBooking();
        }
    },
);

onMounted(() => {
    if (props.show) {
        void loadBooking();
    }
});
</script>

<template>
    <div
        v-if="show"
        class="patudo-modal-backdrop"
        @click.self="handleClose"
        @keydown.escape="handleClose"
    >
        <div class="patudo-modal-card" role="dialog" aria-modal="true">
            <!-- Cabeçalho -->
            <div class="patudo-modal-header">
                <div class="patudo-header-left">
                    <Clock :size="20" class="text-primary" />
                    <h2 class="patudo-modal-title">
                        Confirmação de Pagamento
                    </h2>
                </div>
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

            <!-- Mensagem de sucesso -->
            <div v-if="feedback.includes('sucesso')" class="patudo-success-banner">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M20 6L9 17l-5-5" />
                </svg>
                {{ feedback }}
            </div>

            <!-- Mensagem de erro -->
            <div v-if="feedback && !feedback.includes('sucesso')" class="patudo-error-banner">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="12" cy="12" r="10" />
                    <path d="M15 9l-6 6M9 9l6 6" />
                </svg>
                {{ feedback }}
            </div>

            <!-- Corpo -->
            <div class="patudo-modal-body">
                <!-- Loading -->
                <div v-if="loading && !booking" class="patudo-loading-state">
                    <div class="patudo-spinner"></div>
                    <p class="patudo-empty-text">A carregar detalhes da reserva...</p>
                </div>

                <template v-else-if="booking">
                    <!-- Info do proprietário -->
                    <div class="patudo-section">
                        <h3 class="patudo-section-title">Proprietário</h3>
                        <div class="patudo-owner-row">
                            <div class="patudo-owner-avatar">
                                <img
                                    v-if="owner?.avatar_url"
                                    :src="owner.avatar_url"
                                    :alt="owner?.name"
                                    class="w-100 h-100 object-fit-cover"
                                />
                                <UserRound v-else :size="24" class="text-primary" />
                            </div>
                            <div>
                                <p class="fw-semibold mb-0">{{ owner?.name ?? 'Proprietário' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Datas -->
                    <div class="patudo-section">
                        <h3 class="patudo-section-title">Datas</h3>
                        <div class="patudo-detail-row">
                            <CalendarDays :size="16" class="text-muted" />
                            <span>
                                {{ formatDate(booking.start_date) }}
                                <span class="text-muted"> → </span>
                                {{ formatDate(booking.end_date) }}
                            </span>
                            <span class="patudo-badge">
                                {{ numberOfDays }} {{ numberOfDays === 1 ? 'dia' : 'dias' }}
                            </span>
                        </div>
                    </div>

                    <!-- Animais -->
                    <div class="patudo-section">
                        <h3 class="patudo-section-title">Animais</h3>
                        <div class="patudo-animals-grid">
                            <div
                                v-for="animal in booking.animals"
                                :key="animal.id"
                                class="patudo-animal-card"
                            >
                                <div class="patudo-animal-avatar">
                                    <img
                                        v-if="animal.photo"
                                        :src="animal.photo"
                                        :alt="animal.name"
                                        class="w-100 h-100 object-fit-cover"
                                    />
                                    <Dog v-else :size="20" class="text-primary" />
                                </div>
                                <div class="patudo-animal-details">
                                    <p class="fw-semibold mb-0">{{ animal.name }}</p>
                                    <small class="text-muted">
                                        {{ animal.species }} {{ animal.breed ? '• ' + animal.breed : '' }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preço -->
                    <div class="patudo-section">
                        <h3 class="patudo-section-title">Preço Total</h3>
                        <div class="patudo-price-row">
                            <span class="patudo-price-value">
                                {{ booking.total_price }}€
                            </span>
                            <span class="patudo-price-note">
                                ({{ numberOfDays }} {{ numberOfDays === 1 ? 'dia' : 'dias' }})
                            </span>
                        </div>
                    </div>

                    <!-- Estado -->
                    <div v-if="booking.status === 'pending'" class="patudo-status-pending">
                        <Clock :size="14" />
                        <span>Aguardando resposta</span>
                    </div>
                </template>

                <!-- Erro ao carregar -->
                <div v-else class="patudo-empty-state">
                    <p class="patudo-empty-text">Não foi possível carregar a reserva.</p>
                </div>
            </div>

            <!-- Rodapé com ações -->
            <div v-if="booking?.status === 'pending'" class="patudo-modal-footer">
                <button
                    type="button"
                    class="btn-patudo-reject"
                    :disabled="submitting"
                    @click="updateStatus('rejected')"
                >
                    <XCircle :size="16" />
                    {{ submitting ? 'A rejeitar...' : 'Rejeitar' }}
                </button>
                <button
                    type="button"
                    class="btn-patudo-accept"
                    :disabled="submitting"
                    @click="updateStatus('approved')"
                >
                    <CheckCircle :size="16" />
                    {{ submitting ? 'A confirmar...' : 'Confirmar' }}
                </button>
            </div>
            <div v-else class="patudo-modal-footer patudo-footer-disabled">
                <span class="patudo-footer-note">
                    {{ booking?.status === 'approved' ? 'Reserva já confirmada' : booking?.status === 'rejected' ? 'Reserva já rejeitada' : 'Reserva finalizada' }}
                </span>
                <button
                    type="button"
                    class="btn-patudo-outline-pill"
                    @click="handleClose"
                >
                    Fechar
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
    max-height: 85vh;
    display: flex;
    flex-direction: column;
}

/* Cabeçalho */
.patudo-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.15rem 1.5rem;
    border-bottom: 1.5px solid #c4450c;
    flex-shrink: 0;
}

.patudo-header-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
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

/* Banners */
.patudo-success-banner {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.55rem 1.5rem;
    background-color: #dcfce7;
    color: #166534;
    font-size: 0.78rem;
    font-weight: 600;
    border-bottom: 1px solid #bbf7d0;
}

.patudo-error-banner {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.55rem 1.5rem;
    background-color: #fee2e2;
    color: #991b1b;
    font-size: 0.78rem;
    font-weight: 600;
    border-bottom: 1px solid #fecaca;
}

/* Corpo */
.patudo-modal-body {
    padding: 1.15rem 1.5rem;
    overflow-y: auto;
    flex: 1;
}

/* Secções */
.patudo-section {
    margin-bottom: 1.25rem;
}

.patudo-section:last-child {
    margin-bottom: 0;
}

.patudo-section-title {
    font-size: 0.72rem;
    font-weight: 700;
    color: #6e5239;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 0 0 0.5rem 0;
}

/* Proprietário */
.patudo-owner-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background-color: #ffffff;
    border: 1.2px solid #e5d5c5;
    border-radius: 12px;
}

.patudo-owner-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #fffaf3;
    flex-shrink: 0;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Detalhes */
.patudo-detail-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background-color: #ffffff;
    border: 1.2px solid #e5d5c5;
    border-radius: 12px;
    font-size: 0.85rem;
    color: #231810;
}

.patudo-badge {
    margin-left: auto;
    background-color: #fffaf3;
    color: #c4450c;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    border: 1px solid #e5d5c5;
}

/* Animais */
.patudo-animals-grid {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.patudo-animal-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.65rem 0.75rem;
    background-color: #ffffff;
    border: 1.2px solid #e5d5c5;
    border-radius: 12px;
}

.patudo-animal-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #fffaf3;
    flex-shrink: 0;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.patudo-animal-details {
    flex: 1;
    min-width: 0;
}

/* Preço */
.patudo-price-row {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background-color: #ffffff;
    border: 1.2px solid #e5d5c5;
    border-radius: 12px;
}

.patudo-price-value {
    font-size: 1.25rem;
    font-weight: 800;
    color: #c4450c;
}

.patudo-price-note {
    font-size: 0.78rem;
    color: #6e5239;
}

/* Estado pendente */
.patudo-status-pending {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    justify-content: center;
    padding: 0.5rem;
    background-color: #fef3c7;
    color: #92400e;
    font-size: 0.78rem;
    font-weight: 600;
    border-radius: 8px;
    margin-top: 0.5rem;
}

/* Loading & Empty */
.patudo-loading-state,
.patudo-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem 0;
    gap: 0.5rem;
}

.patudo-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #e5d5c5;
    border-top-color: #c4450c;
    border-radius: 50%;
    animation: patudo-spin 0.6s linear infinite;
}

@keyframes patudo-spin {
    to { transform: rotate(360deg); }
}

.patudo-empty-text {
    font-size: 0.85rem;
    color: #6e5239;
    font-weight: 600;
    margin: 0;
}

/* Rodapé */
.patudo-modal-footer {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.85rem 1.5rem;
    border-top: 1.5px solid #c4450c;
    flex-shrink: 0;
}

.patudo-footer-disabled {
    justify-content: space-between;
}

.patudo-footer-note {
    font-size: 0.72rem;
    color: #6e5239;
    font-weight: 600;
}

.btn-patudo-accept {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    background-color: #c4450c;
    color: #ffffff;
    border: none;
    border-radius: 9999px;
    padding: 0.6rem 1rem;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.15s ease;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.btn-patudo-accept:hover:not(:disabled) {
    background-color: #9a3609;
}

.btn-patudo-accept:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-patudo-reject {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    background-color: #ffffff;
    color: #c4450c;
    border: 1.3px solid #c4450c;
    border-radius: 9999px;
    padding: 0.6rem 1rem;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.15s ease;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.btn-patudo-reject:hover:not(:disabled) {
    background-color: #fffaf3;
}

.btn-patudo-reject:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-patudo-outline-pill {
    background-color: #ffffff;
    color: #c4450c;
    border: 1.3px solid #c4450c;
    border-radius: 9999px;
    padding: 0.38rem 1.15rem;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.15s ease;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.btn-patudo-outline-pill:hover {
    background-color: #fffaf3;
}

.text-primary {
    color: #c4450c;
}

.text-muted {
    color: #6e5239;
}

.fw-semibold {
    font-weight: 600;
}

.mb-0 {
    margin-bottom: 0;
}

.w-100 {
    width: 100%;
}

.h-100 {
    height: 100%;
}

.object-fit-cover {
    object-fit: cover;
}
</style>
