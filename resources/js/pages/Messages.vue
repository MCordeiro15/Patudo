<script setup lang="ts">
// Página de mensagens: lista de conversas à esquerda e a caixa de chat do TalkJS (<t-chatbox>) à direita.
// Messages page: conversation list on the left and the TalkJS chat box (<t-chatbox>) on the right.
//Que Deus abençoe a todos que estão lendo este código. Amém.
// May God bless all who are reading this code. Amen.
// Que Deus abençoe todos os desenvolvedores que trabalham neste projeto. Amém.
// May God bless all the developers working on this project. Amen.
import '@talkjs/web-components';
import '@talkjs/web-components/default.css';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Bell, LogOut, MessageCircle, UserRound } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import AdminNavigationMenu from '@/components/AdminNavigationMenu.vue';
import { useChat } from '@/composables/useChat';
import { useInitials } from '@/composables/useInitials';
import { useNotifications } from '@/composables/useNotifications';
import ModalNotifications from '@/pages/Modals/ModalNotifications.vue';

const showNotifications = ref(false);
const { unreadCount, fetchNotifications } = useNotifications();
const page = usePage<{ auth: { isAdmin: boolean; user: { is_petsitter?: boolean } | null } }>();
const viewerIsPetsitter = computed(() => page.props.auth.user?.is_petsitter === true);
const showOwnerNavigation = computed(() => !viewerIsPetsitter.value || page.props.auth.isAdmin);

const {
    me,
    appId,
    items,
    activeOtherId,
    activeConversationId,
    activePerson,
    activeOnline,
    tokenFetcher,
    totalUnread,
    ready,
    error,
    start,
    openConversation,
    canOpen,
} = useChat();
const { getInitials } = useInitials();

const avatarColors = [
    { background: '#ffed5b', foreground: '#111827' },
    { background: '#62df7e', foreground: '#111827' },
    { background: '#50b9a5', foreground: '#111827' },
    { background: '#d1df50', foreground: '#111827' },
    { background: '#a52352', foreground: '#ffffff' },
];

function avatarStyle(id: string, photoUrl?: string | null): Record<string, string> {
    if (photoUrl) {
        return { backgroundImage: `url("${photoUrl}")` };
    }

    const numericId = Number(id);
    const colorIndex = Number.isFinite(numericId) ? Math.abs(numericId) % avatarColors.length : 0;
    const color = avatarColors[colorIndex] ?? avatarColors[0]!;

    return { backgroundColor: color.background, color: color.foreground };
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

    <main class="min-vh-100 py-4 patudo-page-shell">
        <div class="container ">
            <!-- Barra de navegação, igual à das outras páginas. -->
            <!-- Navigation bar, same as the other pages. -->
            <nav class="navbar navbar-expand-lg bg-white border border-primary rounded-4 px-3 px-md-4">
                <a class="navbar-brand fw-bold fs-3 d-flex align-items-center gap-2" href="/">
                    <img :src="logoPatudo" alt="Patudo" width="70" height="70" />
                    <span>Patudo</span>
                </a>

                <div class="nav nav-pills border rounded-pill p-1 mx-auto">
                    <a v-if="showOwnerNavigation" class="nav-link text-dark rounded-pill px-4" href="/">Início</a>
                    <a class="nav-link text-dark rounded-pill px-4" href="/orders">Pedidos</a>
                    <a v-if="showOwnerNavigation" class="nav-link text-dark rounded-pill px-4" href="/favorites">Favoritos</a>
                    <a class="nav-link text-dark rounded-pill px-4" href="/reservations">Reservas</a>
                    <AdminNavigationMenu />
                </div>

                <div class="d-flex align-items-center gap-3">
                    <a
                        href="/messages"
                        class="text-dark bg-transparent border-0 p-0 position-relative"
                        :aria-label="totalUnread > 0 ? `Mensagens, ${totalUnread} por ler` : 'Mensagens'"
                    >
                        <MessageCircle :size="25" fill="none" />
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
                            <span
                                :class="['chat-avatar', { 'chat-avatar-with-photo': item.photo_url }]"
                                :style="avatarStyle(item.id, item.photo_url)"
                            >{{ item.photo_url ? '' : getInitials(item.name) }}</span>
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
                            <span
                                :class="['chat-avatar', 'chat-avatar-lg', { 'chat-avatar-with-photo': activePerson.photo_url }]"
                                :style="avatarStyle(activePerson.id, activePerson.photo_url)"
                            >{{ activePerson.photo_url ? '' : getInitials(activePerson.name) }}</span>
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
    border-radius: 50%;
    background-color: var(--patudo-branco);
    background-position: center;
    background-repeat: no-repeat;
    background-size: cover;
    font-size: 1.2rem;
    font-weight: 600;
}

.chat-avatar-lg {
    width: 5.5rem;
    height: 5.5rem;
    font-size: 1.8rem;
}

.chat-avatar-with-photo {
    border: 1px solid var(--patudo-castanhoDark);
    font-size: 0;
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

.chat-box :deep(.t-theme-message-field .t-record-button) {
    --t-color-brand-primary: var(--patudo-terracota);
    --t-color-brand-primary-hover: var(--patudo-laranjaVivo);
    --t-color-text-inverse: #ffffff;
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