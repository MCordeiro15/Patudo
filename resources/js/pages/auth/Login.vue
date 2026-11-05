<script setup lang="ts">
import { ref } from 'vue';

const email = ref('');
const password = ref('');
const msg = ref('');

const login = async () => {
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
        msg.value = res.ok
            ? 'Login com sucesso!'
            : 'Erro: ' + (data.message || JSON.stringify(data.errors));
    } catch (e: any) {
        msg.value = 'Erro: ' + e.message;
    }
};
</script>

<template>
    <div
        class="min-vh-100 d-flex align-items-center justify-content-center bg-white"
    >
        <div class="card p-4 shadow-sm" style="max-width: 320px; width: 100%">
            <h2 class="h4 mb-3 text-center">Login Patudo</h2>

            <form @submit.prevent="login">
                <div class="mb-3">
                    <label class="form-label">Email:</label>
                    <input
                        v-model="email"
                        type="email"
                        class="form-control"
                        required
                    />
                </div>

                <div class="mb-3">
                    <label class="form-label">Password:</label>
                    <input
                        v-model="password"
                        type="password"
                        class="form-control"
                        required
                    />
                </div>

                <button
                    type="submit"
                    class="btn text-white w-100"
                    style="background-color: #2c1810"
                >
                    Entrar
                </button>
            </form>

            <div
                v-if="msg"
                class="alert alert-info mt-3 py-2 small mb-0 text-center"
            >
                {{ msg }}
            </div>
        </div>
    </div>
</template>
