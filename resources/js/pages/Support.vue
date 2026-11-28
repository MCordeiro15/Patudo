<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CalendarDays, LifeBuoy, LogOut, ShieldAlert } from '@lucide/vue';
import { computed, ref } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import { logout } from '@/composables/useAuth';

type Suspension = {
    status: 'suspended';
    until: string | null;
    reason: string | null;
    notice: string | null;
};

const props = defineProps<{
    suspension: Suspension | null;
}>();

const subject = ref('');
const description = ref('');
const feedback = ref('');
const feedbackKind = ref<'success' | 'error' | ''>('');
const isSubmitting = ref(false);

const isSuspended = computed(() => props.suspension?.status === 'suspended');
const formattedSuspensionEnd = computed(() => {
    if (!props.suspension?.until) {
        return null;
    }

    const date = new Date(props.suspension.until);

    if (Number.isNaN(date.getTime())) {
        return null;
    }

    return new Intl.DateTimeFormat('pt-PT', {
        dateStyle: 'long',
        timeStyle: 'short',
    }).format(date);
});

function getXsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function submitSupportRequest() {
    feedback.value = '';
    feedbackKind.value = '';
    isSubmitting.value = true;

    try {
        await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });
        const response = await fetch('/api/support-tickets', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getXsrfToken(),
            },
            body: JSON.stringify({
                subject: subject.value,
                description: description.value,
                type: 'help',
            }),
        });
        const result = await response.json().catch(() => ({}));

        if (!response.ok) {
            feedback.value = result.message ?? 'Não foi possível enviar o pedido.';
            feedbackKind.value = 'error';
            return;
        }

        subject.value = '';
        description.value = '';
        feedback.value = 'Pedido enviado. A equipa de suporte irá responder assim que possível.';
        feedbackKind.value = 'success';
    } catch {
        feedback.value = 'Não foi possível contactar o suporte. Tente novamente.';
        feedbackKind.value = 'error';
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <Head :title="isSuspended ? 'Conta suspensa' : 'Suporte geral'" />

    <main class="support-page patudo-page-shell">
        <div class="support-shell">
            <header class="support-header">
                <div class="support-brand">
                    <img :src="logoPatudo" alt="" width="42" height="42" />
                    <span>Patudo</span>
                </div>
                <div class="support-header-actions">
                    <span class="support-header-label">Ajuda e segurança</span>
                    <button v-if="isSuspended" class="btn btn-outline-danger d-inline-flex align-items-center gap-2" type="button" @click="logout">
                        <LogOut :size="16" aria-hidden="true" />
                        Terminar sessão
                    </button>
                </div>
            </header>

            <div class="support-grid">
                <section v-if="isSuspended" class="support-intro suspension-card" aria-labelledby="support-title">
                    <div class="support-icon suspension-icon" aria-hidden="true">
                        <ShieldAlert />
                    </div>
                    <p class="support-eyebrow">Acesso temporariamente limitado</p>
                    <h1 id="support-title">A sua conta está suspensa</h1>
                    <p class="support-description">
                        Durante este período, não poderá utilizar as restantes funcionalidades da plataforma.
                        Pode contactar a nossa equipa através deste formulário.
                    </p>

                    <dl v-if="formattedSuspensionEnd || props.suspension?.reason" class="suspension-details">
                        <div v-if="formattedSuspensionEnd" class="suspension-detail">
                            <dt><CalendarDays aria-hidden="true" /> Suspensão até</dt>
                            <dd>{{ formattedSuspensionEnd }}</dd>
                        </div>
                        <div v-if="props.suspension?.reason" class="suspension-detail">
                            <dt>Motivo</dt>
                            <dd>{{ props.suspension.reason }}</dd>
                        </div>
                    </dl>

                    <p v-if="props.suspension?.notice" class="suspension-notice">
                        {{ props.suspension.notice }}
                    </p>
                </section>

                <section v-else class="support-intro" aria-labelledby="support-title">
                    <div class="support-icon" aria-hidden="true">
                        <LifeBuoy />
                    </div>
                    <p class="support-eyebrow">Estamos aqui para ajudar</p>
                    <h1 id="support-title">Suporte geral</h1>
                    <p class="support-description">
                        Envie a sua questão à equipa Patudo. Vamos responder assim que possível.
                    </p>
                </section>

                <section class="support-form-card" aria-labelledby="form-title">
                    <div class="form-heading">
                        <p class="support-eyebrow">Equipa Patudo</p>
                        <h2 id="form-title">Como podemos ajudar?</h2>
                        <p>Descreva a situação e entraremos em contacto consigo.</p>
                    </div>

                    <form @submit.prevent="submitSupportRequest">
                        <div class="mb-3">
                            <label for="support-subject" class="form-label">Assunto</label>
                            <input
                                id="support-subject"
                                v-model="subject"
                                class="form-control"
                                type="text"
                                maxlength="255"
                                placeholder="Resuma o motivo do contacto"
                                required
                            />
                        </div>

                        <div class="mb-3">
                            <label for="support-description" class="form-label">Mensagem</label>
                            <textarea
                                id="support-description"
                                v-model="description"
                                class="form-control"
                                rows="5"
                                maxlength="5000"
                                placeholder="Conte-nos mais detalhes..."
                                required
                            />
                        </div>

                        <p
                            v-if="feedback"
                            class="support-feedback"
                            :class="`support-feedback--${feedbackKind}`"
                            role="status"
                            aria-live="polite"
                        >
                            {{ feedback }}
                        </p>

                        <button class="btn btn-primary support-submit" type="submit" :disabled="isSubmitting">
                            {{ isSubmitting ? 'A enviar…' : 'Enviar pedido' }}
                        </button>
                    </form>
                </section>
            </div>

            <p v-if="isSuspended" class="support-footnote">
                O formulário de suporte geral continua disponível durante a suspensão da conta.
            </p>
        </div>
    </main>
</template>

<style scoped>
.support-page {
    min-height: 100vh;
    padding: 24px clamp(18px, 5vw, 72px);
    color: #2e1d14;
}

.support-shell {
    width: min(100%, 1120px);
    margin: 0 auto;
}

.support-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(122, 102, 87, 0.2);
}

