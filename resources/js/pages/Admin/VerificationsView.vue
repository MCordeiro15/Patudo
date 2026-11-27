<!--
    PLACEHOLDER PARA TESTES: este front-end é só para testar no browser o backend das verificações
    (candidaturas, motivos de recusa e avisos). Segue o mockup por alto, sem o layout de administração
    (barra lateral). A página a sério fica para o ticket de front-end.
    PLACEHOLDER FOR TESTING: this front-end only exists to test the verifications backend in the browser.
-->
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ChevronRight, FileText, Search } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import ModalRejectVerification from '@/pages/Modals/ModalRejectVerification.vue';

type Status = 'pending' | 'approved' | 'rejected';

// Candidatura (tabela petsitter_applications), como vem do PetsitterApplicationResource
type ApplicationDocument = { index: number; name: string; type: string; url: string };

type Application = {
    id: number;
    status: Status;
    documents: ApplicationDocument[];
    reasons: string[];
    reason_labels: string[];
    message: string | null;
    decided_by?: { id: number; name: string } | null;
    decided_at: string | null;
    created_at: string;
};

type Candidate = {
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
    application?: Application | null;
    applications?: Application[];
    applications_remaining?: number;
};

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

const tabs: { key: Status; label: string }[] = [
    { key: 'pending', label: 'Pendentes' },
    { key: 'approved', label: 'Aprovadas' },
    { key: 'rejected', label: 'Recusadas' },
];

const statusLabels: Record<Status, string> = { pending: 'Pendente', approved: 'Aprovada', rejected: 'Recusada' };

// O modal de recusa devolve os textos; a API recebe estes códigos
const reasonCodes: Record<string, string> = {
    'Documento ilegível': 'illegible_document',
    'A identidade não confere': 'identity_mismatch',
    'Fotografias inadequadas': 'inappropriate_photos',
    'Contactos externos na descrição': 'external_contacts',
    'Outro motivo': 'other',
};

const tab = ref<Status>('pending');
const candidates = ref<Candidate[]>([]);
const pendingCount = ref(0);
const selectedId = ref<number | null>(null);
const search = ref('');
const loading = ref(false);
const loadError = ref('');
const busy = ref(false);
const feedback = ref('');
const actionError = ref('');
const docError = ref('');
const openingDoc = ref<string | null>(null);
const showReject = ref(false);
const showInfo = ref(false);
const infoMessage = ref('');
const infoSubmitted = ref(false);

// Só no browser: o ticket não pede para guardar a lista de verificação
const checklist = ref([
    { label: 'A identidade confere com o nome do perfil', checked: false },
    { label: 'O documento é legível e está válido', checked: false },
    { label: 'As fotografias do perfil são adequadas', checked: false },
    { label: 'A descrição não tem contactos externos', checked: false },
]);

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();
    return term
        ? candidates.value.filter((c) => (c.user?.name ?? '').toLowerCase().includes(term))
        : candidates.value;
});

const selected = computed(() => candidates.value.find((c) => c.user_id === selectedId.value) ?? null);

// Candidaturas anteriores (as que não são a atual)
const history = computed(() =>
    (selected.value?.applications ?? []).filter((a) => a.id !== selected.value?.application?.id),
);

const daysAgo = (iso?: string) => {
    if (!iso) return '';
    const days = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 86400000));
    return days === 0 ? 'Pedido hoje' : `Pedido há ${days} ${days === 1 ? 'dia' : 'dias'}`;
};

const formatDate = (iso?: string | null) => (iso ? new Date(iso).toLocaleDateString('pt-PT') : '—');

const load = async () => {
    loading.value = true;
    loadError.value = '';
    try {
        const res = await apiFetch(`/api/petsitters?approval_status=${tab.value}`);
        if (!res.ok) throw new Error(`erro ${res.status}`);
        const data = await res.json();
        candidates.value = data.data ?? data;
        if (tab.value === 'pending') pendingCount.value = candidates.value.length;
        if (!candidates.value.some((c) => c.user_id === selectedId.value)) {
            selectedId.value = candidates.value[0]?.user_id ?? null;
        }
    } catch (e) {
        loadError.value = `Não foi possível carregar as candidaturas (${(e as Error).message}).`;
        candidates.value = [];
        selectedId.value = null;
    } finally {
        loading.value = false;
    }
};

