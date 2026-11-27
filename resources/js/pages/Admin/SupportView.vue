<!-- Página do administrador para responder aos pedidos de ajuda (versão só front-end, dados falsos). -->
<!-- Admin page to answer help requests (front-end only version, mock data). -->
<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ChevronRight, Search } from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';

type Status = 'open' | 'in_progress' | 'closed';

interface Message {
    fromAdmin: boolean;
    body: string;
    time: string;
}

interface Ticket {
    id: number;
    subject: string; // texto curto na lista
    title: string; // título no detalhe
    userName: string;
    role: string;
    suspended: boolean;
    listAgo: string; // "há 2 h"
    detailAgo: string; // "há 2 horas"
    status: Status;
    isNew: boolean;
    messages: Message[];
}

// ---------- DADOS FALSOS (substituir pela API quando o back-end existir) ----------
const tickets = ref<Ticket[]>([
    {
        id: 87,
        subject: 'Conta suspensa',
        title: 'Pedido de revisão da suspensão',
        userName: 'Nome do utilizador',
        role: 'Cuidador',
        suspended: true,
        listAgo: 'há 2 h',
        detailAgo: 'há 2 horas',
        status: 'open',
        isNew: true,
        messages: [
            { fromAdmin: false, body: 'Boa tarde, a minha conta foi suspensa mas\nacho que foi um engano. Podem rever?', time: '14:20' },
            { fromAdmin: true, body: 'Olá! Vamos rever o caso e respondemos em 24 h.', time: '14:35' },
        ],
    },
    {
        id: 84,
        subject: 'Erro ao fazer uma reserva',
        title: 'Erro ao fazer uma reserva',
        userName: 'Nome do utilizador',
        role: 'Dono',
        suspended: false,
        listAgo: 'há 1 dia',
        detailAgo: 'há 1 dia',
        status: 'open',
        isNew: false,
        messages: [{ fromAdmin: false, body: 'Quando tento confirmar a reserva aparece uma mensagem de erro.', time: '10:05' }],
    },
    {
        id: 79,
        subject: 'Dúvida sobre verificação',
        title: 'Dúvida sobre verificação',
        userName: 'Nome do utilizador',
        role: 'Cuidador',
        suspended: false,
        listAgo: 'há 2 dias',
        detailAgo: 'há 2 dias',
        status: 'open',
        isNew: false,
        messages: [{ fromAdmin: false, body: 'Que documentos preciso de enviar para ser verificado?', time: '09:12' }],
    },
    {
        id: 75,
        subject: 'Não consigo entrar',
        title: 'Não consigo entrar na conta',
        userName: 'Nome do utilizador',
        role: 'Dono',
        suspended: false,
        listAgo: 'há 3 dias',
        detailAgo: 'há 3 dias',
        status: 'open',
        isNew: false,
        messages: [{ fromAdmin: false, body: 'Esqueci-me da palavra-passe e o email de recuperação não chega.', time: '18:40' }],
    },
    {
        id: 72,
        subject: 'Pagamento não recebido',
        title: 'Pagamento não recebido',
        userName: 'Nome do utilizador',
        role: 'Cuidador',
        suspended: false,
        listAgo: 'há 4 dias',
        detailAgo: 'há 4 dias',
        status: 'in_progress',
        isNew: false,
        messages: [
            { fromAdmin: false, body: 'Ainda não recebi o pagamento da última reserva.', time: '11:30' },
            { fromAdmin: true, body: 'Estamos a verificar com a equipa financeira.', time: '12:10' },
        ],
    },
    {
        id: 60,
        subject: 'Alterar email da conta',
        title: 'Alterar email da conta',
        userName: 'Nome do utilizador',
        role: 'Dono',
        suspended: false,
        listAgo: 'há 1 semana',
        detailAgo: 'há 1 semana',
        status: 'closed',
        isNew: false,
        messages: [
            { fromAdmin: false, body: 'Como posso mudar o email da minha conta?', time: '16:02' },
            { fromAdmin: true, body: 'Já podes alterar em Definições. Dá-nos nota se precisares de mais ajuda.', time: '16:20' },
        ],
    },
]);
// ---------------------------------------------------------------------------------

