<script setup lang="ts">
import {Link} from '@inertiajs/vue3';
import {ref} from 'vue';
import {login as authLogin} from '@/composables/useAuth';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import imgCao from '@/../../assets/caoLoginRegister.jpg';
import '../../../css/login.css';

const email = ref('');
const password = ref('');
const msg = ref('');
const isError = ref(false);
const isLoading = ref(false);

const login = async () => {
    isLoading.value = true;
    msg.value = 'A validar...';
    isError.value = false;

    const result = await authLogin(email.value, password.value);

    if (result.success) {
        isError.value = false;
        msg.value = 'Login com sucesso!';
        // Navigation is handled by the auth composable
    } else {
        isError.value = true;
        const errors = result.errors as Record<string, string | string[]>;
        const firstError = Object.values(errors)[0];
        msg.value = Array.isArray(firstError)
            ? firstError.join(', ')
            : (firstError ?? 'Credenciais inválidas.');
    }

    isLoading.value = false;
};
</script>

<template>
    <div class="login-wrapper min-vh-100 d-flex align-items-center justify-content-center p-3">
        <div class="auth-card d-flex flex-column flex-md-row">
            <div class="brand-panel d-flex flex-column justify-content-between">
                <img :src="imgCao" alt="Cão Patudo" class="brand-bg-cover"/>
                <div class="brand-header d-flex align-items-center gap-2">
                    <img :src="logoPatudo" alt="Logótipo Patudo" class="brand-icon"/>
                    <span class="brand-title">Patudo</span>
                </div>
            </div>

            <div class="form-panel d-flex flex-column justify-content-center align-items-center">
                <div class="form-container w-100">
                    <h1 class="auth-title text-center">Login</h1>
                    <p class="auth-subtitle text-center mb-4">
                        Bem-vindo de volta!
                    </p>

                    <form @submit.prevent="login">
                        <div class="mb-3">
                            <input v-model="email" type="email" class="form-control auth-input" placeholder="Email"
                                   required/>
                        </div>

                        <div class="mb-4">
                            <input v-model="password" type="password" class="form-control auth-input"
                                   placeholder="Palavra-passe" required/>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3" :disabled="isLoading">
                            {{ isLoading ? 'A entrar...' : 'Entrar' }}
                        </button>
                    </form>

                    <div v-if="msg" :class="['alert py-2 small mb-3 text-center',isError ? 'auth-error-alert' : 'auth-valid-alert']">
                        {{ msg }}
                    </div>

                    <p class="text-center small mb-0 footer-text">
                        Não tens conta?
                        <Link href="/register" class="auth-link">
                            Regista-te aqui
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
