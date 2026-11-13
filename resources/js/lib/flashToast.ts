// Função utilitária para exibir notificações toast a partir de flash messages do servidor. Integra Inertia com vue-sonner.
// Utility function to display toast notifications from server flash messages. Integrates Inertia with vue-sonner.
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        toast[data.type](data.message);
    });
}