const menu = [
    { label: 'Utilizadores' },
    { label: 'Verificações', badge: 5 },
    { label: 'Reservas' },
    { label: 'Avaliações' },
    { label: 'Denúncias', badge: 3 },
    { label: 'Suporte', active: true },
    { label: 'Definições' },
];

const tabs: { key: Status; label: string }[] = [
    { key: 'open', label: 'Abertos' },
    { key: 'in_progress', label: 'Em curso' },
    { key: 'closed', label: 'Fechados' },
];

const statusLabels: Record<Status, string> = { open: 'Aberto', in_progress: 'Em curso', closed: 'Fechado' };

const tab = ref<Status>('open');
const search = ref('');
const selectedId = ref<number | null>(87);
const reply = ref('');
const messagesBox = ref<HTMLElement | null>(null);

const openCount = computed(() => tickets.value.filter((t) => t.status === 'open').length);

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();
    return tickets.value
        .filter((t) => t.status === tab.value)
        .filter((t) => !term || t.subject.toLowerCase().includes(term) || t.userName.toLowerCase().includes(term));
});

const selected = computed(() => tickets.value.find((t) => t.id === selectedId.value) ?? null);

const userSubtitle = (t: Ticket) => (t.suspended ? `${t.role} · Conta suspensa` : t.role);

function setTab(key: Status) {
    tab.value = key;
    selectedId.value = filtered.value[0]?.id ?? null;
}

async function scrollToEnd() {
    await nextTick();
    if (messagesBox.value) messagesBox.value.scrollTop = messagesBox.value.scrollHeight;
}

function select(t: Ticket) {
    selectedId.value = t.id;
    scrollToEnd();
}

function sendReply() {
    const t = selected.value;
    const body = reply.value.trim();
    if (!t || !body || t.status === 'closed') return;

    const time = new Date().toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' });
    t.messages.push({ fromAdmin: true, body, time });
    // Ao responder, o pedido passa a "Em curso" (só visual por agora).
    if (t.status === 'open') t.status = 'in_progress';
    t.isNew = false;
    tab.value = t.status;
    reply.value = '';
    scrollToEnd();
}

function closeTicket() {
    const t = selected.value;
    if (!t) return;
    t.status = 'closed';
    t.isNew = false;
    tab.value = 'closed';
}

const logout = () => router.post('/logout');
</script>