const setTab = (key: Status) => {
    if (tab.value === key) return;
    tab.value = key;
    selectedId.value = null;
    feedback.value = '';
    actionError.value = '';
    void load();
};

const select = (c: Candidate) => {
    selectedId.value = c.user_id;
    feedback.value = '';
    actionError.value = '';
    docError.value = '';
    checklist.value.forEach((item) => (item.checked = false));
};

// Documentos da candidatura atual; sem candidatura, usa o documento antigo do perfil
const documents = computed<ApplicationDocument[]>(() => {
    const current = selected.value;
    if (!current) return [];
    if (current.application) return current.application.documents;
    return current.identification_documents
        ? [{ index: 0, name: 'Documento', type: 'Ficheiro', url: `/api/petsitters/${current.user_id}/identification-document` }]
        : [];
});

// Abre o documento num separador novo (o ficheiro vem pela API, só o admin tem acesso)
const openDocument = async (doc: ApplicationDocument) => {
    if (!selected.value) return;
    docError.value = '';
    openingDoc.value = doc.url;
    const win = window.open('', '_blank');

    try {
        const res = await apiFetch(doc.url, { headers: { Accept: '*/*' } });
        if (!res.ok) {
            win?.close();
            docError.value = res.status === 404 ? 'O documento não foi encontrado.' : `Não foi possível abrir o documento (erro ${res.status}).`;
            return;
        }
        const url = URL.createObjectURL(await res.blob());
        if (win) win.location.href = url;
        else window.open(url, '_blank');
    } catch {
        win?.close();
        docError.value = 'Não foi possível abrir o documento.';
    } finally {
        openingDoc.value = null;
    }
};

// Aprovar, recusar ou pedir informação; mostra o erro da API (ex.: 422) se falhar
const act = async (action: 'approve' | 'reject' | 'request-info', body?: object) => {
    const current = selected.value;
    if (!current || busy.value) return false;
    busy.value = true;
    actionError.value = '';
    feedback.value = '';

    try {
        await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });
        const res = await apiFetch(`/api/petsitters/${current.user_id}/${action}`, {
            method: 'POST',
            body: body ? JSON.stringify(body) : undefined,
        });
        const json = await res.json().catch(() => null);

        if (!res.ok) {
            const firstError = json?.errors ? Object.values(json.errors as Record<string, string[]>)[0]?.[0] : null;
            actionError.value = `Erro ${res.status}: ${firstError ?? json?.message ?? 'não foi possível concluir a ação.'}`;
            return false;
        }

        const name = current.user?.name ?? 'O cuidador';
        feedback.value = {
            approve: `${name} foi aprovado.`,
            reject: `${name} foi recusado.`,
            'request-info': `Pedido de informação enviado a ${name}. A candidatura continua pendente.`,
        }[action];
        await load();
        return true;
    } catch {
        actionError.value = 'Não foi possível concluir a ação.';
        return false;
    } finally {
        busy.value = false;
    }
};

const confirmReject = async (payload: { reasons: string[]; message: string }) => {
    const ok = await act('reject', {
        reasons: payload.reasons.map((r) => reasonCodes[r] ?? r),
        message: payload.message,
    });
    if (ok) showReject.value = false;
};

const openInfo = () => {
    infoMessage.value = '';
    infoSubmitted.value = false;
    actionError.value = '';
    showInfo.value = true;
};

const confirmInfo = async () => {
    infoSubmitted.value = true;
    if (!infoMessage.value.trim()) return;
    if (await act('request-info', { message: infoMessage.value.trim() })) showInfo.value = false;
};

onMounted(() => {
    void load();
});
</script>

