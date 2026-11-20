<script setup lang="ts">
// Página de mensagens: lista de conversas à esquerda e a conversa selecionada à direita.
// Messages page: conversation list on the left and the selected conversation on the right.
import { Head, Link } from '@inertiajs/vue3';
import { Bell, Ellipsis, LogOut, MessageCircle, Pencil, Send, SmilePlus, Trash2, UserRound } from '@lucide/vue';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import { REACTION_EMOJIS, useChat } from '@/composables/useChat';
import { useNotifications } from '@/composables/useNotifications';
import ModalNotifications from '@/pages/Modals/ModalNotifications.vue';

interface ReactionChip {
    emoji: string;
    count: number;
    mine: boolean;
}

type Row =
    | { kind: 'day'; key: string; label: string }
    | {
          kind: 'msg';
          key: string;
          id: string;
          mine: boolean;
          text: string;
          time: string;
          edited: boolean;
          reactions: ReactionChip[];
      };

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
    editMessage,
    deleteMessage,
    toggleReaction,
} = useChat();

const draft = ref('');
const messagesEl = ref<HTMLElement | null>(null);

// Menus abertos (editar/apagar e escolher emoji) e mensagem em edição.
// Open menus (edit/delete and emoji picker) and the message being edited.
const openMenuId = ref<string | null>(null);
const pickerId = ref<string | null>(null);
const editingId = ref<string | null>(null);
const editDraft = ref('');

function closePopups(): void {
    openMenuId.value = null;
    pickerId.value = null;
}

function toggleMenu(id: string): void {
    const open = openMenuId.value !== id;
    closePopups();
    openMenuId.value = open ? id : null;
}

function togglePicker(id: string): void {
    const open = pickerId.value !== id;
    closePopups();
    pickerId.value = open ? id : null;
}

function startEdit(id: string, text: string): void {
    closePopups();
    editingId.value = id;
    editDraft.value = text;
}

function cancelEdit(): void {
    editingId.value = null;
    editDraft.value = '';
}

async function saveEdit(): Promise<void> {
    if (editingId.value && (await editMessage(editingId.value, editDraft.value))) {
        cancelEdit();
    }
}

async function removeMessage(id: string): Promise<void> {
    closePopups();

    if (window.confirm('Apagar esta mensagem?')) {
        await deleteMessage(id);
    }
}

async function react(id: string, emoji: string, alreadyReacted: boolean): Promise<void> {
    closePopups();
    await toggleReaction(id, emoji, alreadyReacted);
}

// Dá o foco ao campo de edição quando ele aparece.
// Focuses the edit field when it appears.
function focusEl(el: unknown): void {
    if (el instanceof HTMLInputElement) {
        el.focus();
    }
}

function initial(name: string): string {
    return name.trim().charAt(0).toUpperCase();
}

// Um de três avatares ilustrados, conforme o id da pessoa (sempre o mesmo para a mesma pessoa).
// One of three illustrated avatars, chosen by the person's id (always the same for the same person).
function avatarClass(id: string): string {
    return `avatar-v${Number(id) % 3}`;
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
            id: message.id,
            mine: message.sender?.id === me.value?.id,
            text: message.plaintext,
            time: timeLabel(message.createdAt),
            edited: message.editedAt !== null,
            reactions: message.reactions.map((reaction) => ({
                emoji: reaction.emoji,
                count: reaction.count,
                mine: reaction.currentUserReacted,
            })),
        });
    });

    return result;
});

