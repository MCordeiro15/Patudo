<script setup lang="ts">
// Página de mensagens: lista de conversas à esquerda e a caixa de chat do TalkJS (<t-chatbox>) à direita.
// Messages page: conversation list on the left and the TalkJS chat box (<t-chatbox>) on the right.
//Que Deus abençoe a todos que estão lendo este código. Amém.
// May God bless all who are reading this code. Amen.
// Que Deus abençoe todos os desenvolvedores que trabalham neste projeto. Amém.
// May God bless all the developers working on this project. Amen.
import '@talkjs/web-components';
import '@talkjs/web-components/default.css';
import { Head, Link } from '@inertiajs/vue3';
import { Bell, LogOut, MessageCircle, UserRound } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import { useChat } from '@/composables/useChat';
import { useNotifications } from '@/composables/useNotifications';
import ModalNotifications from '@/pages/Modals/ModalNotifications.vue';

const showNotifications = ref(false);
const { unreadCount, fetchNotifications } = useNotifications();

const {
    me,
    appId,
    items,
    activeOtherId,
    activeConversationId,
    activePerson,
    activeOnline,
    tokenFetcher,
    ready,
    error,
    start,
    openConversation,
    canOpen,
} = useChat();

function initial(name: string): string {
    return name.trim().charAt(0).toUpperCase();
}

// Um de três avatares ilustrados, conforme o id da pessoa (sempre o mesmo para a mesma pessoa).
// One of three illustrated avatars, chosen by the person's id (always the same for the same person).
function avatarClass(id: string): string {
    return `avatar-v${Number(id) % 3}`;
}

// Foto de perfil por cima do avatar ilustrado, quando o utilizador tem foto.
// Profile photo over the illustrated avatar, when the user has one.
function photoStyle(photoUrl?: string | null): Record<string, string> | undefined {
    return photoUrl ? { backgroundImage: `url("${photoUrl}")` } : undefined;
}

function timeLabel(timestamp: number): string {
    return new Date(timestamp).toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' });
}

