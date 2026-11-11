import { createInertiaApp, router } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import { loadUser } from '@/composables/useAuth';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import '../css/app.css';

if (typeof window !== 'undefined') {
    void import('bootstrap');
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Global 401 interceptor – redirect to /login when the session is missing
router.on('error', (error) => {
    if ((error as { status?: number }).status === 401) {
        window.location.href = '/login';
    }
});

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Homepage':
            case name.startsWith('auth/'):
            case name === 'Petsitter/SearchPetsitter':
            case name === 'Petsitter/PublicSearchPetsitter':
            case name === 'Petsitter/Favorites':
            case name === 'Petsitter/Profile':
                return null;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    withApp: (app) => {
        app.directive('focus', {
            mounted: (el: HTMLElement, shouldFocus) => {
                if (shouldFocus.value !== false) {
                    el.focus();
                }
            },
        });
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();

// Load the authenticated user once on app initialization so all pages
// have access to the session data without repeating the request.
void loadUser();
