<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Bell, MessageCircle } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import { useChat } from '@/composables/useChat';
import { useNotifications } from '@/composables/useNotifications';
import ModalNotifications from '@/pages/Modals/ModalNotifications.vue';

const page = usePage<{ auth: { user: { id: number } | null } }>();
const showNotifications = ref(false);
const { totalUnread, start } = useChat();
const { unreadCount, fetchNotifications } = useNotifications();

if (typeof page.props.unreadNotificationCount === 'number') {
    unreadCount.value = page.props.unreadNotificationCount;
}

onMounted(() => {
    if (!page.props.auth.user) return;

    void fetchNotifications();
    void start();
});
</script>

<template>
    <a
        href="/messages"
        class="text-dark position-relative"
        :aria-label="totalUnread > 0 ? `Mensagens, ${totalUnread} por ler` : 'Mensagens'"
    >
        <MessageCircle :size="25" />
        <span
            v-if="totalUnread > 0"
            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
            style="font-size: 0.6rem"
        >
            {{ totalUnread > 9 ? '9+' : totalUnread }}
        </span>
    </a>
    <button
        type="button"
        class="text-dark bg-transparent border-0 p-0 position-relative"
        :aria-label="unreadCount > 0 ? `Notificações, ${unreadCount} por ler` : 'Notificações'"
        @click="showNotifications = true"
    >
        <Bell :size="25" />
        <span
            v-if="unreadCount > 0"
            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
            style="font-size: 0.6rem"
        >
            {{ unreadCount > 9 ? '9+' : unreadCount }}
        </span>
    </button>
    <ModalNotifications :show="showNotifications" @close="showNotifications = false" />
</template>