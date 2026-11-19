<script setup lang="ts">
// Página de mensagens: lista de conversas à esquerda e a conversa selecionada à direita.
// Messages page: conversation list on the left and the selected conversation on the right.
import { Head, Link } from '@inertiajs/vue3';
import { Bell, LogOut, MessageCircle, Send, UserRound } from '@lucide/vue';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import { useChat } from '@/composables/useChat';
import { useNotifications } from '@/composables/useNotifications';
import ModalNotifications from '@/pages/Modals/ModalNotifications.vue';

type Row =
    | { kind: 'day'; key: string; label: string }
    | { kind: 'msg'; key: string; mine: boolean; text: string; time: string };

const showNotifications = ref(false);
const { unreadCount, fetchNotifications } = useNotifications();

const {
    me,
    items,
    messages,
    activeOtherId,
    activePerson,
    activeOnline,
    ready,
    error,
    start,
    openConversation,
    sendMessage,
} = useChat();

const draft = ref('');
const messagesEl = ref<HTMLElement | null>(null);

function initial(name: string): string {
    return name.trim().charAt(0).toUpperCase();
}

function timeLabel(timestamp: number): string {
    return new Date(timestamp).toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' });
}

function dayLabel(timestamp: number): string {
    const day = new Date(timestamp);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);

    if (day.toDateString() === today.toDateString()) {
        return 'Hoje';
    }

    return day.toDateString() === yesterday.toDateString() ? 'Ontem' : day.toLocaleDateString('pt-PT');
}

// Mensagens com um separador de dia sempre que o dia muda.
// Messages with a day separator whenever the day changes.
const rows = computed<Row[]>(() => {
    const result: Row[] = [];
    let previousDay = '';

    messages.value.forEach((message) => {
        const day = new Date(message.createdAt).toDateString();

        if (day !== previousDay) {
            previousDay = day;
            result.push({ kind: 'day', key: `day-${day}`, label: dayLabel(message.createdAt) });
        }

        result.push({
            kind: 'msg',
            key: message.id,
            mine: message.sender?.id === me.value?.id,
            text: message.plaintext,
            time: timeLabel(message.createdAt),
        });
    });

    return result;
});

watch(rows, async () => {
    await nextTick();

    if (messagesEl.value) {
        messagesEl.value.scrollTop = messagesEl.value.scrollHeight;
    }
});

async function submit(): Promise<void> {
    const text = draft.value;
    draft.value = '';
    await sendMessage(text);
}

onMounted(async () => {
    void fetchNotifications();
    await start();

    // /messages?with=ID abre logo a conversa com essa pessoa (ex.: botão "Chat" no perfil).
    // /messages?with=ID opens the conversation with that person straight away.
    const withId = new URLSearchParams(window.location.search).get('with');

    if (withId && items.value.some((item) => item.id === withId)) {
        await openConversation(withId);
    }
});
</script>

<template>
    <Head title="Mensagens" />

    <main class="min-vh-100 py-4 patudo-page">
        <div class="container">
            <!-- Barra de navegação, igual à das outras páginas. -->
            <!-- Navigation bar, same as the other pages. -->
            <nav class="navbar navbar-expand-lg bg-white border border-primary rounded-4 px-3 px-md-4">
                <a class="navbar-brand fw-bold fs-3 d-flex align-items-center gap-2" href="/">
                    <img :src="logoPatudo" alt="Patudo" width="70" height="70" />
                    <span>Patudo</span>
                </a>

                <div class="nav nav-pills border rounded-pill p-1 mx-auto">
                    <a class="nav-link text-dark rounded-pill px-4" href="/">Início</a>
                    <a class="nav-link text-dark rounded-pill px-4" href="/orders">Pedidos</a>
                    <a class="nav-link text-dark rounded-pill px-4" href="/favorites">Favoritos</a>
                    <a class="nav-link text-dark rounded-pill px-4" href="/reservations">Reservas</a>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <a href="/messages" class="text-primary">
                        <MessageCircle :size="25" />
                    </a>
                    <button
                        type="button"
                        class="text-dark bg-transparent border-0 p-0 position-relative"
                        @click="showNotifications = true"
                    >
                        <Bell :size="25" />
                        <span
                            v-if="unreadCount > 0"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            style="font-size: 0.65rem"
                        >
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </span>
                    </button>
                    <a href="/profile" class="text-dark">
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

            <div class="chat-layout mt-4">
                <!-- Lista de conversas -->
                <aside class="chat-panel">
                    <h1 class="chat-title">Mensagens</h1>

                    <p v-if="error" class="text-danger small">{{ error }}</p>
                    <p v-else-if="ready && items.length === 0" class="text-muted small">
                        Ainda não tens conversas.
                    </p>

                    <div class="chat-items">
                        <button
                            v-for="item in items"
                            :key="item.id"
                            type="button"
                            class="chat-item"
                            :class="{ active: item.id === activeOtherId }"
                            @click="openConversation(item.id)"
                        >
                            <span class="chat-avatar">{{ initial(item.name) }}</span>
                            <span class="chat-item-info">
                                <strong>{{ item.name }} • {{ item.role }}</strong>
                                <span class="chat-preview">{{ item.lastText || 'Sem mensagens' }}</span>
                            </span>
                            <span class="chat-item-side">
                                <span>{{ item.lastAt ? timeLabel(item.lastAt) : '' }}</span>
                                <span v-if="item.unread > 0" class="chat-badge">{{ item.unread }}</span>
                            </span>
                        </button>
                    </div>
                </aside>

                <!-- Conversa aberta -->
                <section class="chat-panel">
                    <p v-if="!activePerson" class="chat-empty">Escolhe uma conversa para começar.</p>

                    <template v-else>
                        <div class="chat-header">
                            <span class="chat-avatar chat-avatar-lg">{{ initial(activePerson.name) }}</span>
                            <div>
                                <strong class="chat-header-name">{{ activePerson.name }} • {{ activePerson.role }}</strong>
                                <span class="chat-status">
                                    <i class="chat-dot" :class="{ online: activeOnline }"></i>
                                    {{ activeOnline ? 'Online agora' : 'Offline' }}
                                </span>
                            </div>
                        </div>

                        <div ref="messagesEl" class="chat-messages">
                            <template v-for="row in rows" :key="row.key">
                                <div v-if="row.kind === 'day'" class="chat-day">{{ row.label }}</div>
                                <div v-else class="chat-row" :class="row.mine ? 'mine' : 'theirs'">
                                    <span v-if="!row.mine" class="chat-avatar">{{ initial(activePerson.name) }}</span>
                                    <div class="chat-col">
                                        <div class="chat-bubble">{{ row.text }}</div>
                                        <small class="chat-hour">{{ row.time }}</small>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <form class="chat-form" autocomplete="off" @submit.prevent="submit">
                            <input
                                v-model="draft"
                                type="text"
                                class="chat-input"
                                placeholder="Escrever mensagem..."
                                maxlength="2000"
                            />
                            <button type="submit" class="chat-send" aria-label="Enviar">
                                <Send :size="22" />
                            </button>
                        </form>
                    </template>
                </section>
            </div>
        </div>
    </main>

    <ModalNotifications
        :show="showNotifications"
        @close="showNotifications = false"
        @mark-as-read="fetchNotifications"
    />