<template>
    <Head title="Verificações" />

    <main class="patudo-page-shell">
        <div class="patudo-content container py-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                <h1 class="h3 fw-bold mb-0">Verificações</h1>
                <span class="badge text-bg-warning">Placeholder para testes</span>
            </div>
            <p class="text-muted small mb-4">
                {{ pendingCount }} {{ pendingCount === 1 ? 'cuidador' : 'cuidadores' }} à espera de aprovação
            </p>

            <!-- Separadores e pesquisa -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div class="d-flex flex-wrap gap-2" role="group" aria-label="Filtrar candidaturas">
                    <button
                        v-for="t in tabs"
                        :key="t.key"
                        type="button"
                        class="btn"
                        :class="tab === t.key ? 'btn-primary' : 'btn-outline-primary'"
                        :aria-pressed="tab === t.key"
                        @click="setTab(t.key)"
                    >
                        {{ t.label }}<template v-if="t.key === 'pending'"> ({{ pendingCount }})</template>
                    </button>
                </div>
                <label class="verif-search d-flex align-items-center gap-2 px-3">
                    <Search :size="18" />
                    <input
                        v-model="search"
                        type="search"
                        class="border-0 bg-transparent flex-grow-1"
                        placeholder="Pesquisar cuidador"
                        aria-label="Pesquisar cuidador"
                    />
                </label>
            </div>

            <div v-if="feedback" class="alert alert-success py-2 small">{{ feedback }}</div>
            <div v-if="loadError" class="alert alert-danger py-2 small">{{ loadError }}</div>

            <div class="row g-3">
                <!-- Lista de candidatos -->
                <div class="col-lg-4">
                    <p v-if="loading" class="text-muted small">A carregar...</p>
                    <p v-else-if="!filtered.length" class="text-muted small">Não existem cuidadores nesta lista.</p>

                    <button
                        v-for="c in loading ? [] : filtered"
                        :key="c.user_id"
                        type="button"
                        class="verif-item d-flex align-items-center gap-3 w-100 mb-2"
                        :class="{ selected: c.user_id === selectedId }"
                        @click="select(c)"
                    >
                        <img v-if="c.avatar_url" :src="c.avatar_url" alt="" class="verif-avatar sm" />
                        <span v-else class="verif-avatar sm"></span>
                        <span class="flex-grow-1 text-start min-w-0">
                            <strong class="d-block text-truncate">{{ c.user?.name ?? 'Cuidador' }}</strong>
                            <small class="text-muted">{{ daysAgo(c.application?.created_at ?? c.created_at) }} · Cuidador</small>
                        </span>
                        <ChevronRight :size="20" />
                    </button>

                    <div class="verif-deadline mt-3">
                        <strong>Prazo de resposta</strong>
                        <p class="mb-0">Os pedidos devem ser respondidos em até 3 dias úteis.</p>
                    </div>
                </div>

                <!-- Detalhe -->
                <div class="col-lg-8">
                    <section class="verif-detail">
                        <p v-if="!selected" class="text-muted mb-0">Seleciona um cuidador para ver os detalhes.</p>

                        <template v-else>
                            <div class="d-flex flex-wrap align-items-center gap-3 pb-3 mb-3 verif-divider">
                                <img v-if="selected.avatar_url" :src="selected.avatar_url" alt="" class="verif-avatar lg" />
                                <span v-else class="verif-avatar lg"></span>
                                <div class="flex-grow-1 min-w-0">
                                    <h2 class="h5 fw-bold mb-0">{{ selected.user?.name ?? 'Cuidador' }}</h2>
                                    <small class="text-muted">
                                        {{ selected.city ?? selected.service_location ?? 'Localização' }} · Registo
                                        {{ formatDate(selected.created_at) }}
                                    </small>
                                </div>
                                <span class="verif-status" :class="`st-${selected.application?.status ?? selected.approval_status ?? 'pending'}`">
                                    {{ statusLabels[selected.application?.status ?? selected.approval_status ?? 'pending'] }}
                                </span>
                            </div>

                            <h3 class="verif-h3">Dados do perfil</h3>
                            <div class="row g-2 mb-3 small">
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">Email</span>
                                    <strong>{{ selected.user?.email ?? '—' }}</strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">Telemóvel</span>
                                    <strong>{{ selected.user?.phone ?? '—' }}</strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">Serviços</span>
                                    <strong>{{ selected.services?.map((s) => s.type).join(', ') || '—' }}</strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">Experiência</span>
                                    <strong>{{ selected.experience ?? '—' }}</strong>
                                </div>
                            </div>

                            <h3 class="verif-h3">Documentos enviados ({{ documents.length }})</h3>
                            <p v-if="!documents.length" class="text-muted small">Este cuidador não enviou documentos.</p>
                            <div class="row g-2 mb-2">
                                <div v-for="doc in documents" :key="doc.url" class="col-sm-6">
                                    <div class="verif-doc d-flex align-items-center gap-2">
                                        <FileText :size="30" :stroke-width="1.5" />
                                        <span class="flex-grow-1 min-w-0">
                                            <strong class="d-block small">Identificação {{ documents.length > 1 ? doc.index + 1 : '' }}</strong>
                                            <small class="text-muted d-block text-truncate">{{ doc.type }} · {{ doc.name }}</small>
                                        </span>
                                        <button
                                            type="button"
                                            class="btn btn-outline-primary btn-sm"
                                            :disabled="openingDoc !== null"
                                            @click="openDocument(doc)"
                                        >
                                            {{ openingDoc === doc.url ? 'A abrir...' : 'Abrir' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <p v-if="docError" class="text-danger small">{{ docError }}</p>

                            <h3 class="verif-h3 mt-3">Lista de verificação</h3>
                            <div class="mb-3">
                                <label v-for="item in checklist" :key="item.label" class="d-flex align-items-center gap-2 small mb-1">
                                    <input v-model="item.checked" type="checkbox" class="form-check-input mt-0" />
                                    <span>{{ item.label }}</span>
                                </label>
                            </div>

                            <!-- Candidatura atual: motivos da recusa ou último pedido de informação -->
                            <div v-if="selected.application?.reason_labels.length" class="verif-box mb-3 small">
                                <strong class="d-block mb-1">Motivos da recusa</strong>
                                <ul class="mb-1">
                                    <li v-for="label in selected.application.reason_labels" :key="label">{{ label }}</li>
                                </ul>
                                <p v-if="selected.application.message" class="mb-1">"{{ selected.application.message }}"</p>
                                <span class="text-muted">
                                    {{ selected.application.decided_by?.name ?? 'Admin' }} · {{ formatDate(selected.application.decided_at) }}
                                </span>
                            </div>
                            <div
                                v-else-if="selected.application?.status === 'pending' && selected.application.message"
                                class="verif-box mb-3 small"
                            >
                                <strong class="d-block mb-1">Último pedido de informação</strong>
                                <p class="mb-0">"{{ selected.application.message }}"</p>
                            </div>

                            <!-- Candidaturas anteriores: ficam guardadas quando o candidato volta a candidatar-se -->
                            <div v-if="selected.applications" class="verif-box mb-3 small">
                                <strong class="d-block mb-1">
                                    Candidaturas: {{ selected.applications.length }} de 3
                                    <span class="text-muted fw-normal">· pode fazer mais {{ selected.applications_remaining ?? 0 }}</span>
                                </strong>
                                <div v-for="a in history" :key="a.id" class="mb-1">
                                    {{ formatDate(a.created_at) }} · {{ statusLabels[a.status] }}
                                    <template v-if="a.reason_labels.length"> — {{ a.reason_labels.join(', ') }}</template>
                                    <template v-if="a.message"> · "{{ a.message }}"</template>
                                </div>
                                <span v-if="!history.length" class="text-muted">Sem candidaturas anteriores.</span>
                            </div>

                            <p v-if="actionError && !showReject && !showInfo" class="text-danger small">{{ actionError }}</p>

                            <div
                                v-if="(selected.application?.status ?? selected.approval_status ?? 'pending') === 'pending'"
                                class="d-flex flex-wrap justify-content-end gap-2"
                            >
                                <button type="button" class="btn btn-outline-danger" :disabled="busy" @click="showReject = true">
                                    Recusar
                                </button>
                                <button type="button" class="btn btn-outline-primary" :disabled="busy" @click="openInfo">
                                    Pedir mais informação
                                </button>
                                <button type="button" class="btn btn-primary" :disabled="busy" @click="act('approve')">
                                    {{ busy ? 'A guardar...' : 'Aprovar cuidador' }}
                                </button>
                            </div>
                        </template>
                    </section>
                </div>
            </div>
        </div>

        <!-- Recusa: modal do colega, já com os motivos e a mensagem do mockup -->
        <ModalRejectVerification
            :show="showReject"
            :name="selected?.user?.name"
            :requested-label="daysAgo(selected?.application?.created_at ?? selected?.created_at)"
            :avatar-url="selected?.avatar_url"
            :processing="busy"
            :error="actionError"
            @close="showReject = false"
            @confirm="confirmReject"
        />

        <!-- Pedir mais informação: mensagem obrigatória -->
        <div v-if="showInfo" class="verif-backdrop" @click.self="showInfo = false">
            <div class="verif-modal" role="dialog" aria-modal="true" aria-labelledby="info-title">
                <h2 id="info-title" class="h5 fw-bold pb-2 mb-3 verif-divider">Pedir mais informação</h2>
                <label for="info-message" class="small fw-semibold mb-1">Mensagem para o cuidador (obrigatório)</label>
                <textarea
                    id="info-message"
                    v-model="infoMessage"
                    class="form-control"
                    rows="4"
                    placeholder="Indica o que falta ou o que deve corrigir..."
                ></textarea>
                <p v-if="infoSubmitted && !infoMessage.trim()" class="text-danger small mt-1 mb-0">Escreve uma mensagem.</p>
                <p class="text-muted small mt-2 mb-0">O cuidador recebe uma notificação e a candidatura continua pendente.</p>
                <p v-if="actionError" class="text-danger small mt-2 mb-0">{{ actionError }}</p>
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="button" class="btn btn-outline-primary" :disabled="busy" @click="showInfo = false">Cancelar</button>
                    <button type="button" class="btn btn-primary" :disabled="busy" @click="confirmInfo">
                        {{ busy ? 'A enviar...' : 'Enviar pedido' }}
                    </button>
                </div>
            </div>
        </div>
    </main>
</template>

<style scoped>
.min-w-0 {
    min-width: 0;
}

.verif-search {
    min-width: 16rem;
    height: 2.5rem;
    border: 1.5px solid #2e1d14;
    border-radius: 999px;
}

.verif-search input:focus {
    outline: none;
}

.verif-item {
    background-color: #fffaf3;
    border: 1.5px solid #f3b78a;
    border-radius: 0.75rem;
    padding: 0.6rem 0.9rem;
    color: #2e1d14;
}

.verif-item.selected {
    border-color: #2e1d14;
    border-width: 2px;
}

.verif-avatar {
    display: inline-block;
    flex-shrink: 0;
    border-radius: 50%;
    background-color: #fdd9a8;
    object-fit: cover;
}

.verif-avatar.sm {
    width: 2.5rem;
    height: 2.5rem;
}

.verif-avatar.lg {
    width: 3.5rem;
    height: 3.5rem;
}

.verif-deadline {
    border: 1.5px dashed #f3b78a;
    border-radius: 0.75rem;
    padding: 0.9rem;
    font-size: 0.85rem;
}

.verif-detail {
    background-color: #fffaf3;
    border: 1.5px solid #c2410c;
    border-radius: 1rem;
    padding: 1.25rem;
}

.verif-divider {
    border-bottom: 1.5px solid #f3b78a;
}

.verif-h3 {
    font-size: 0.95rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.verif-doc {
    border: 1.5px solid #f3b78a;
    border-radius: 0.75rem;
    padding: 0.5rem 0.75rem;
}

.verif-box {
    border: 1.5px solid #f3b78a;
    border-radius: 0.75rem;
    padding: 0.75rem;
}

.verif-status {
    border: 1.5px solid #c2410c;
    border-radius: 999px;
    padding: 0.2rem 0.75rem;
    font-size: 0.8rem;
    font-weight: 700;
}

.st-approved {
    border-color: #166534;
    color: #166534;
}

.st-rejected {
    border-color: #991b1b;
    color: #991b1b;
}

.verif-backdrop {
    position: fixed;
    inset: 0;
    z-index: 10000000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background-color: rgba(0, 0, 0, 0.45);
}

.verif-modal {
    width: 100%;
    max-width: 440px;
    padding: 1.25rem 1.5rem;
    background-color: #fffaf3;
    border: 1.5px solid #f3b78a;
    border-radius: 1rem;
    color: #2e1d14;
}
</style>