onMounted(async () => {
    void fetchNotifications();
    await start();

    // /messages?with=ID abre logo a conversa com essa pessoa (ex.: botão "Chat" no perfil).
    // /messages?with=ID opens the conversation with that person straight away.
    const withId = new URLSearchParams(window.location.search).get('with');

    // a lista só mostra conversas com mensagens, por isso verifica-se nos contactos/conversas
    if (withId && canOpen(withId)) {
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
                            <span :class="['chat-avatar', avatarClass(item.id)]" :style="photoStyle(item.photo_url)">{{ initial(item.name) }}</span>
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
                            <span :class="['chat-avatar', 'chat-avatar-lg', avatarClass(activePerson.id)]" :style="photoStyle(activePerson.photo_url)">{{ initial(activePerson.name) }}</span>
                            <div>
                                <strong class="chat-header-name">{{ activePerson.name }} • {{ activePerson.role }}</strong>
                                <span class="chat-status">
                                    <i class="chat-dot" :class="{ online: activeOnline }"></i>
                                    {{ activeOnline ? 'Online agora' : 'Offline' }}
                                </span>
                            </div>
                        </div>

                        <p v-if="error" class="text-danger small mb-0 pb-2 px-2">{{ error }}</p>

                        

                        <!-- Caixa de chat do TalkJS: envia, edita, apaga, reage e anexa ficheiros. -->
                        <!-- TalkJS chat box: sends, edits, deletes, reacts and attaches files. -->
                        <t-chatbox
                            v-if="appId && me && activeConversationId"
                            :key="activeConversationId"
                            class="chat-box"
                            :app-id="appId"
                            :user-id="me.id"
                            :conversation-id="activeConversationId"
                            :token-fetcher.prop="tokenFetcher"
                            :chat-header-visible.prop="false"
                            message-field-placeholder="Escrever mensagem..."
                        />
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
    background-color: white;
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

.chat-box {
    display: block;
    flex: 1;
    width: 100%;
    min-height: 0;
    overflow: hidden;
    background-color: white;
    /* o TalkJS põe uma moldura à volta da caixa; o mockup não tem */
    border: 0;

    --t-color-brand-primary: var(--bs-primary);
    --t-color-bg: var(--patudo-branco);
    --t-color-text: var(--patudo-castanhoDark);
    --t-color-border: var(--bs-primary);
    --t-radius-md: 0.75rem;
    --t-font-family: inherit;
}

/* Estilos da caixa do TalkJS para ficar igual ao mockup (o TalkJS desenha o HTML, por isso usa-se :deep). */
/* TalkJS box styles to match the mockup (TalkJS renders the HTML, hence :deep). */

/* separador de data ("Hoje"): só a pastilha, sem as linhas */
.chat-box :deep(.t-message-divider .t-line) {
    visibility: hidden;
}

.chat-box :deep(.t-message-divider > span) {
    height: auto;
    padding: 0.2rem 2rem;
    color: var(--patudo-castanhoDark);
    background: var(--patudo-bege);
    border: 1px solid var(--patudo-terracota);
    border-radius: 999px;
}

/* mensagens recebidas: bege com contorno, com a foto à esquerda */
.chat-box :deep(.t-theme-message .t-message-body) {
    padding: 0.5rem 1rem;
    color: var(--patudo-castanhoDark);
    background-color: var(--patudo-bege);
    border: 1px solid var(--patudo-terracota);
    border-radius: 0.5rem;
    font-size: 1rem;
    
}

.chat-box :deep(.t-theme-message > .t-avatar-wrapper > .t-theme-avatar) {
    width: 48px;
    height: 48px;
    border: 1px solid var(--patudo-castanhoDark);
    border-radius: 50%;
}

/* mensagens enviadas: castanho-escuro, sem foto */
.chat-box :deep(.t-theme-message[t-sender='currentUser'] .t-message-body) {
    color: #fff;
    background-color: var(--patudo-castanhoDark);
    border-color: var(--patudo-castanhoDark);
}

.chat-box :deep(.t-theme-message[t-sender='currentUser'] > .t-avatar-wrapper) {
    display: none;
}

.chat-box :deep(.t-theme-message[t-sender='currentUser'] .t-message-status) {
    justify-self: end;
}

/* campo de escrever: sem linha por cima, caixa arredondada e botões redondos */
.chat-box :deep(.t-theme-message-field .t-wrapper) {
    border-top: 0;
    background-color: white;
}

.chat-box :deep(.t-theme-message-field .t-editor) {
    border: 1px solid var(--patudo-terracota);
    border-radius: 0.6rem;
    background: #fff;
}

/* a caixa de texto lá dentro tem outra borda cinzenta: fica só a de fora */
.chat-box :deep(.t-theme-message-field .t-text-input) {
    border: 0;
}

.chat-box :deep(.t-theme-message-field .t-attachment-button) {
    width: 2.6rem;
    height: 2.6rem;
    padding: 0.55rem;
    color: var(--patudo-castanhoDark);
    background: #fff;
    border: 1px solid var(--patudo-terracota);
    border-radius: 50%;
}

.chat-box :deep(.t-theme-message-field .t-send-button),
.chat-box :deep(.t-theme-message-field .t-record-button) {
    width: 2.6rem;
    height: 2.6rem;
    border-radius: 50%;
}

.chat-box :deep(.t-theme-message-action-menu) {
    min-width: 180px;
    padding: 0.35rem;
    background: #fff;
    border: 1px solid var(--patudo-terracota);
    border-radius: 0.75rem;
    box-shadow: 0 4px 12px rgb(0 0 0 / 12%);
}

.chat-box :deep(.t-theme-message-action-menu .t-menu-item) {
    color: var(--patudo-castanhoDark);
    border-radius: 0.5rem;
}

.chat-box :deep(.t-theme-message-action-menu .t-menu-item-icon-box) {
    color: var(--patudo-castanhoDark);
    background: #fff;
    border: 1px solid var(--patudo-terracota);
    border-radius: 0.5rem;
}

.chat-box :deep(.t-theme-message-action-menu .t-menu-item:hover),
.chat-box :deep(.t-theme-message-action-menu .t-menu-item:focus) {
    background: var(--patudo-bege);
}

.chat-box :deep(.t-theme-message-action-menu .t-menu-item:hover .t-menu-item-icon-box),
.chat-box :deep(.t-theme-message-action-menu .t-menu-item:focus .t-menu-item-icon-box) {
    background: #fff;
    border-color: var(--patudo-terracota);
}

.chat-box :deep(.t-theme-message-action-menu .t-menu-item:hover .t-menu-item-icon),
.chat-box :deep(.t-theme-message-action-menu .t-menu-item:focus .t-menu-item-icon) {
    color: var(--patudo-castanhoDark);
}

@media (max-width: 991px) {
    .chat-layout {
        grid-template-columns: 1fr;
        height: auto;
    }

    .chat-box {
        min-height: 420px;
    }
}
</style>