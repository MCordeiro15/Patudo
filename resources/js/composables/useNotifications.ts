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
    deleteNotification: (id: number) => Promise<void>;
    clearErrors: () => void;
};

const notifications = ref<NotificationItem[]>([]);
const isLoading = ref(false);
const errors = ref<string[]>([]);

const unreadCount = ref(0);

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

async function apiRequest(url: string, options: RequestInit = {}): Promise<any> {
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
        throw {
            response: {
                data: errorData,
            },
            status: response.status,
        };
    }

    return response.json().catch(() => ({}));
}

export const useNotifications = (): UseNotificationsReturn => {
    const fetchNotifications = async (): Promise<void> => {
        isLoading.value = true;
        errors.value = [];
        try {
            await fetchCsrfToken();

            const response = await fetch('/api/notifications', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            notifications.value = data.data ?? [];
            unreadCount.value = data.unread_count ?? notifications.value.filter((n) => !n.read).length;
        } catch (error: any) {
            errors.value.push(
                error.message ?? 'Falha ao carregar notificações',
            );
        } finally {
            isLoading.value = false;
        }
    };

    const markAsRead = async (id: number): Promise<boolean> => {
        isLoading.value = true;
        errors.value = [];
        try {
            await fetchCsrfToken();

            const response = await fetch(`/api/notifications/${id}/read`, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': getXsrfToken(),
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const notification = notifications.value.find((n) => n.id === id);
            if (notification && !notification.read) {
                notification.read = true;
                notification.read_at = new Date().toISOString();
                unreadCount.value = Math.max(0, unreadCount.value - 1);
            }
            return true;
        } catch (error: any) {
            errors.value.push(
                error.message ?? 'Falha ao marcar como lida',
            );
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    const markAsUnread = async (id: number): Promise<boolean> => {
        isLoading.value = true;
        errors.value = [];
        try {
            await fetchCsrfToken();

            const response = await fetch(`/api/notifications/${id}/unread`, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': getXsrfToken(),
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const notification = notifications.value.find((n) => n.id === id);
            if (notification && notification.read) {
                notification.read = false;
                notification.read_at = null;
                unreadCount.value += 1;
            }
            return true;
        } catch (error: any) {
            errors.value.push(
                error.message ?? 'Falha ao marcar como não lida',
            );
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    const deleteNotification = async (id: number): Promise<void> => {
        isLoading.value = true;
        errors.value = [];
        try {
            await fetchCsrfToken();

            const response = await fetch(`/api/notifications/${id}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': getXsrfToken(),
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const notification = notifications.value.find((n) => n.id === id);
            if (notification && !notification.read) {
                unreadCount.value = Math.max(0, unreadCount.value - 1);
            }
            notifications.value = notifications.value.filter((n) => n.id !== id);
        } catch (error: any) {
            errors.value.push(
                error.message ?? 'Falha ao apagar notificação',
            );
        } finally {
            isLoading.value = false;
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