</template>

<style scoped>
.chat-layout {
    display: grid;
    grid-template-columns: minmax(320px, 2fr) 3fr;
    gap: 1rem;
    height: calc(100vh - 210px);
    min-height: 520px;
}

.chat-panel {
    display: flex;
    flex-direction: column;
    min-height: 0;
    padding: 1.25rem 1rem 1rem;
    border: 1px solid var(--bs-primary);
    border-radius: 1.75rem;
}

.chat-title {
    margin: 0 0 1rem 0.5rem;
    font-size: 1.9rem;
    font-weight: 500;
}

.chat-items {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    overflow-y: auto;
}

.chat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    width: 100%;
    padding: 0.75rem 0.9rem;
    border: 1px solid var(--bs-primary);
    border-radius: 0.75rem;
    background: transparent;
    color: inherit;
    text-align: left;
}

.chat-item.active {
    background: var(--patudo-bege);
}

.chat-avatar {
    display: flex;
    flex: none;
    align-items: center;
    justify-content: center;
    width: 3.4rem;
    height: 3.4rem;
    border: 1px solid var(--patudo-castanhoDark);
    border-radius: 50%;
    background: var(--patudo-bege);
    font-weight: 700;
}

.chat-avatar-lg {
    width: 5.5rem;
    height: 5.5rem;
    font-size: 1.8rem;
}

.chat-item-info {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 0;
}

.chat-preview {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.chat-item-side {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.35rem;
}

.chat-badge {
    padding: 0 0.55rem;
    border-radius: 999px;
    background: var(--bs-primary);
    color: #fff;
    font-size: 0.75rem;
    font-weight: 700;
}

.chat-empty {
    margin: auto;
    color: var(--patudo-castanhoMid);
}

.chat-header {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    padding: 0.25rem 0.5rem 1rem;
}

.chat-header-name {
    display: block;
    font-size: 1.5rem;
}

.chat-status {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin-top: 0.25rem;
}

.chat-dot {
    width: 0.8rem;
    height: 0.8rem;
    border-radius: 50%;
    background: var(--patudo-castanhoMid);
}

.chat-dot.online {
    background: var(--patudo-verde);
}

.chat-messages {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 1rem;
    padding: 0.5rem 0.75rem;
    overflow-y: auto;
}

.chat-day {
    align-self: center;
    padding: 0.2rem 2rem;
    border: 1px solid var(--bs-primary);
    border-radius: 999px;
    background: var(--patudo-bege);
    font-size: 0.9rem;
}

.chat-row {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    max-width: 75%;
}

.chat-row.theirs {
    align-self: flex-start;
}

.chat-row.mine {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.chat-row .chat-avatar {
    width: 3.6rem;
    height: 3.6rem;
}

.chat-col {
    display: flex;
    flex-direction: column;
}

.chat-row.mine .chat-col {
    align-items: flex-end;
}

.chat-bubble {
    padding: 0.7rem 1.4rem;
    border-radius: 0.5rem;
    overflow-wrap: anywhere;
    white-space: pre-wrap;
}

.theirs .chat-bubble {
    border: 1px solid var(--bs-primary);
    background: var(--patudo-bege);
}

.mine .chat-bubble {
    background: var(--patudo-castanhoDark);
    color: #fff;
}

.chat-hour {
    margin: 0.35rem 0.75rem 0;
}

.chat-form {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding-top: 1rem;
}

.chat-input {
    flex: 1;
    height: 3.4rem;
    padding: 0 1rem;
    border: 1px solid var(--bs-primary);
    border-radius: 0.5rem;
    background: transparent;
    color: inherit;
}

.chat-send {
    display: flex;
    flex: none;
    align-items: center;
    justify-content: center;
    width: 3.4rem;
    height: 3.4rem;
    border: 0;
    border-radius: 50%;
    background: var(--bs-primary);
    color: #fff;
}

@media (max-width: 991px) {
    .chat-layout {
        grid-template-columns: 1fr;
        height: auto;
    }

    .chat-messages {
        min-height: 320px;
    }
}
</style>