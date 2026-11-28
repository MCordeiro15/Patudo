<!-- Página do administrador para verificar (aprovar/recusar) candidaturas de petsitters. -->
<!-- Admin page to verify (approve/reject) petsitter applications. -->
<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    ChevronRight,
    CircleCheck,
    CircleX,
    Clock,
    FileText,
    Search,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import logoPatudo from '../../../../assets/logoPatudo.svg';
import ModalRejectVerification from '@/pages/Modals/ModalRejectVerification.vue';

type Status = 'pending' | 'approved' | 'rejected';

interface Candidate {
    user_id: number;
    avatar_url?: string | null;
    city?: string | null;
    service_location?: string | null;
    experience?: string | null;
    created_at?: string;
    approval_status?: Status;
    services?: { id: number; type: string }[];
    user?: { name?: string; email?: string; phone?: string | null };
    // caminho do documento de identificação (só o admin o recebe da API)
    identification_documents?: string | null;
}

function getXsrf(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

function apiFetch(url: string, options: RequestInit = {}) {
    return fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getXsrf(),
            ...options.headers,
        },
    });
}

// Itens da sidebar sem página ainda: ficam em mock (sem ligação).
const menu = [
    { label: 'Utilizadores' },
    { label: 'Verificações', active: true },
    { label: 'Reservas' },
    { label: 'Avaliações' },
    { label: 'Denúncias', badge: 3 },
    { label: 'Suporte', badge: 2 },
    { label: 'Relatórios' },
    { label: 'Definições' },
];

const tabs: { key: Status; label: string }[] = [
    { key: 'pending', label: 'Pendentes' },
    { key: 'approved', label: 'Aprovadas' },
    { key: 'rejected', label: 'Recusadas' },
];

const statusLabels: Record<Status, string> = {
    pending: 'Pendente',
    approved: 'Aprovado',
    rejected: 'Recusado',
};
const statusIcons = {
    pending: Clock,
    approved: CircleCheck,
    rejected: CircleX,
};

const tab = ref<Status>('pending');
const candidates = ref<Candidate[]>([]);
const pendingCount = ref(0);
const selectedId = ref<number | null>(null);
const search = ref('');
const loading = ref(true);
const loadError = ref('');
const busy = ref(false);
const actionError = ref('');
const showReject = ref(false);

// A lista de verificação ainda não é guardada no servidor.
const checklist = ref([
    { label: 'A identidade confere com o nome do perfil', checked: true },
    { label: 'O documento é legível e está válido', checked: true },
    { label: 'As fotografias do perfil são adequadas', checked: false },
    { label: 'A descrição não tem contactos externos', checked: false },
]);

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();
    return term
        ? candidates.value.filter((c) =>
              (c.user?.name ?? '').toLowerCase().includes(term),
          )
        : candidates.value;
});

const selected = computed(
    () => candidates.value.find((c) => c.user_id === selectedId.value) ?? null,
);

const subtitle = computed(() => {
    const n = pendingCount.value;
    return `${n} ${n === 1 ? 'cuidador' : 'cuidadores'} à espera de aprovação`;
});

const daysAgo = (iso?: string) => {
    if (!iso) return '';
    let date: Date;
    if (iso.includes('T')) {
        date = new Date(iso);
    } else if (iso.match(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/)) {
        date = new Date(iso.replace(' ', 'T') + 'Z');
    } else if (iso.match(/^\d{4}-\d{2}-\d{2}$/)) {
        date = new Date(iso + 'T00:00:00Z');
    } else {
        date = new Date(iso);
    }
    if (isNaN(date.getTime())) return '';
    const days = Math.max(
        0,
        Math.floor((Date.now() - date.getTime()) / 86400000),
    );
    return days === 0
        ? 'Pedido hoje'
        : `Pedido há ${days} ${days === 1 ? 'dia' : 'dias'}`;
};

function parseDate(iso?: string): Date | null {
    if (!iso) return null;
    let date: Date;
    if (iso.includes('T')) {
        date = new Date(iso);
    } else if (iso.match(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/)) {
        date = new Date(iso.replace(' ', 'T') + 'Z');
    } else if (iso.match(/^\d{4}-\d{2}-\d{2}$/)) {
        date = new Date(iso + 'T00:00:00Z');
    } else {
        date = new Date(iso);
    }
    return isNaN(date.getTime()) ? null : date;
}

