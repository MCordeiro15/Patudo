<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AdminNavigationMenu from '@/components/AdminNavigationMenu.vue';

const props = defineProps<{
    settings: {
        weeklySummaryEnabled: boolean;
    };
}>();

const weeklySummaryEnabled = ref(props.settings.weeklySummaryEnabled);
const savedWeeklySummaryEnabled = ref(props.settings.weeklySummaryEnabled);
const isSaving = ref(false);
const statusMessage = ref('');
const hasUnsavedChanges = computed(
    () => weeklySummaryEnabled.value !== savedWeeklySummaryEnabled.value,
);

watch(
    () => props.settings.weeklySummaryEnabled,
    (value) => {
        weeklySummaryEnabled.value = value;
        savedWeeklySummaryEnabled.value = value;
    },
);

const saveSettings = () => {
    statusMessage.value = '';
    isSaving.value = true;

    router.patch(
        '/admin/settings',
        { weekly_summary_enabled: weeklySummaryEnabled.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                savedWeeklySummaryEnabled.value =
                    props.settings.weeklySummaryEnabled;
                statusMessage.value = 'Preferências guardadas.';
            },
            onFinish: () => {
                isSaving.value = false;
            },
        },
    );
};

const cancelChanges = () => {
    weeklySummaryEnabled.value = savedWeeklySummaryEnabled.value;
    statusMessage.value = '';
};

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <Head title="Definições do administrador" />

    <main class="patudo-page-shell">
        <div class="patudo-content container py-4">
            <header
                class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4"
            >
                <div>
                    <h1 class="h3 fw-bold mb-1">Definições do administrador</h1>
                    <p class="text-muted mb-0">
                        Gere as tuas preferências e a sessão atual.
                    </p>
                </div>
                <AdminNavigationMenu />
            </header>

            <section
                class="card border-0 shadow-sm mb-4"
                aria-labelledby="notification-settings-title"
            >
                <div class="card-body p-4">
                    <h2 id="notification-settings-title" class="h5 fw-bold">
                        Notificações
                    </h2>
                    <div class="alert alert-info mb-4" role="status">
                        As notificações importantes da conta são obrigatórias e
                        não podem ser desativadas. Também poderão ser enviadas
                        por email.
                    </div>

                    <h3 class="h6 fw-semibold">Resumo semanal</h3>
                    <p class="text-muted small">
                        Escolhe se queres receber o resumo semanal. Esta
                        preferência aplica-se apenas à tua conta.
                    </p>
                    <div class="form-check form-switch">
                        <input
                            id="weekly-summary-enabled"
                            v-model="weeklySummaryEnabled"
                            class="form-check-input"
                            type="checkbox"
                            role="switch"
                            :disabled="isSaving"
                        />
                        <label
                            class="form-check-label"
                            for="weekly-summary-enabled"
                        >
                            Receber resumo semanal
                        </label>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2 mt-4">
                        <button
                            type="button"
                            class="btn btn-primary"
                            :disabled="!hasUnsavedChanges || isSaving"
                            @click="saveSettings"
                        >
                            {{ isSaving ? 'A guardar…' : 'Guardar alterações' }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            :disabled="!hasUnsavedChanges || isSaving"
                            @click="cancelChanges"
                        >
                            Cancelar
                        </button>
                        <span
                            v-if="statusMessage"
                            class="small text-success"
                            role="status"
                        >
                            {{ statusMessage }}
                        </span>
                    </div>
                </div>
            </section>

            <section
                class="card border-0 shadow-sm"
                aria-labelledby="session-settings-title"
            >
                <div
                    class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between gap-3"
                >
                    <div>
                        <h2 id="session-settings-title" class="h5 fw-bold">
                            Sessão atual
                        </h2>
                        <p class="text-muted mb-0">
                            Termina a sessão apenas neste dispositivo ou
                            browser. As restantes sessões mantêm-se ativas.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="btn btn-outline-danger"
                        @click="logout"
                    >
                        Terminar sessão
                    </button>
                </div>
            </section>
        </div>
    </main>
</template>
