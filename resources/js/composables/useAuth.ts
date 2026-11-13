// Composable para gestão de autenticação no front-end. Fornece estado reativo do utilizador logado, métodos de login/logout e verificação de permissões.
// Composable for front-end authentication management. Provides reactive state of the logged-in user, login/logout methods, and permission checks.
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { User } from '@/types';

const user = ref<User | null>(null);
const isLoading = ref(false);
const isInitialized = ref(false);

/**
 * Fetch the CSRF token from Laravel and store it for subsequent API calls.
 */
async function fetchCsrfToken() {
    await fetch('/sanctum/csrf-cookie', {
        method: 'GET',
        credentials: 'same-origin',
    });
}

/**
 * Load the authenticated user from GET /api/me.
 * Called once on app initialization to populate global session state.
 */
export async function loadUser() {
    if (isInitialized.value) {
        return user.value;
    }

    isLoading.value = true;

    try {
        await fetchCsrfToken();

        const response = await fetch('/api/me', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            const data = await response.json();
            // Laravel API resources wrap data in a `data` property
            user.value = data.data ?? data;
        } else {
            user.value = null;
        }
    } catch {
        user.value = null;
    } finally {
        isLoading.value = false;
        isInitialized.value = true;
    }

    return user.value;
}

/**
 * Log in the user via POST /api/login.
 * On success, loads the user data and navigates to the given redirect URL.
 */
export async function login(
    email: string,
    password: string,
    redirectTo: string = '/petsitters',
) {
    isLoading.value = true;

    try {
        await fetchCsrfToken();

        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        const xsrfToken = match ? decodeURIComponent(match[1]) : '';

        const response = await fetch('/api/login', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken ?? '',
            },
            body: JSON.stringify({ email, password }),
        });

        if (response.ok) {
            const data = await response.json();
            user.value = data.data ?? data;

            // Also refresh the Inertia page props so page.props.auth.user stays in sync
            router.get(
                redirectTo,
                {},
                {
                    replace: true,
                    preserveScroll: true,
                },
            );

            return { success: true, user: user.value };
        } else {
            const errorData = await response.json();
            return {
                success: false,
                errors: errorData.errors ?? { general: errorData.message },
            };
        }
    } catch (error: any) {
        return {
            success: false,
            errors: { general: error.message ?? 'Erro de rede.' },
        };
    } finally {
        isLoading.value = false;
    }
}

/**
 * Register a new user via POST /api/register.
 * On success, loads the user data and navigates to the given redirect URL.
 */
export async function register(
    name: string,
    email: string,
    password: string,
    passwordConfirmation: string,
    phone: string | null = null,
    redirectTo: string = '/petsitters',
) {
    isLoading.value = true;

    try {
        await fetchCsrfToken();

        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        const xsrfToken = match ? decodeURIComponent(match[1]) : '';

        const body: Record<string, string | null> = {
            name,
            email,
            password,
            password_confirmation: passwordConfirmation,
        };
        if (phone) {
            body.phone = phone;
        }

        const response = await fetch('/api/register', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken ?? '',
            },
            body: JSON.stringify(body),
        });

        if (response.ok) {
            const data = await response.json();
            user.value = data.data ?? data;

            router.get(
                redirectTo,
                {},
                {
                    replace: true,
                    preserveScroll: true,
                },
            );

            return { success: true, user: user.value };
        } else {
            const errorData = await response.json();
            return {
                success: false,
                errors: errorData.errors ?? { general: errorData.message },
            };
        }
    } catch (error: any) {
        return {
            success: false,
            errors: { general: error.message ?? 'Erro de rede.' },
        };
    } finally {
        isLoading.value = false;
    }
}

/**
 * Log out the current user via POST /api/logout.
 * Clears local state and redirects to /login.
 */
export async function logout() {
    try {
        await fetchCsrfToken();

        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        const xsrfToken = match ? decodeURIComponent(match[1]) : '';

        await fetch('/api/logout', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken ?? '',
            },
        });
    } finally {
        user.value = null;
        isInitialized.value = false;
        router.flushAll();
        window.location.href = '/login';
    }
}

/**
 * Reactive composable that exposes the global auth state.
 * Can be called from any component to access the current user.
 */
export function useAuth() {
    const isAuthenticated = computed(() => user.value !== null);

    return {
        user,
        isAuthenticated,
        isLoading,
        login,
        register,
        logout,
        loadUser,
    };
}
