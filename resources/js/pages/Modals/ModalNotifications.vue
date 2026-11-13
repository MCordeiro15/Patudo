<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useNotifications, type NotificationItem } from '@/composables/useNotifications';

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'markAsRead', id: number): void;
    (e: 'delete', id: number): void;
}>();

const {
    notifications,
    isLoading,
    errors: apiErrors,
    unreadCount,
    fetchNotifications,
    markAsRead,
    deleteNotification,
} = useNotifications();

const selectedNotification = ref<NotificationItem | null>(null);
const activeTab = ref<'list' | 'detail'>('list');
const filterType = ref<'all' | 'read' | 'unread'>('all');
const successMessage = ref('');

const errorMessage = computed(() => apiErrors.value[0] ?? '');

const filteredNotifications = computed(() => {
    switch (filterType.value) {
        case 'read':
            return notifications.value.filter((n) => n.read);
        case 'unread':
            return notifications.value.filter((n) => !n.read);
        default:
            return [...notifications.value];
    }
});

const typeLabels: Record<string, string> = {
    booking: 'Reserva',
    review: 'Avaliação',
    system: 'Sistema',
    message: 'Mensagem',
};

const typeIcons: Record<string, string> = {
    booking: '📅',
    review: '⭐',
    system: '⚙️',
    message: '💬',
};

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            fetchNotifications();
            selectedNotification.value = null;
            activeTab.value = 'list';
            filterType.value = 'all';
            successMessage.value = '';
        }
    }
);

const handleClose = () => {
    emit('close');
};

const handleSelect = (notification: NotificationItem) => {
    selectedNotification.value = notification;
    activeTab.value = 'detail';
};

const handleBack = () => {
    selectedNotification.value = null;
    activeTab.value = 'list';
};

const handleMarkAsRead = async (id: number) => {
    await markAsRead(id);
    successMessage.value = 'Notificação marcada como lida.';
    emit('markAsRead', id);
    if (selectedNotification.value && selectedNotification.value.id === id) {
        selectedNotification.value = { ...selectedNotification.value };
    }
    setTimeout(() => {
        successMessage.value = '';
    }, 3000);
};

const handleDelete = async (id: number) => {
    await deleteNotification(id);
    successMessage.value = 'Notificação apagada.';
    emit('delete', id);
    handleBack();
    setTimeout(() => {
        successMessage.value = '';
    }, 3000);
};

