<script setup lang="ts">
import {Link} from '@inertiajs/vue3';
import {ref} from 'vue';

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
                        <svg class="brand-icon" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 3L2 12h3v8h14v-8h3L12 3zm0 4.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5-1.5-.67-1.5-1.5.67-1.5 1.5-1.5zm-3.5 3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5-1.5-.67-1.5-1.5.67-1.5 1.5-1.5zm7 0c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5-1.5-.67-1.5-1.5.67-1.5 1.5-1.5zm-3.5 2c1.38 0 2.5 1.12 2.5 2.5s-1.12 2.5-2.5 2.5-2.5-1.12-2.5-2.5 1.12-2.5 2.5-2.5z"/>
                        </svg>
                        <span class="brand-title">Patudo</span>
                    </div>

                    <div class="image-placeholder text-center text-muted">
                        <em>imagem background</em>
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

                        <button type="submit" class="btn btn-auth w-100 mb-3" :disabled="isLoading">
                            {{ isLoading ? 'A entrar...' : 'Entrar' }}
                        </button>
                    </form>

                    <div v-if="msg" class="alert alert-info py-2 small mb-3 text-center">
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

<style scoped>
.login-wrapper {
    background-color: #faf7f2;
}

.auth-card {
    background-color: #f7d5b2;
    border-radius: 28px;
    padding: 24px;
    width: 100%;
    max-width: 880px;
    min-height: 520px;
    gap: 24px;
}

.brand-panel {
    background-color: #ffffff;
    border-radius: 22px;
    border: 1px solid #d9531e;
    padding: 32px 28px;
    flex: 1;
    min-height: 460px;
}

.brand-icon {
    width: 32px;
    height: 32px;
    color: #d9531e;
}

.brand-title {
    font-size: 1.6rem;
    font-weight: 700;
    color: #2c1810;
}

.image-placeholder {
    margin-top: 60px;
    font-size: 0.95rem;
}

.brand-slogan {
    font-size: 1.1rem;
    line-height: 1.4;
    color: #2c1810;
    font-weight: 600;
}

/* Painel da Direita */
.form-panel {
    flex: 1;
    padding: 20px 32px;
}

.form-container {
    max-width: 320px;
}

.auth-title {
    font-size: 2.2rem;
    font-weight: 700;
    color: #2c1810;
    margin-bottom: 4px;
}

.auth-subtitle {
    font-size: 0.95rem;
    color: #4a3830;
}

.auth-input {
    height: 48px;
    border-radius: 12px;
    border: 1.5px solid #2c1810;
    padding: 10px 16px;
    font-size: 0.95rem;
    background-color: #ffffff;
}

.auth-input:focus {
    border-color: #d9531e;
    box-shadow: 0 0 0 0.2rem rgba(217, 83, 30, 0.2);
}

.btn-auth {
    height: 48px;
    border-radius: 12px;
    background-color: #b73e10;
    border: none;
    color: #ffffff;
    font-weight: 600;
    font-size: 1.05rem;
    transition: background-color 0.2s ease;
}

.btn-auth:hover {
    background-color: #9c330c;
    color: #ffffff;
}

.footer-text {
    color: #2c1810;
}

.auth-link {
    color: #2c1810;
    text-decoration: underline;
    font-weight: 500;
}

.auth-link:hover {
    color: #d9531e;
}
</style>