const formatDate = (iso?: string) => {
    const date = parseDate(iso);
    return date ? date.toLocaleDateString('pt-PT') : '';
};

const load = async () => {
    loading.value = true;
    loadError.value = '';
    try {
        const res = await apiFetch(
            `/api/petsitters?approval_status=${tab.value}`,
        );
        if (!res.ok) throw new Error();
        const data = await res.json();
        candidates.value = data.data ?? data;
        if (tab.value === 'pending')
            pendingCount.value = candidates.value.length;
        selectedId.value = candidates.value[0]?.user_id ?? null;
    } catch {
        loadError.value = 'Não foi possível carregar os cuidadores.';
        candidates.value = [];
        selectedId.value = null;
    } finally {
        loading.value = false;
    }
};

const setTab = (key: Status) => {
    if (tab.value === key) return;
    tab.value = key;
    actionError.value = '';
    void load();
};

const select = (c: Candidate) => {
    selectedId.value = c.user_id;
    actionError.value = '';
    docError.value = '';
};

// Documento de identificação: tipo pela extensão do ficheiro guardado (PDF, JPG, PNG)
const docError = ref('');
const openingDoc = ref(false);

const docType = (path: string) =>
    path.split('.').pop()?.toUpperCase() ?? 'Ficheiro';

// Abre o documento num separador novo. Vai buscar o ficheiro pela API (só o admin tem acesso)
// e mostra-o a partir do browser. O separador abre logo no clique para não ser bloqueado.
const openDocument = async (c: Candidate) => {
    docError.value = '';
    openingDoc.value = true;
    const tab = window.open('', '_blank');

    try {
        const res = await apiFetch(
            `/api/petsitters/${c.user_id}/identification-document`,
            {
                headers: { Accept: '*/*' },
            },
        );

        if (!res.ok) {
            tab?.close();
            docError.value =
                res.status === 404
                    ? 'O ficheiro do documento não foi encontrado.'
                    : 'Não foi possível abrir o documento.';
            return;
        }

        const url = URL.createObjectURL(await res.blob());
        if (tab) tab.location.href = url;
        else window.open(url, '_blank');
    } catch {
        tab?.close();
        docError.value = 'Não foi possível abrir o documento.';
    } finally {
        openingDoc.value = false;
    }
};

const act = async (action: 'approve' | 'reject') => {
    const current = selected.value;
    if (!current || busy.value) return;
    busy.value = true;
    actionError.value = '';

    try {
        const res = await apiFetch(
            `/api/petsitters/${current.user_id}/${action}`,
            { method: 'POST' },
        );
        if (res.ok) {
            candidates.value = candidates.value.filter(
                (c) => c.user_id !== current.user_id,
            );
            selectedId.value = candidates.value[0]?.user_id ?? null;
            if (tab.value === 'pending')
                pendingCount.value = candidates.value.length;
            showReject.value = false;
        } else {
            const body = await res.json().catch(() => null);
            actionError.value =
                body?.message ?? 'Não foi possível concluir a ação.';
        }
    } catch {
        actionError.value = 'Não foi possível concluir a ação.';
    } finally {
        busy.value = false;
    }
};

const openReject = () => {
    actionError.value = '';
    showReject.value = true;
};

// Motivos e mensagem ainda não são enviados: a API de recusa não os guarda.
const confirmReject = () => act('reject');

const logout = () => {
    router.post('/logout');
};

onMounted(() => {
    void load();
});
</script>

