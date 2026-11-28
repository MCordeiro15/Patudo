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
import logoPatudo from '@/../../assets/logoPatudo.svg';
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

const navItems = computed(() => [
    { label: 'Utilizadores', href: '/admin/users' },
    {
        label: 'Verificações',
        href: '/admin/petsitters/pending',
        active: true,
        badge: pendingCount.value,
    },
    { label: 'Reservas', href: '/admin/bookings' },
    { label: 'Avaliações', href: '/admin/reviews' },
    { label: 'Denúncias', href: '/admin/reports', badge: 3 },
    { label: 'Suporte', href: '/admin/support', badge: 2 },
    { label: 'Definições', href: '/admin/settings' },
]);

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

function statusPillClass(status: Status) {
    switch (status) {
        case 'approved':
            return 'status-pill--approved';
        case 'rejected':
            return 'status-pill--rejected';
        default:
            return 'status-pill--pending';
    }
}

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

const docError = ref('');
const openingDoc = ref(false);

const docType = (path: string) =>
    path.split('.').pop()?.toUpperCase() ?? 'Ficheiro';

const openDocument = async (c: Candidate) => {
    docError.value = '';
    openingDoc.value = true;
    const newTab = window.open('', '_blank');

    try {
        const res = await apiFetch(
            `/api/petsitters/${c.user_id}/identification-document`,
            {
                headers: { Accept: '*/*' },
            },
        );

        if (!res.ok) {
            newTab?.close();
            docError.value =
                res.status === 404
                    ? 'O ficheiro do documento não foi encontrado.'
                    : 'Não foi possível abrir o documento.';
            return;
        }

        const url = URL.createObjectURL(await res.blob());
        if (newTab) newTab.location.href = url;
        else window.open(url, '_blank');
    } catch {
        newTab?.close();
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

const confirmReject = () => act('reject');

const logout = () => {
    router.post('/logout');
};

onMounted(() => {
    void load();
});
</script>

<template>
    <Head title="Verificações — Administração" />

    <div class="patudo-page-shell patudo-admin-shell">
        <aside class="patudo-navbar" aria-label="Menu de administração">
            <a
                class="navbar-brand fw-bold fs-3 d-flex align-items-center gap-2"
                href="/"
            >
                <img :src="logoPatudo" alt="Patudo" width="70" height="70" />
                <span>Patudo</span>
            </a>

            <div class="navbar-section-title">Administração</div>

            <nav class="navbar-nav">
                <a
                    v-for="item in navItems"
                    :key="item.label"
                    :href="item.href"
                    class="navbar-link"
                    :class="{ active: item.active }"
                >
                    <span>{{ item.label }}</span>
                    <span v-if="item.badge" class="navbar-badge">{{
                        item.badge
                    }}</span>
                </a>
            </nav>

            <div class="navbar-footer">
                <button
                    type="button"
                    class="navbar-link logout w-100 border-0 bg-transparent text-start"
                    @click="logout"
                >
                    Terminar sessão
                </button>
            </div>
        </aside>

        <main
            class="patudo-content verifications-container flex-grow-1 py-4 px-4 px-lg-5"
        >
            <header
                class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4"
            >
                <div>
                    <h1 class="fw-bold mb-1">Verificações</h1>
                    <p class="text-secondary mb-0">{{ subtitle }}</p>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="fw-semibold small">Administrador</span>
                    <span class="admin-avatar"></span>
                </div>
            </header>

            <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
                <div class="d-flex flex-wrap gap-2">
                    <button
                        v-for="t in tabs"
                        :key="t.key"
                        type="button"
                        class="btn"
                        :class="
                            tab === t.key
                                ? 'btn-primary'
                                : 'btn-outline-primary'
                        "
                        @click="setTab(t.key)"
                    >
                        {{ t.label
                        }}<template v-if="t.key === 'pending'">
                            ({{ pendingCount }})</template
                        >
                    </button>
                </div>

                <div class="search-wrapper ms-auto">
                    <span class="search-icon" aria-hidden="true">
                        <Search :size="16" />
                    </span>
                    <input
                        v-model="search"
                        type="search"
                        class="form-control bookings-search"
                        placeholder="Pesquisar cuidador"
                    />
                </div>
            </div>

            <div class="card verifications-shell p-3 p-md-4">
                <div class="content d-flex gap-4">
                    <div class="list-col" style="width: 280px; flex-shrink: 0">
                        <p
                            v-if="loading"
                            class="state text-center py-4 text-secondary"
                        >
                            A carregar...
                        </p>
                        <div
                            v-else-if="loadError"
                            class="state state-error text-center py-4 text-danger"
                        >
                            {{ loadError }}
                            <button
                                type="button"
                                class="btn btn-outline-danger btn-sm mt-2"
                                @click="load"
                            >
                                Tentar novamente
                            </button>
                        </div>
                        <p
                            v-else-if="filtered.length === 0"
                            class="state text-center py-4 text-secondary"
                        >
                            Não existem cuidadores nesta lista.
                        </p>
                        <template v-else>
                            <button
                                v-for="c in filtered"
                                :key="c.user_id"
                                type="button"
                                class="list-item w-100 text-start"
                                :class="{
                                    'list-item-selected':
                                        c.user_id === selectedId,
                                }"
                                @click="select(c)"
                            >
                                <div class="d-flex align-items-center gap-3">
                                    <img
                                        v-if="c.avatar_url"
                                        :src="c.avatar_url"
                                        alt=""
                                        class="person-avatar"
                                    />
                                    <span v-else class="person-avatar"></span>
                                    <div class="list-text flex-grow-1 min-w-0">
                                        <strong class="d-block text-truncate">{{
                                            c.user?.name ?? 'Cuidador'
                                        }}</strong>
                                        <small class="text-secondary"
                                            >Cuidador</small
                                        >
                                    </div>
                                </div>
                            </button>
                        </template>

                        <div
                            class="deadline mt-4 p-3 rounded-3 border"
                            style="
                                border-color: var(--patudo-terracota);
                                background-image: url('data:image/svg+xml,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27100%25%27 height=%27100%25%27%3e%3crect width=%27100%25%27 height=%27100%25%27 fill=%27none%27 rx=%2712%27 ry=%2712%27 stroke=%27%23c2410c%27 stroke-width=%273%27 stroke-dasharray=%278%2c 10%27 stroke-linecap=%27butt%27/%3e%3c/svg%3e');
                            "
                        >
                            <strong class="d-block">Prazo de resposta</strong>
                            <p class="mb-0 small text-secondary">
                                Os pedidos devem ser respondidos em até 3 dias
                                úteis.
                            </p>
                        </div>
                    </div>

                    <section class="detail flex-grow-1 min-w-0">
                        <template v-if="selected">
                            <div
                                class="detail-head d-flex align-items-center gap-3 pb-3 mb-3"
                                style="
                                    border-bottom: 1px solid
                                        var(--patudo-terracota);
                                "
                            >
                                <img
                                    v-if="selected.avatar_url"
                                    :src="selected.avatar_url"
                                    alt=""
                                    class="person-avatar-lg"
                                />
                                <span v-else class="person-avatar-lg"></span>
                                <div class="detail-title flex-grow-1 min-w-0">
                                    <h2 class="fw-bold mb-1">
                                        {{ selected.user?.name ?? 'Cuidador' }}
                                    </h2>
                                    <small class="text-secondary">
                                        {{
                                            selected.city ??
                                            selected.service_location ??
                                            'Localização'
                                        }}
                                        · Registo
                                        {{ formatDate(selected.created_at) }}
                                    </small>
                                </div>
                                <span
                                    class="status-pill"
                                    :class="
                                        statusPillClass(
                                            selected.approval_status ??
                                                'pending',
                                        )
                                    "
                                >
                                    <component
                                        :is="
                                            statusIcons[
                                                selected.approval_status ??
                                                    'pending'
                                            ]
                                        "
                                        :size="14"
                                        :stroke-width="2.5"
                                        class="me-1"
                                    />
                                    <strong>{{
                                        statusLabels[
                                            selected.approval_status ??
                                                'pending'
                                        ]
                                    }}</strong>
                                </span>
                            </div>

                            <h3 class="fw-bold mb-3">Dados do perfil</h3>
                            <div class="data-grid row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <small class="text-secondary d-block"
                                        >Email</small
                                    >
                                    <strong>{{
                                        selected.user?.email ?? '—'
                                    }}</strong>
                                </div>
                                <div class="col-12 col-md-6">
                                    <small class="text-secondary d-block"
                                        >Telemóvel</small
                                    >
                                    <strong>{{
                                        selected.user?.phone ?? '—'
                                    }}</strong>
                                </div>
                                <div class="col-12 col-md-6">
                                    <small class="text-secondary d-block"
                                        >Serviços</small
                                    >
                                    <strong>{{
                                        selected.services
                                            ?.map((s) => s.type)
                                            .join(', ') || '—'
                                    }}</strong>
                                </div>
                                <div class="col-12 col-md-6">
                                    <small class="text-secondary d-block"
                                        >Experiência</small
                                    >
                                    <strong>{{
                                        selected.experience ?? '—'
                                    }}</strong>
                                </div>
                            </div>

                            <h3 class="fw-bold mb-3">Documentos enviados</h3>
                            <div class="docs d-flex gap-3 mb-3">
                                <div
                                    v-if="selected.identification_documents"
                                    class="doc flex-grow-1 d-flex align-items-center gap-2 p-3 rounded-3"
                                    style="
                                        border: 1px solid
                                            var(--patudo-terracota);
                                        background: #fff5ea;
                                        color: var(--patudo-terracota);
                                    "
                                >
                                    <FileText :size="28" stroke-width="1.5" />
                                    <div class="doc-text flex-grow-1">
                                        <strong class="d-block small"
                                            >Identificação</strong
                                        >
                                        <small class="text-secondary">{{
                                            docType(
                                                selected.identification_documents,
                                            )
                                        }}</small>
                                    </div>
                                    <button
                                        type="button"
                                        class="btn btn-outline-primary btn-sm"
                                        :disabled="openingDoc"
                                        @click="openDocument(selected)"
                                    >
                                        {{
                                            openingDoc ? 'A abrir...' : 'Abrir'
                                        }}
                                    </button>
                                </div>
                                <p
                                    v-else
                                    class="state text-secondary text-center py-4 w-100"
                                >
                                    Este cuidador não enviou documento de
                                    identificação.
                                </p>
                            </div>
                            <p
                                v-if="docError"
                                class="action-error text-danger small mb-3"
                            >
                                {{ docError }}
                            </p>

                            <h3 class="fw-bold mb-3">Lista de verificação</h3>
                            <label
                                v-for="item in checklist"
                                :key="item.label"
                                class="check d-flex align-items-center gap-2 mb-2 small"
                            >
                                <input
                                    v-model="item.checked"
                                    type="checkbox"
                                    class="form-check-input"
                                    style="
                                        accent-color: var(--patudo-terracota);
                                        width: 16px;
                                        height: 16px;
                                    "
                                />
                                <span>{{ item.label }}</span>
                            </label>

                            <p
                                v-if="actionError"
                                class="action-error text-danger small mb-3"
                            >
                                {{ actionError }}
                            </p>

                            <div
                                v-if="
                                    (selected.approval_status ?? 'pending') ===
                                    'pending'
                                "
                                class="actions d-flex gap-2 mt-3"
                            >
                                <button
                                    type="button"
                                    class="btn btn-outline-danger"
                                    :disabled="busy"
                                    @click="openReject"
                                >
                                    Recusar
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-outline-primary"
                                    :disabled="busy"
                                >
                                    Pedir mais informação
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-primary ms-auto"
                                    :disabled="busy"
                                    @click="act('approve')"
                                >
                                    Aprovar cuidador
                                </button>
                            </div>
                        </template>
                        <p v-else class="state text-center py-5 text-secondary">
                            Seleciona um cuidador para ver os detalhes.
                        </p>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <ModalRejectVerification
        :show="showReject"
        :name="selected?.user?.name"
        :avatar-url="selected?.avatar_url"
        :processing="busy"
        :error="actionError"
        @close="showReject = false"
        @confirm="confirmReject"
    />
</template>

<style scoped>
.verifications-container {
    max-width: 1400px;
}

.verifications-shell {
    background-color: rgba(255, 255, 255, 0.78);
    border-color: var(--patudo-terracota);
}

.list-item {
    padding: 0.75rem 1rem;
    border-radius: 12px;
    background-color: #ffffff;
    border: 1.5px solid rgba(194, 65, 12, 0.25);
    transition:
        border-color 0.2s ease,
        background-color 0.2s ease;
}

.list-item:hover {
    background-color: rgba(194, 65, 12, 0.04);
}

.list-item-selected {
    border-color: var(--patudo-castanhoDark);
    background-color: rgba(46, 29, 20, 0.03);
}

.list-text strong {
    font-size: 0.85rem;
}

.list-text small {
    font-size: 0.75rem;
}

.admin-avatar,
.person-avatar {
    border-radius: 50%;
    background-color: var(--patudo-bege);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.55);
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

.status-pill {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 25px;
    font-size: 0.75rem;
    font-weight: 600;
    border: 1.5px solid var(--patudo-terracota);
}

.data-grid small {
    font-size: 0.7rem;
}

.data-grid strong {
    font-size: 0.85rem;
}

.doc-text strong {
    font-size: 0.75rem;
}

.doc-text small {
    font-size: 0.65rem;
}

.check input {
    width: 16px;
    height: 16px;
    accent-color: var(--patudo-terracota);
}

.action-error {
    font-size: 0.75rem;
}

.search-wrapper {
    position: relative;
    width: 100%;
    max-width: 290px;
}

.search-icon {
    position: absolute;
    left: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--patudo-castanhoMid);
    display: flex;
    pointer-events: none;
}

.bookings-search {
    padding-left: 2.35rem;
    border-radius: 25px;
    border-color: var(--patudo-terracota);
    background-color: #ffffff;
    font-size: 0.85rem;
}

.bookings-search:focus {
    border-color: var(--patudo-laranjaVivo);
    box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.15);
}

@media (max-width: 991.98px) {
    .verifications-container {
        padding: 1rem !important;
    }

    .content {
        flex-direction: column;
    }

    .list-col {
        width: 100% !important;
    }

    .deadline {
        display: none;
    }
}
</style>
