<script setup lang="ts">
import {Link} from '@inertiajs/vue3';
import {ref} from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import imgCao from '@/../../assets/caoLoginRegister.jpg';
import '../../../css/login.css';

const email = ref('');
const password = ref('');
const msg = ref('');
const isLoading = ref(false);

const login = async () => {
    isLoading.value = true;
    msg.value = 'A validar...';
    try {
        const res = await fetch('/api/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                email: email.value,
                password: password.value,
            }),
        });
        const data = await res.json();
        if (res.ok) {
            msg.value = 'Login com sucesso!';
            window.location.href = '/';
        } else {
            msg.value =
                'Erro: ' + (data.message || JSON.stringify(data.errors));
        }
    } catch (e: any) {
        msg.value = 'Erro: ' + e.message;
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="login-wrapper min-vh-100 d-flex align-items-center justify-content-center p-3">
        <div class="auth-card d-flex flex-column flex-md-row">
            <div class="brand-panel d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <img :src="logoPatudo" alt="Logótipo Patudo" class="brand-icon"/>
                        <span class="brand-title">Patudo</span>
                    </div>

                    <div class="image-placeholder text-center text-muted">
                        <img :src="imgCao" alt="Imagem Cão" class="brand-dog-img">
                    </div>
                </div>

                <p class="brand-slogan mb-0">
                    Encontra o<br/>
                    cuidador ideal<br/>
                    para o teu animal,<br/>
                    perto de ti.
                </p>
            </div>

            <div class="form-panel d-flex flex-column justify-content-center align-items-center">
                <div class="form-container w-100">
                    <h1 class="auth-title text-center">Login</h1>
                    <p class="auth-subtitle text-center mb-4">
                        Bem-vindo de volta!
                    </p>

                    <form @submit.prevent="login">
                        <div class="mb-3">
                            <input v-model="email" type="email" class="form-control auth-input" placeholder="Email" required/>
                        </div>

                        <div class="mb-4">
                            <input v-model="password" type="password" class="form-control auth-input" placeholder="Palavra-passe" required/>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3" :disabled="isLoading">
                            {{ isLoading ? 'A entrar...' : 'Entrar' }}
                        </button>
                    </form>

                    <div v-if="msg" class="alert auth-valid-alert py-2 small mb-3 text-center">
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