<template>
    <Head title="Verificações" />

    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div>
                <div class="brand">
                    <img
                        :src="logoPatudo"
                        alt="Patudo"
                        width="34"
                        height="34"
                    />
                    <span>Patudo</span>
                </div>
                <p class="menu-label">ADMINISTRAÇÃO</p>
                <nav class="menu">
                    <div
                        v-for="item in menu"
                        :key="item.label"
                        class="menu-item"
                        :class="{ active: item.active }"
                    >
                        <span>{{ item.label }}</span>
                        <span v-if="item.active" class="menu-badge">{{
                            pendingCount
                        }}</span>
                        <span v-else-if="item.badge" class="menu-badge">{{
                            item.badge
                        }}</span>
                    </div>
                </nav>
            </div>
            <button type="button" class="logout" @click="logout">
                Terminar sessão
            </button>
        </aside>

        <main class="admin-main">
            <header class="main-header">
                <div>
                    <h1>Verificações</h1>
                    <p class="subtitle">{{ subtitle }}</p>
                </div>
                <div class="admin-user">
                    <span>Administrador</span>
                    <span class="admin-avatar"></span>
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
                        {{ t.label
                        }}<template v-if="t.key === 'pending'">
                            ({{ pendingCount }})</template
                        >
                    </button>
                </div>
                <label class="search">
                    <Search :size="20" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Pesquisar cuidador"
                    />
                </label>
            </div>

            <div class="content">
                <div class="list-col">
                    <p v-if="loading" class="state">A carregar...</p>
                    <div v-else-if="loadError" class="state state-error">
                        {{ loadError }}
                        <button type="button" class="retry" @click="load">
                            Tentar novamente
                        </button>
                    </div>
                    <p v-else-if="filtered.length === 0" class="state">
                        Não existem cuidadores nesta lista.
                    </p>
                    <template v-else>
                        <button
                            v-for="c in filtered"
                            :key="c.user_id"
                            type="button"
                            class="list-item"
                            :class="{ selected: c.user_id === selectedId }"
                            @click="select(c)"
                        >
                            <img
                                v-if="c.avatar_url"
                                :src="c.avatar_url"
                                alt=""
                                class="person-avatar"
                            />
                            <span v-else class="person-avatar"></span>
                            <div class="list-text">
                                <strong>{{
                                    c.user?.name ?? 'Cuidador'
                                }}</strong>
                                <small
                                    >{{ daysAgo(c.created_at) }} ·
                                    Cuidador</small
                                >
                            </div>
                            <ChevronRight :size="22" />
                        </button>
                    </template>

                    <div class="deadline">
                        <strong>Prazo de resposta</strong>
                        <p>
                            Os pedidos devem ser respondidos em até 3 dias
                            úteis.
                        </p>
                    </div>
                </div>

                <section class="detail">
                    <template v-if="selected">
                        <div class="detail-head">
                            <img
                                v-if="selected.avatar_url"
                                :src="selected.avatar_url"
                                alt=""
                                class="person-avatar-lg"
                            />
                            <span v-else class="person-avatar-lg"></span>
                            <div class="detail-title">
                                <h2>{{ selected.user?.name ?? 'Cuidador' }}</h2>
                                <small>
                                    {{
                                        selected.city ??
                                        selected.service_location ??
                                        'Localização'
                                    }}
                                    · Registo
                                    {{ formatDate(selected.created_at) }}
                                </small>
                            </div>
                            <span class="status-pill">
                                <component
                                    :is="
                                        statusIcons[
                                            selected.approval_status ??
                                                'pending'
                                        ]
                                    "
                                    :size="16"
                                    :stroke-width="3"
                                />
                                <strong>{{
                                    statusLabels[
                                        selected.approval_status ?? 'pending'
                                    ]
                                }}</strong>
                            </span>
                        </div>

                        <h3>Dados do perfil</h3>
                        <div class="data-grid">
                            <div>
                                <small>Email</small>
                                <strong>{{
                                    selected.user?.email ?? '—'
                                }}</strong>
                            </div>
                            <div>
                                <small>Telemóvel</small>
                                <strong>{{
                                    selected.user?.phone ?? '—'
                                }}</strong>
                            </div>
                            <div>
                                <small>Serviços</small>
                                <strong>{{
                                    selected.services
                                        ?.map((s) => s.type)
                                        .join(', ') || '—'
                                }}</strong>
                            </div>
                            <div>
                                <small>Experiência</small>
                                <strong>{{
                                    selected.experience ?? '—'
                                }}</strong>
                            </div>
                        </div>

                        <h3>Documentos enviados</h3>
                        <div class="docs">
                            <div
                                v-if="selected.identification_documents"
                                class="doc"
                            >
                                <FileText :size="34" stroke-width="1.5" />
                                <div class="doc-text">
                                    <strong>Identificação</strong>
                                    <small>{{
                                        docType(
                                            selected.identification_documents,
                                        )
                                    }}</small>
                                </div>
                                <button
                                    type="button"
                                    class="open-btn"
                                    :disabled="openingDoc"
                                    @click="openDocument(selected)"
                                >
                                    {{ openingDoc ? 'A abrir...' : 'Abrir' }}
                                </button>
                            </div>
                            <p v-else class="state">
                                Este cuidador não enviou documento de
                                identificação.
                            </p>
                        </div>
                        <p v-if="docError" class="action-error">
                            {{ docError }}
                        </p>

                        <h3>Lista de verificação</h3>
                        <label
                            v-for="item in checklist"
                            :key="item.label"
                            class="check"
                        >
                            <input v-model="item.checked" type="checkbox" />
                            <span>{{ item.label }}</span>
                        </label>

                        <p v-if="actionError" class="action-error">
                            {{ actionError }}
                        </p>

                        <div
                            v-if="
                                (selected.approval_status ?? 'pending') ===
                                'pending'
                            "
                            class="actions"
                        >
                            <button
                                type="button"
                                class="btn-reject"
                                :disabled="busy"
                                @click="openReject"
                            >
                                Recusar
                            </button>
                            <button
                                type="button"
                                class="btn-info"
                                :disabled="busy"
                            >
                                Pedir mais informação
                            </button>
                            <button
                                type="button"
                                class="btn-approve"
                                :disabled="busy"
                                @click="act('approve')"
                            >
                                Aprovar cuidador
                            </button>
                        </div>
                    </template>
                    <p v-else class="state">
                        Seleciona um cuidador para ver os detalhes.
                    </p>
                </section>
            </div>
        </main>

        <ModalRejectVerification
            :show="showReject"
            :name="selected?.user?.name"
            :requested-label="daysAgo(selected?.created_at)"
            :avatar-url="selected?.avatar_url"
            :processing="busy"
            :error="actionError"
            @close="showReject = false"
            @confirm="confirmReject"
        />
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
    width: 160px;
    flex-shrink: 0;
    background-color: #fff;
    border-right: 1px solid #f3c9a8;
    padding: 24px 12px 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.brand {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 1.25rem;
    font-weight: 600;
    padding: 0 6px;
}