.support-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: -0.04em;
}

.support-brand img {
    display: block;
    object-fit: contain;
}

.support-header-label {
    color: #7a6657;
    font-size: 0.9rem;
    font-weight: 600;
}

.support-header-actions {
    display: flex;
    align-items: center;
    gap: 20px;
}

.support-grid {
    display: grid;
    grid-template-columns: minmax(0, 0.95fr) minmax(340px, 1.05fr);
    align-items: start;
    gap: clamp(28px, 6vw, 76px);
    padding: clamp(36px, 7vw, 82px) 0 24px;
}

.support-intro {
    padding: 20px 4px;
}

.suspension-card {
    padding: clamp(24px, 4vw, 38px);
    border: 1px solid rgba(194, 65, 12, 0.15);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.72);
    box-shadow: 0 18px 50px rgba(46, 29, 20, 0.06);
}

.support-icon {
    display: grid;
    width: 54px;
    height: 54px;
    margin-bottom: 26px;
    place-items: center;
    border-radius: 18px;
    background: #ffedd5;
    color: #c2410c;
}

.suspension-icon {
    background: #fff1e8;
    color: #a63c12;
}

.support-icon :deep(svg) {
    width: 27px;
    height: 27px;
    stroke-width: 1.8;
}

.support-eyebrow {
    margin: 0 0 10px;
    color: #a04a24;
    font-size: 0.76rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.support-intro h1 {
    max-width: 520px;
    margin: 0 0 16px;
    font-size: clamp(2rem, 4vw, 3.15rem);
    font-weight: 800;
    letter-spacing: -0.055em;
    line-height: 1.08;
}

.support-description {
    max-width: 510px;
    margin: 0;
    color: #705c4d;
    font-size: 1.03rem;
    line-height: 1.7;
}

.suspension-details {
    display: grid;
    gap: 14px;
    margin: 26px 0 0;
    padding-top: 20px;
    border-top: 1px solid rgba(122, 102, 87, 0.18);
}

.suspension-detail dt {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 4px;
    color: #7a6657;
    font-size: 0.8rem;
    font-weight: 700;
}

.suspension-detail dt :deep(svg) {
    width: 15px;
    height: 15px;
}

.suspension-detail dd {
    margin: 0;
    color: #2e1d14;
    font-weight: 600;
    line-height: 1.5;
}

.suspension-notice {
    margin: 20px 0 0;
    padding: 13px 15px;
    border-radius: 12px;
    background: #fff4e8;
    color: #75421f;
    font-size: 0.9rem;
    line-height: 1.55;
}

.support-form-card {
    padding: clamp(24px, 4vw, 38px);
    border: 1px solid rgba(122, 102, 87, 0.16);
    border-radius: 24px;
    background: #ffffff;
    box-shadow: 0 18px 50px rgba(46, 29, 20, 0.08);
}

.form-heading {
    margin-bottom: 26px;
}

.form-heading h2 {
    margin: 0 0 8px;
    color: #2e1d14;
    font-size: 1.55rem;
    font-weight: 800;
    letter-spacing: -0.04em;
}

.form-heading > p:last-child {
    margin: 0;
    color: #7a6657;
    line-height: 1.55;
}

.support-form-card .form-label {
    color: #44352d;
    font-size: 0.9rem;
    font-weight: 700;
}

.support-form-card .form-control {
    min-height: 48px;
    border-color: rgba(122, 102, 87, 0.25);
    border-radius: 12px;
    padding: 12px 14px;
    color: #2e1d14;
}

.support-form-card textarea.form-control {
    min-height: 132px;
    resize: vertical;
}

.support-form-card .form-control:focus {
    border-color: #c2410c;
    box-shadow: 0 0 0 0.2rem rgba(194, 65, 12, 0.14);
}

.support-submit {
    min-height: 48px;
    margin-top: 4px;
}

.support-submit:disabled {
    opacity: 0.7;
}

.support-feedback {
    margin: 0 0 16px;
    padding: 12px 14px;
    border-radius: 12px;
    font-size: 0.9rem;
    line-height: 1.5;
}

.support-feedback--success {
    background: #edf5ed;
    color: #315a35;
}

.support-feedback--error {
    background: #fff0ed;
    color: #8e3023;
}

.support-footnote {
    margin: 0;
    color: #7a6657;
    font-size: 0.88rem;
    text-align: center;
}

@media (max-width: 760px) {
    .support-page {
        padding: 18px;
    }

    .support-grid {
        grid-template-columns: minmax(0, 1fr);
        gap: 10px;
        padding-top: 28px;
    }

    .support-intro {
        padding: 8px 2px 20px;
    }

    .suspension-card {
        padding: 24px;
    }

    .support-form-card {
        padding: 24px;
    }
}

@media (max-width: 420px) {
    .support-header-label {
        font-size: 0.78rem;
    }

    .support-brand {
        font-size: 1.15rem;
    }

    .support-brand img {
        width: 36px;
        height: 36px;
    }
}
</style>