<template>
    <Head title="Suporte" />

    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div>
                <div class="brand">
                    <img :src="logoPatudo" alt="Patudo" width="36" height="36" />
                    <span>Patudo</span>
                </div>
                <p class="menu-label">ADMINISTRAÇÃO</p>
                <nav class="menu">
                    <div v-for="item in menu" :key="item.label" class="menu-item" :class="{ active: item.active }">
                        <span>{{ item.label }}</span>
                        <span v-if="item.active && openCount" class="menu-badge">{{ openCount }}</span>
                        <span v-else-if="!item.active && item.badge" class="menu-badge">{{ item.badge }}</span>
                    </div>
                </nav>
            </div>
            <button type="button" class="logout" @click="logout">Terminar sessão</button>
        </aside>

        <main class="admin-main">
            <header class="main-header">
                <h1>Suporte</h1>
                <div class="admin-user">
                    <span>Administrador</span>
                    <div class="avatar avatar-admin"></div>
                </div>
            </header>

            <div class="toolbar">
                <div class="tabs">
                    <button
                        v-for="t in tabs"
                        :key="t.key"
                        type="button"
                        class="tab"
                        :class="{ active: tab === t.key }"
                        @click="setTab(t.key)"
                    >
                        {{ t.label }}<template v-if="t.key === 'open'"> ({{ openCount }})</template>
                    </button>
                </div>
                <label class="search">
                    <Search :size="22" />
                    <input v-model="search" type="text" placeholder="Pesquisar pedido" />
                </label>
            </div>

            <div class="content">
                <div class="list-col">
                    <p v-if="filtered.length === 0" class="state">Não existem pedidos nesta lista.</p>
                    <button
                        v-for="t in filtered"
                        :key="t.id"
                        type="button"
                        class="list-item"
                        :class="{ selected: t.id === selectedId }"
                        @click="select(t)"
                    >
                        <div class="list-text">
                            <strong>{{ t.subject }}</strong>
                            <small>{{ t.userName }} · {{ t.listAgo }}</small>
                        </div>
                        <span v-if="t.isNew" class="badge-new">Nova</span>
                        <ChevronRight v-else :size="24" />
                    </button>
                </div>

                <section class="detail">
                    <template v-if="selected">
                        <div class="detail-head">
                            <div class="detail-title">
                                <h2>{{ selected.title }}</h2>
                                <small>Pedido #{{ String(selected.id).padStart(4, '0') }} · {{ selected.detailAgo }}</small>
                            </div>
                            <span class="status-pill"><i class="dot"></i>{{ statusLabels[selected.status] }}</span>
                        </div>

                        <div class="user-card">
                            <div class="avatar avatar-user"></div>
                            <div class="user-text">
                                <strong>{{ selected.userName }}</strong>
                                <small>{{ userSubtitle(selected) }}</small>
                            </div>
                            <button type="button" class="view-account">Ver conta ›</button>
                        </div>

                        <div ref="messagesBox" class="messages">
                            <div v-for="(m, i) in selected.messages" :key="i" class="message-row" :class="{ mine: m.fromAdmin }">
                                <div class="bubble" :class="{ mine: m.fromAdmin }">{{ m.body }}</div>
                                <small class="meta">{{ m.fromAdmin ? 'Administração' : 'Utilizador' }} · {{ m.time }}</small>
                            </div>
                        </div>

                        <label class="reply-label" for="reply">Responder ao utilizador</label>
                        <textarea
                            id="reply"
                            v-model="reply"
                            class="reply-box"
                            rows="2"
                            placeholder="Escreve a tua resposta..."
                            :disabled="selected.status === 'closed'"
                        ></textarea>

                        <div class="actions">
                            <button type="button" class="btn-outline" :disabled="selected.status === 'closed'" @click="closeTicket">
                                Fechar pedido
                            </button>
                            <button
                                type="button"
                                class="btn-solid"
                                @click="sendReply"
                            >
                                Responder
                            </button>
                        </div>
                    </template>
                    <p v-else class="state">Seleciona um pedido para ver os detalhes.</p>
                </section>
            </div>
        </main>
    </div>
</template>

<style scoped>
.admin-shell {
    display: flex;
    min-height: 100vh;
    background-color: #fffaf3;
    color: #2e1d14;
}

.admin-sidebar {
    width: 186px;
    flex-shrink: 0;
    background-color: #fff;
    border-right: 1px solid #f3b78a;
    padding: 28px 12px 22px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.brand {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 1.3rem;
    font-weight: 600;
    padding: 0 4px;
}

.menu-label {
    font-size: 0.62rem;
    margin: 26px 12px 12px;
    color: #5b4638;
}

.menu-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    font-size: 0.82rem;
    font-weight: 700;
    border-radius: 999px;
    margin-bottom: 6px;
}

.menu-item.active {
    background-color: #2e1d14;
    color: #fff;
}

.menu-badge {
    background-color: #c2410c;
    color: #fff;
    font-size: 0.68rem;
    font-weight: 700;
    border-radius: 999px;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.logout {
    background: none;
    border: 0;
    border-top: 1px solid #f3b78a;
    padding: 20px 16px 0;
    font-size: 0.82rem;
    font-weight: 700;
    text-align: left;
    color: inherit;
}

.admin-main {
    flex: 1;
    min-width: 0;
    padding: 36px 26px 30px;
}

.main-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

h1 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}

.admin-user {
    display: flex;
    align-items: center;
    gap: 14px;
    font-size: 0.8rem;
    font-weight: 700;
}

.avatar {
    border-radius: 50%;
    background-color: #fed7aa;
    flex-shrink: 0;
}

.avatar-admin {
    width: 34px;
    height: 34px;
    border: 1.5px solid #2e1d14;
}

.avatar-user {
    width: 36px;
    height: 36px;
    border: 1.5px solid #c2410c;
}

.toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 26px 0 18px;
}

.tabs {
    display: flex;
    gap: 10px;
}

.tab {
    border: 1.5px solid #c2410c;
    background-color: #fff;
    color: #2e1d14;
    border-radius: 999px;
    padding: 7px 22px;
    font-size: 0.75rem;
    font-weight: 700;
}