.menu-label {
    font-size: 0.6rem;
    margin: 28px 6px 10px;
    letter-spacing: 0.02em;
}

.menu-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    font-size: 0.8rem;
    font-weight: 600;
    border-radius: 999px;
    margin-bottom: 4px;
}

.menu-item.active {
    background-color: #2e1d14;
    color: #fff;
}

.menu-badge {
    background-color: #c2410c;
    color: #fff;
    font-size: 0.65rem;
    font-weight: 700;
    border-radius: 999px;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.logout {
    background: none;
    border: 0;
    border-top: 1px solid #f3c9a8;
    padding: 18px 14px 0;
    font-size: 0.8rem;
    font-weight: 600;
    text-align: left;
    color: inherit;
}

.admin-main {
    flex: 1;
    min-width: 0;
    padding: 26px 22px;
}

.main-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

h1 {
    font-size: 1.9rem;
    font-weight: 700;
    margin: 0;
}

.subtitle {
    font-size: 0.8rem;
    color: #7a6657;
    margin: 2px 0 0;
}

.admin-user {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.8rem;
    font-weight: 700;
}

.admin-avatar,
.person-avatar {
    border-radius: 50%;
    background-color: var(--patudo-bege);
    border: 1px solid var(--patudo-terracota);
    flex-shrink: 0;
}

.admin-avatar {
    width: 38px;
    height: 38px;
}

.person-avatar {
    width: 38px;
    height: 38px;
    object-fit: cover;
}

.person-avatar-lg {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--patudo-terracota);
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--patudo-terracota);
    background-color: var(--patudo-bege);
}

.toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 20px 0 16px;
}

.tabs {
    display: flex;
    gap: 10px;
}

.tab {
    border: 1.5px solid #c2410c;
    background-color: #fff;
    color: #c2410c;
    border-radius: 999px;
    padding: 6px 18px;
    font-size: 0.75rem;
    font-weight: 700;
}

.tab.active {
    background-color: #c2410c;
    color: #fff;
}

