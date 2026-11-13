<!-- Página de registo. Formulário para criar nova conta de utilizador. -->
<!-- Registration page. Form to create a new user account. -->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { register as authRegister } from '@/composables/useAuth';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import imgCao from '@/../../assets/caoLoginRegister.jpg';
import '../../../css/register.css';

const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const msg = ref('');
const isError = ref(false);
const isLoading = ref(false);

const register = async () => {
    if (password.value !== passwordConfirmation.value) {
        isError.value = true;
        msg.value = 'As palavras-passe não coincidem.';
        return;
    }

    isLoading.value = true;
    isError.value = false;
    msg.value = 'A registar...';

    const result = await authRegister(
        name.value,
        email.value,
        password.value,
        passwordConfirmation.value,
    );

    if (result.success) {
        isError.value = false;
        msg.value = 'Registo efetuado com sucesso!';
        // Navigation is handled by the auth composable
    } else {
        isError.value = true;
        const errors = result.errors as Record<string, string | string[]>;
        const firstError = Object.values(errors)[0];
        msg.value = Array.isArray(firstError)
            ? firstError.join(', ')
            : (firstError ?? 'Erro ao registar.');
    }

    isLoading.value = false;
};
</script>

<template>
    <div
        class="register-wrapper min-vh-100 d-flex align-items-center justify-content-center p-3"
    >
        <div class="auth-card d-flex flex-column-reverse flex-md-row">
            <div
                class="form-panel d-flex flex-column justify-content-center align-items-center"
            >
                <div class="form-container w-100">
                    <h1 class="auth-title text-center">Registar</h1>
                    <p class="auth-subtitle text-center mb-4">Sê bem-vindo!</p>

                    <!-- Formulário para entrada de dados do utilizador. -->
                    <!-- Form for user data input. -->
                    <form @submit.prevent="register">
                        <div class="mb-3">
                            <input
                                v-model="name"
                                type="text"
                                class="form-control auth-input"
                                placeholder="Nome de utilizador"
                                required
                            />
                        </div>

                        <div class="mb-3">
                            <input
                                v-model="email"
                                type="email"
                                class="form-control auth-input"
                                placeholder="Email"
                                required
                            />
                        </div>

                        <div class="mb-3">
                            <input
                                v-model="password"
                                type="password"
                                class="form-control auth-input"
                                placeholder="Palavra-passe"
                                required
                            />
                        </div>

                        <div class="mb-4">
                            <input
                                v-model="passwordConfirmation"
                                type="password"
                                class="form-control auth-input"
                                placeholder="Confirmar palavra-passe"
                                required
                            />
                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary w-100 mb-3"
                            :disabled="isLoading"
                        >
                            {{ isLoading ? 'A registar...' : 'Registar' }}
                        </button>
                    </form>

                    <div
                        v-if="msg"
                        :class="[
                            'alert py-2 small mb-3 text-center',
                            isError ? 'auth-error-alert' : 'auth-valid-alert',
                        ]"
                    >
                        {{ msg }}
                    </div>

                    <p class="text-center small mb-0 footer-text">
                        Já tens conta?
                        <Link href="/login" class="auth-link"
                            >Faz login aqui</Link
                        >
                    </p>
                </div>
            </div>

            <div class="brand-panel d-flex flex-column justify-content-between">
                <img :src="imgCao" alt="Cão Patudo" class="brand-bg-cover" />
                <div class="brand-header d-flex align-items-center gap-2">
                    <img
                        :src="logoPatudo"
                        alt="Logótipo Patudo"
                        class="brand-icon"
                    />
                    <span class="brand-title">Patudo</span>
                </div>
            </div>
        </div>
    </div>
</template>