const formatDate = (dateString: string | null) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-PT', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const timeAgo = (dateString: string) => {
    const now = new Date();
    const date = new Date(dateString);
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 1) return 'Agora mesmo';
    if (diffMins < 60) return `${diffMins}min atrás`;
    if (diffHours < 24) return `${diffHours}h atrás`;
    if (diffDays < 7) return `${diffDays}d atrás`;
    return formatDate(dateString);
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
            <div class="patudo-modal-card patudo-modal-wide" role="dialog" aria-modal="true">
                <!-- Cabeçalho -->
                <div class="patudo-modal-header">
                    <div class="patudo-header-left">
                        <button
                            v-if="activeTab === 'detail'"
                            type="button"
                            class="patudo-back-btn"
                            aria-label="Voltar"
                            @click="handleBack"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 12H5M12 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <div class="patudo-header-title-group">
                            <h2 class="patudo-modal-title">
                                {{ activeTab === 'detail' ? 'Detalhes da Notificação' : 'Notificações' }}
                            </h2>
                            <span v-if="activeTab === 'list'" class="patudo-unread-badge">
                                {{ unreadCount }} não lidas
                            </span>
                        </div>
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
                <div v-if="successMessage" class="patudo-success-banner">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M20 6L9 17l-5-5" />
                    </svg>
                    {{ successMessage }}
                </div>

                <!-- Mensagem de erro -->
                <div v-if="errorMessage" class="patudo-error-banner">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M15 9l-6 6M9 9l6 6" />
                    </svg>
                    {{ errorMessage }}
                </div>

                <!-- Corpo -->
                <div class="patudo-modal-body">
                    <!-- ABA: LISTA -->
                    <div v-if="activeTab === 'list'">
                        <!-- Filtros -->
                        <div class="patudo-filter-tabs">
                            <button
                                type="button"
                                class="patudo-filter-tab"
                                :class="{ 'is-active': filterType === 'all' }"
                                @click="filterType = 'all'"
                            >
                                Todas ({{ notifications.length }})
                            </button>
                            <button
                                type="button"
                                class="patudo-filter-tab"
                                :class="{ 'is-active': filterType === 'unread' }"
                                @click="filterType = 'unread'"
                            >
                                Não lidas ({{ unreadCount }})
                            </button>
                            <button
                                type="button"
                                class="patudo-filter-tab"
                                :class="{ 'is-active': filterType === 'read' }"
                                @click="filterType = 'read'"
                            >
                                Lidas ({{ notifications.length - unreadCount }})
                            </button>
                        </div>

                        <!-- Lista de notificações -->
                        <div class="patudo-notification-list">
                            <!-- Loading state -->
                            <div v-if="isLoading" class="patudo-loading-state">
                                <div class="patudo-spinner"></div>
                                <p class="patudo-empty-text">A carregar notificações...</p>
                            </div>
                            <!-- Empty state -->
                            <div v-else-if="filteredNotifications.length === 0" class="patudo-empty-state">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#c4450c" stroke-width="1.2" stroke-linecap="round">
                                    <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0" />
                                </svg>
                                <p class="patudo-empty-text">Nenhuma notificação encontrada.</p>
                            </div>

                            <div
                                v-for="notification in filteredNotifications"
                                :key="notification.id"
                                class="patudo-notification-item"
                                :class="{ 'is-unread': !notification.read }"
                                @click="handleSelect(notification)"
                            >
                                <div class="patudo-notification-icon">
                                    {{ typeIcons[notification.type] || '🔔' }}
                                </div>
                                <div class="patudo-notification-content">
                                    <div class="patudo-notification-top-row">
                                        <span class="patudo-notification-type">
                                            {{ typeLabels[notification.type] || notification.type }}
                                        </span>
                                        <span class="patudo-notification-time">
                                            {{ timeAgo(notification.created_at) }}
                                        </span>
                                    </div>
                                    <p class="patudo-notification-message">
                                        {{ notification.message }}
                                    </p>
                                </div>
                                <div v-if="!notification.read" class="patudo-unread-dot"></div>
                            </div>
                        </div>
                    </div>

                    <!-- ABA: DETALHE -->
                    <div v-else-if="activeTab === 'detail' && selectedNotification">
                        <div class="patudo-detail-card">
                            <div class="patudo-detail-icon-row">
                                <div class="patudo-detail-icon">
                                    {{ typeIcons[selectedNotification.type] || '🔔' }}
                                </div>
                                <div class="patudo-detail-meta">
                                    <span class="patudo-detail-type">
                                        {{ typeLabels[selectedNotification.type] || selectedNotification.type }}
                                    </span>
                                    <span class="patudo-detail-status" :class="{ 'is-read': selectedNotification.read }">
                                        {{ selectedNotification.read ? 'Lida' : 'Não lida' }}
                                    </span>
                                </div>
                            </div>

                            <p class="patudo-detail-message">
                                {{ selectedNotification.message }}
                            </p>

                            <div class="patudo-detail-timestamps">
                                <div class="patudo-timestamp-item">
                                    <span class="patudo-timestamp-label">Criada em</span>
                                    <span class="patudo-timestamp-value">
                                        {{ formatDate(selectedNotification.created_at) }}
                                    </span>
                                </div>
                                <div v-if="selectedNotification.read_at" class="patudo-timestamp-item">
                                    <span class="patudo-timestamp-label">Lida em</span>
                                    <span class="patudo-timestamp-value">
                                        {{ formatDate(selectedNotification.read_at) }}
                                    </span>
                                </div>
                            </div>

                            <div class="patudo-detail-actions">
                                <button
                                    v-if="!selectedNotification.read"
                                    type="button"
                                    class="btn-patudo-solid-pill"
                                    :disabled="isLoading"
                                    @click="handleMarkAsRead(selectedNotification.id)"
                                >
                                    {{ isLoading ? 'A processar...' : 'Marcar como lida' }}
                                </button>
                                <button
                                    type="button"
                                    class="btn-patudo-danger-pill"
                                    :disabled="isLoading"
                                    @click="handleDelete(selectedNotification.id)"
                                >
                                    {{ isLoading ? 'A processar...' : 'Apagar notificação' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rodapé -->
                <div class="patudo-modal-footer">
                    <span class="patudo-footer-note">
                        {{ activeTab === 'list' ? `${filteredNotifications.length} notificações` : 'ID: ' + selectedNotification?.id }}
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
    max-height: 85vh;
    display: flex;
    flex-direction: column;
}

.patudo-modal-wide {
    max-width: 560px;
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

.patudo-header-title-group {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.patudo-modal-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #231810;
    margin: 0;
}

.patudo-unread-badge {
    font-size: 0.72rem;
    font-weight: 600;
    color: #c4450c;
}

.patudo-back-btn {
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

.patudo-back-btn:hover {
    opacity: 0.65;
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

/* Banners de mensagem */
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

/* Filtros */
.patudo-filter-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.patudo-filter-tab {
    background-color: #ffffff;
    color: #6e5239;
    border: 1.2px solid #d4a574;
    border-radius: 9999px;
    padding: 0.3rem 0.85rem;
    font-size: 0.74rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}

.patudo-filter-tab:hover {
    background-color: #fdf2ea;
}

.patudo-filter-tab.is-active {
    background-color: #c4450c;
    color: #ffffff;
    border-color: #c4450c;
}

/* Lista de notificações */
.patudo-notification-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.patudo-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem 0;
    gap: 0.75rem;
}

.patudo-loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem 0;
    gap: 0.75rem;
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

.patudo-notification-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.85rem 1rem;
    background-color: #ffffff;
    border: 1.2px solid #e5d5c5;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.15s ease;
    position: relative;
}

.patudo-notification-item:hover {
    border-color: #c4450c;
    box-shadow: 0 2px 8px rgba(196, 69, 12, 0.1);
}

.patudo-notification-item.is-unread {
    border-color: #c4450c;
    background-color: #fff5ee;
}

.patudo-notification-icon {
    font-size: 1.4rem;
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.patudo-notification-content {
    flex: 1;
    min-width: 0;
}

.patudo-notification-top-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.2rem;
}

.patudo-notification-type {
    font-size: 0.72rem;
    font-weight: 700;
    color: #c4450c;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.patudo-notification-time {
    font-size: 0.68rem;
    color: #6e5239;
    font-weight: 500;
    flex-shrink: 0;
}

.patudo-notification-message {
    font-size: 0.82rem;
    color: #231810;
    margin: 0;
    line-height: 1.4;
    font-weight: 500;
}

.patudo-unread-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #c4450c;
    flex-shrink: 0;
    margin-top: 0.35rem;
}

/* Detalhe */
.patudo-detail-card {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.patudo-detail-icon-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.patudo-detail-icon {
    font-size: 2rem;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #ffffff;
    border: 1.2px solid #e5d5c5;
    border-radius: 12px;
    flex-shrink: 0;
}

.patudo-detail-meta {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.patudo-detail-type {
    font-size: 0.85rem;
    font-weight: 700;
    color: #c4450c;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.patudo-detail-status {
    font-size: 0.74rem;
    font-weight: 600;
    color: #6e5239;
}

.patudo-detail-status.is-read {
    color: #166534;
}

.patudo-detail-message {
    font-size: 0.95rem;
    color: #231810;
    margin: 0;
    line-height: 1.5;
    font-weight: 500;
    padding: 0.85rem 1rem;
    background-color: #ffffff;
    border: 1.2px solid #e5d5c5;
    border-radius: 12px;
}

.patudo-detail-timestamps {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    padding: 0.75rem 1rem;
    background-color: #ffffff;
    border: 1.2px solid #e5d5c5;
    border-radius: 12px;
}

.patudo-timestamp-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.patudo-timestamp-label {
    font-size: 0.74rem;
    color: #6e5239;
    font-weight: 600;
}

.patudo-timestamp-value {
    font-size: 0.74rem;
    color: #231810;
    font-weight: 700;
}

.patudo-detail-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

/* Rodapé */
.patudo-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.85rem 1.5rem;
    border-top: 1.5px solid #c4450c;
    flex-shrink: 0;
}

.patudo-footer-note {
    font-size: 0.72rem;
    color: #6e5239;
    font-weight: 600;
}

.btn-patudo-outline-pill {
    background-color: #ffffff;
    color: var(--patudo-terracota, #c4450c);
    border: 1.3px solid var(--patudo-terracota, #c4450c);
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

.btn-patudo-danger-pill {
    background-color: #ffffff;
    color: #dc2626;
    border: 1.3px solid #dc2626;
    border-radius: 9999px;
    padding: 0.38rem 1.6rem;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.btn-patudo-danger-pill:hover:not(:disabled) {
    background-color: #fef2f2;
}

.btn-patudo-danger-pill:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