.search {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1.5px solid #2e1d14;
    background-color: #fff;
    border-radius: 999px;
    padding: 6px 16px;
    width: 206px;
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
    gap: 24px;
    align-items: flex-start;
}

.list-col {
    width: 255px;
    flex-shrink: 0;
}

.list-item {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    background-color: #fff;
    border: 1.5px solid #f3b78a;
    border-radius: 12px;
    padding: 10px 12px;
    margin-bottom: 8px;
    text-align: left;
    color: inherit;
}

.list-item.selected {
    border-color: #2e1d14;
}

.list-text {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.list-text strong {
    font-size: 0.8rem;
}

.list-text small {
    font-size: 0.65rem;
    color: #7a6657;
}

.deadline {
    border-radius: 12px;
    padding: 14px;
    margin-top: 16px;
    font-size: 0.7rem;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='100%25' height='100%25'%3e%3crect width='100%25' height='100%25' fill='none' rx='12' ry='12' stroke='%23c2410c' stroke-width='3' stroke-dasharray='8%2c 10' stroke-linecap='butt'/%3e%3c/svg%3e");
}

.deadline p {
    margin: 4px 0 0;
    font-size: 0.65rem;
    color: #7a6657;
}

.state {
    font-size: 0.8rem;
    color: #7a6657;
    text-align: center;
    padding: 16px 0;
    margin: 0;
}

.state-error {
    color: #dc3545;
    padding: 16px 0;
    font-size: 0.8rem;
    text-align: center;
}

.retry {
    display: block;
    margin: 8px auto 0;
    border: 1px solid #dc3545;
    background: #fff;
    color: #dc3545;
    border-radius: 999px;
    padding: 3px 12px;
    font-size: 0.7rem;
}

.detail {
    flex: 1;
    min-width: 0;
    background-color: #fff;
    border: 1.5px solid #c2410c;
    border-radius: 16px;
    padding: 18px 20px;
}

.detail-head {
    display: flex;
    align-items: center;
    gap: 14px;
    padding-bottom: 12px;
    border-bottom: 1.5px solid #f3b78a;
}

.detail-title {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.detail-title h2 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
}

.detail-title small {
    font-size: 0.7rem;
    color: #7a6657;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1.5px solid #c2410c;
    color: #000;
    border-radius: 999px;
    padding: 4px 14px;
    font-size: 0.75rem;
    font-weight: 800;
}

h3 {
    font-size: 0.95rem;
    font-weight: 700;
    margin: 18px 0 8px;
}

.data-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px 20px;
    font-size: 0.8rem;
}

.data-grid small {
    display: block;
    font-size: 0.65rem;
    color: #7a6657;
}

.docs {
    display: flex;
    gap: 12px;
}

.doc {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1.5px solid #f3b78a;
    background-color: #fff5ea;
    border-radius: 10px;
    padding: 8px 10px;
    color: #c2410c;
}

.doc-text {
    flex: 1;
    display: flex;
    flex-direction: column;
    color: #2e1d14;
}

.doc-text strong {
    font-size: 0.7rem;
}

.doc-text small {
    font-size: 0.6rem;
    color: #7a6657;
}

.open-btn {
    border: 1.5px solid #2e1d14;
    background-color: #fff;
    border-radius: 999px;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 2px 12px;
}

.check {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.75rem;
    margin-bottom: 8px;
}

.check input {
    width: 16px;
    height: 16px;
    accent-color: #c2410c;
}

.action-error {
    color: #dc3545;
    font-size: 0.75rem;
    margin: 6px 0 0;
}

.actions {
    display: flex;
    gap: 10px;
    margin-top: 16px;
}

.actions button {
    border-radius: 999px;
    padding: 9px 22px;
    font-size: 0.8rem;
    font-weight: 700;
}

.actions button:disabled {
    opacity: 0.6;
}

.btn-reject {
    background-color: #ff5252;
    border: 1.5px solid #ff5252;
    color: #fff;
}

.btn-info {
    background-color: #fff;
    border: 1.5px solid #c2410c;
    color: #c2410c;
}

.btn-approve {
    background-color: #c2410c;
    border: 1.5px solid #c2410c;
    color: #fff;
    margin-left: auto;
}
</style>
