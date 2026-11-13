// Composable para gestão de notificações no front-end.
// Composable for front-end notifications management.
import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';

export interface NotificationItem {
    id: number;
    user_id: number;
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
    unreadCount: ComputedRef<number>;
    fetchNotifications: () => Promise<void>;
    markAsRead: (id: number) => Promise<void>;
    deleteNotification: (id: number) => Promise<void>;
    clearErrors: () => void;
};

const notifications = ref<NotificationItem[]>([]);
const isLoading = ref(false);
const errors = ref<string[]>([]);

const unreadCount = computed<number>(() =>
    notifications.value.filter((n) => !n.read).length,
);

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
        } catch (error: any) {
            errors.value.push(
                error.message ?? 'Falha ao carregar notificações',
            );
        } finally {
            isLoading.value = false;
        }
    };

    const markAsRead = async (id: number): Promise<void> => {
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
            if (notification) {
                notification.read = true;
                notification.read_at = new Date().toISOString();
            }
        } catch (error: any) {
            errors.value.push(
                error.message ?? 'Falha ao marcar como lida',
            );
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
        deleteNotification,
        clearErrors,
    };
};