.tab.active {
    background-color: #c2410c;
    border-color: #c2410c;
    color: #fff;
}

.search {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1.5px solid #c2410c;
    background-color: #fff;
    border-radius: 999px;
    padding: 6px 16px;
    width: 300px;
}

.search input {
    border: 0;
    outline: 0;
    background: transparent;
    font-size: 0.75rem;
    width: 100%;
}

.content {
    display: flex;
    gap: 26px;
    align-items: flex-start;
}

.list-col {
    width: 280px;
    flex-shrink: 0;
}

.list-item {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    background-color: #fffaf3;
    border: 1.5px solid #f3b78a;
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 10px;
    text-align: left;
    color: inherit;
}

.list-item.selected {
    border: 1.5px solid #2e1d14;
}

.list-text {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.list-text strong {
    font-size: 0.82rem;
    font-weight: 700;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.list-text small {
    font-size: 0.62rem;
    color: #5b4638;
}

.badge-new {
    background-color: #c2410c;
    color: #fff;
    font-size: 0.65rem;
    font-weight: 700;
    border-radius: 999px;
    padding: 3px 14px;
}

.state {
    font-size: 0.8rem;
    color: #7a6657;
    text-align: center;
    padding: 16px 0;
    margin: 0;
}

.detail {
    flex: 1;
    min-width: 0;
    background-color: #fffaf3;
    border: 1.5px solid #c2410c;
    border-radius: 16px;
    padding: 20px 22px;
}

.detail-head {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f3b78a;
}

.detail-title {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.detail-title h2 {
    font-size: 1.05rem;
    font-weight: 700;
    margin: 0;
}

.detail-title small {
    font-size: 0.68rem;
    color: #5b4638;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 1.5px solid #c2410c;
    border-radius: 999px;
    padding: 5px 20px;
    font-size: 0.72rem;
    font-weight: 700;
}

.dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #c2410c;
}

.user-card {
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1.5px solid #f3b78a;
    border-radius: 12px;
    padding: 10px 14px;
    margin: 14px 0 18px;
}

.user-text {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.user-text strong {
    font-size: 0.82rem;
    font-weight: 700;
}

.user-text small {
    font-size: 0.65rem;
    color: #5b4638;
}

.view-account {
    background: none;
    border: 0;
    color: #c2410c;
    font-size: 0.65rem;
    font-weight: 700;
    text-decoration: underline;
}

.messages {
    display: flex;
    flex-direction: column;
    gap: 14px;
    max-height: 280px;
    overflow-y: auto;
    padding: 2px 2px 8px;
}

.message-row {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 5px;
}

.message-row.mine {
    align-items: flex-end;
}

.bubble {
    max-width: 75%;
    background-color: #fffaf3;
    border: 1.5px solid #f3b78a;
    border-radius: 12px;
    padding: 12px 16px;
    font-size: 0.75rem;
    line-height: 1.5;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.bubble.mine {
    background-color: #c2410c;
    border-color: #c2410c;
    color: #fff;
}

.meta {
    font-size: 0.6rem;
    color: #5b4638;
}

.reply-label {
    display: block;
    font-size: 0.7rem;
    font-weight: 700;
    margin: 22px 0 8px;
}

.reply-box {
    width: 100%;
    resize: none;
    background-color: #fffaf3;
    border: 1.5px solid #c2410c;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 0.75rem;
    color: #2e1d14;
}

.reply-box::placeholder {
    color: #8a7a6c;
}

.reply-box:disabled {
    opacity: 0.6;
}

.actions {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
}

.btn-outline,
.btn-solid {
    border: 1.5px solid #c2410c;
    border-radius: 999px;
    padding: 10px 28px;
    font-size: 0.78rem;
    font-weight: 700;
}

.btn-outline {
    background-color: #fffaf3;
    color: #c2410c;
}

.btn-solid {
    background-color: #c2410c;
    color: #fff;
    padding: 10px 40px;
}

.btn-outline:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

button:focus-visible,
textarea:focus-visible {
    outline: 2px solid #2e1d14;
    outline-offset: 2px;
}

@media (max-width: 900px) {
    .content {
        flex-direction: column;
    }

    .list-col {
        width: 100%;
    }

    .admin-sidebar {
        display: none;
    }
}
</style>