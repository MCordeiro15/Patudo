// Composable para gestão de notificações no front-end.
// Composable for front-end notifications management.
import type { Ref } from 'vue';
import { ref } from 'vue';

export interface NotificationItem {
    id: number;
    user_id: number;
    booking_id: number | null;
    booking_start_date: string | null;
    booking_url: string | null;
    type: string;
    message: string;
    read: boolean;
    read_at: string | null;
    created_at: string;
    updated_at: string;
}

export type UseNotificationsReturn = {
    notifications: Ref<NotificationItem[]>;
    isLoading: Ref<boolean>;
    errors: Ref<string[]>;
    unreadCount: Ref<number>;
    fetchNotifications: () => Promise<void>;
    markAsRead: (id: number) => Promise<boolean>;
    markAsUnread: (id: number) => Promise<boolean>;
    deleteNotification: (id: number) => Promise<boolean>;
    clearErrors: () => void;
};

const notifications = ref<NotificationItem[]>([]);
const isLoading = ref(false);
const errors = ref<string[]>([]);

const unreadCount = ref(0);
let pendingRequests = 0;
let fetchSequence = 0;
let stateRevision = 0;

function beginRequest(): void {
    pendingRequests += 1;
    isLoading.value = true;
}

function endRequest(): void {
    pendingRequests = Math.max(0, pendingRequests - 1);
    isLoading.value = pendingRequests > 0;
}

async function fetchCsrfToken() {
    await fetch('/sanctum/csrf-cookie', {
        method: 'GET',
        credentials: 'same-origin',
    });
}

function getXsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function apiRequest(
    url: string,
    options: RequestInit = {},
): Promise<any> {
    await fetchCsrfToken();
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getXsrfToken(),
            ...options.headers,
        },
        ...options,
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message ?? `HTTP ${response.status}`);
    }

    return response.json().catch(() => ({}));
}

export const useNotifications = (): UseNotificationsReturn => {
    const fetchNotifications = async (): Promise<void> => {
        const requestSequence = ++fetchSequence;
        const revisionAtStart = stateRevision;
        beginRequest();
        errors.value = [];
        try {
            const data = await apiRequest('/api/notifications');

            if (
                requestSequence === fetchSequence &&
                revisionAtStart === stateRevision
            ) {
                notifications.value = data.data ?? [];
                unreadCount.value =
                    data.unread_count ??
                    notifications.value.filter((n) => !n.read).length;
            }
        } catch (error: any) {
            if (requestSequence === fetchSequence) {
                errors.value.push(
                    error.message ?? 'Falha ao carregar notificações',
                );
            }
        } finally {
            endRequest();
        }
    };

    const markAsRead = async (id: number): Promise<boolean> => {
        beginRequest();
        errors.value = [];
        try {
            await apiRequest(`/api/notifications/${id}/read`, {
                method: 'PATCH',
            });

            const notification = notifications.value.find((n) => n.id === id);
            if (notification && !notification.read) {
                notification.read = true;
                notification.read_at = new Date().toISOString();
                unreadCount.value = Math.max(0, unreadCount.value - 1);
                stateRevision += 1;
            }
            return true;
        } catch (error: any) {
            errors.value.push(error.message ?? 'Falha ao marcar como lida');
            return false;
        } finally {
            endRequest();
        }
    };

    const markAsUnread = async (id: number): Promise<boolean> => {
        beginRequest();
        errors.value = [];
        try {
            await apiRequest(`/api/notifications/${id}/unread`, {
                method: 'PATCH',
            });

            const notification = notifications.value.find((n) => n.id === id);
            if (notification && notification.read) {
                notification.read = false;
                notification.read_at = null;
                unreadCount.value += 1;
                stateRevision += 1;
            }
            return true;
        } catch (error: any) {
            errors.value.push(error.message ?? 'Falha ao marcar como não lida');
            return false;
        } finally {
            endRequest();
        }
    };

    const deleteNotification = async (id: number): Promise<boolean> => {
        beginRequest();
        errors.value = [];
        try {
            await apiRequest(`/api/notifications/${id}`, { method: 'DELETE' });

            const notification = notifications.value.find((n) => n.id === id);
            if (notification && !notification.read) {
                unreadCount.value = Math.max(0, unreadCount.value - 1);
            }
            notifications.value = notifications.value.filter(
                (n) => n.id !== id,
            );
            stateRevision += 1;
            return true;
        } catch (error: any) {
            errors.value.push(error.message ?? 'Falha ao apagar notificação');
            return false;
        } finally {
            endRequest();
        }
    };

    const clearErrors = (): void => {
        errors.value = [];
    };

    return {
        notifications,
        isLoading,
        errors,
        unreadCount,
        fetchNotifications,
        markAsRead,
        markAsUnread,
        deleteNotification,
        clearErrors,
    };
};