watch(rows, async (newRows, oldRows) => {
    if (newRows.length <= (oldRows?.length ?? 0)) {
        return;
    }

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

onBeforeUnmount(() => {
    document.removeEventListener('click', closePopups);
});

onMounted(async () => {
    document.addEventListener('click', closePopups);
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
                        <MessageCircle :size="25" fill="currentColor" />
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
                            <span :class="['chat-avatar', avatarClass(item.id)]">{{ initial(item.name) }}</span>
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
                            <span :class="['chat-avatar', 'chat-avatar-lg', avatarClass(activePerson.id)]">{{ initial(activePerson.name) }}</span>
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
                                    <span v-if="!row.mine" :class="['chat-avatar', avatarClass(activePerson.id)]">{{ initial(activePerson.name) }}</span>
                                    <div class="chat-col">
                                        <div class="chat-bubble-line">
                                            <form
                                                v-if="editingId === row.id"
                                                class="chat-edit"
                                                autocomplete="off"
                                                @submit.prevent="saveEdit"
                                            >
                                                <input
                                                    :ref="focusEl"
                                                    v-model="editDraft"
                                                    type="text"
                                                    class="chat-input chat-edit-input"
                                                    maxlength="2000"
                                                    @keydown.esc="cancelEdit"
                                                />
                                                <button type="submit" class="chat-edit-save">Guardar</button>
                                                <button type="button" class="chat-edit-cancel" @click="cancelEdit">
                                                    Cancelar
                                                </button>
                                            </form>

                                            <template v-else>
                                                <div class="chat-bubble">{{ row.text }}</div>

                                                <div class="chat-actions">
                                                    <button
                                                        type="button"
                                                        class="chat-action"
                                                        aria-label="Reagir"
                                                        @click.stop="togglePicker(row.id)"
                                                    >
                                                        <SmilePlus :size="18" />
                                                    </button>
                                                    <button
                                                        v-if="row.mine"
                                                        type="button"
                                                        class="chat-action"
                                                        aria-label="Mais opções"
                                                        @click.stop="toggleMenu(row.id)"
                                                    >
                                                        <Ellipsis :size="18" />
                                                    </button>
                                                </div>

                                                <div v-if="pickerId === row.id" class="chat-popup chat-picker" @click.stop>
                                                    <button
                                                        v-for="emoji in REACTION_EMOJIS"
                                                        :key="emoji"
                                                        type="button"
                                                        class="chat-emoji"
                                                        @click="react(row.id, emoji, row.reactions.some((r) => r.emoji === emoji && r.mine))"
                                                    >
                                                        {{ emoji }}
                                                    </button>
                                                </div>

                                                <div v-if="openMenuId === row.id" class="chat-popup chat-menu" @click.stop>
                                                    <button type="button" @click="startEdit(row.id, row.text)">
                                                        <Pencil :size="16" /> Editar
                                                    </button>
                                                    <button type="button" @click="removeMessage(row.id)">
                                                        <Trash2 :size="16" /> Apagar
                                                    </button>
                                                </div>
                                            </template>
                                        </div>

                                        <div v-if="row.reactions.length > 0" class="chat-reactions">
                                            <button
                                                v-for="reaction in row.reactions"
                                                :key="reaction.emoji"
                                                type="button"
                                                class="chat-chip"
                                                :class="{ mine: reaction.mine }"
                                                @click="react(row.id, reaction.emoji, reaction.mine)"
                                            >
                                                {{ reaction.emoji }} {{ reaction.count }}
                                            </button>
                                        </div>

                                        <small class="chat-hour">
                                            {{ row.time }}<span v-if="row.edited"> (editada)</span>
                                        </small>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <p v-if="error" class="text-danger small mb-0 pt-2 px-2">{{ error }}</p>

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
    background-color: var(--patudo-branco);
    background-position: center;
    background-repeat: no-repeat;
    background-size: cover;
    font-size: 0;
}

.chat-avatar-lg {
    width: 5.5rem;
    height: 5.5rem;
}

.avatar-v0 {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' fill='%23FFF6EA'/%3E%3Cpath d='M12 64c0-12 9-19 20-19s20 7 20 19z' fill='%234BA3C3'/%3E%3Crect x='27' y='36' width='10' height='11' rx='4' fill='%23F4A58A'/%3E%3Cellipse cx='32' cy='27' rx='10' ry='12' fill='%23F9B39A'/%3E%3Cpath d='M21 24c0-9 6-13 12-13 7 0 11 5 10 13-3-4-7-5-11-5s-8 1-11 5z' fill='%23495C85'/%3E%3C/svg%3E");
}

.avatar-v1 {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' fill='%23FFF6EA'/%3E%3Cpath d='M18 28c0-13 6-19 14-19s14 6 14 19v14H18z' fill='%23F25C66'/%3E%3Cpath d='M14 64c0-11 8-17 18-17s18 6 18 17z' fill='%23F6D9B8'/%3E%3Crect x='27' y='38' width='10' height='11' rx='4' fill='%23F4B796'/%3E%3Cellipse cx='32' cy='29' rx='10' ry='12' fill='%23FBC9AA'/%3E%3Cpath d='M21 26c2-9 7-11 11-11s9 3 11 11c-4-4-8-5-11-5s-7 1-11 5z' fill='%23F25C66'/%3E%3C/svg%3E");
}

.avatar-v2 {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' fill='%23FFF6EA'/%3E%3Cpath d='M12 64c0-12 9-19 20-19s20 7 20 19z' fill='%234FCB8B'/%3E%3Crect x='27' y='36' width='10' height='11' rx='4' fill='%23E8963F'/%3E%3Cellipse cx='32' cy='27' rx='10' ry='12' fill='%23F6B054'/%3E%3Cpath d='M21 24c0-9 5-13 11-13s11 4 11 13c-3-3-7-4-11-4s-8 1-11 4z' fill='%236B4A2B'/%3E%3C/svg%3E");
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

.chat-bubble-line {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.chat-row.mine .chat-bubble-line {
    flex-direction: row-reverse;
}

.chat-actions {
    display: flex;
    gap: 0.15rem;
    opacity: 0.35;
    transition: opacity 0.15s;
}

.chat-bubble-line:hover .chat-actions,
.chat-actions:focus-within {
    opacity: 1;
}

.chat-action {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.8rem;
    height: 1.8rem;
    padding: 0;
    border: 0;
    border-radius: 50%;
    background: transparent;
    color: var(--patudo-castanhoMid);
}

.chat-action:hover {
    background: var(--patudo-bege);
    color: var(--patudo-castanhoDark);
}

.chat-popup {
    position: absolute;
    top: 100%;
    z-index: 5;
    margin-top: 0.25rem;
    border: 1px solid var(--bs-primary);
    border-radius: 0.75rem;
    background: var(--patudo-branco);
    box-shadow: 0 4px 14px rgb(46 29 20 / 15%);
}

.chat-row.theirs .chat-popup {
    left: 0;
}

.chat-row.mine .chat-popup {
    right: 0;
}

.chat-picker {
    display: flex;
    gap: 0.15rem;
    padding: 0.3rem 0.4rem;
}

.chat-emoji {
    padding: 0.1rem 0.3rem;
    border: 0;
    border-radius: 0.5rem;
    background: transparent;
    font-size: 1.3rem;
    line-height: 1.4;
}

.chat-emoji:hover {
    background: var(--patudo-bege);
}

.chat-menu {
    display: flex;
    flex-direction: column;
    min-width: 8rem;
    padding: 0.25rem;
}

.chat-menu button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.6rem;
    border: 0;
    border-radius: 0.5rem;
    background: transparent;
    color: var(--patudo-castanhoDark);
    text-align: left;
}

.chat-menu button:hover {
    background: var(--patudo-bege);
}

.chat-reactions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
    margin-top: 0.35rem;
}

.chat-chip {
    padding: 0.05rem 0.55rem;
    border: 1px solid var(--bs-primary);
    border-radius: 999px;
    background: transparent;
    color: inherit;
    font-size: 0.9rem;
}

.chat-chip.mine {
    background: var(--patudo-bege);
    font-weight: 700;
}

.chat-edit {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.chat-edit-input {
    flex: 1;
    min-width: 12rem;
    height: 2.6rem;
}

.chat-edit-save,
.chat-edit-cancel {
    padding: 0.35rem 0.8rem;
    border-radius: 0.5rem;
    font-size: 0.9rem;
}

.chat-edit-save {
    border: 0;
    background: var(--bs-primary);
    color: #fff;
}

.chat-edit-cancel {
    border: 1px solid var(--bs-primary);
    background: transparent;
    color: inherit;
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