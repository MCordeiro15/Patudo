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
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ email: email.value, password: password.value })
        });
        const data = await res.json();
        msg.value = res.ok ? 'Login com sucesso!' : 'Erro: ' + (data.message || JSON.stringify(data.errors));
    } catch (e: any) {
        msg.value = 'Erro: ' + e.message;
    }
};
</script>

<template>
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #fff;">
        <div style="padding: 24px; border: 1px solid #ddd; border-radius: 8px; width: 320px;">
            <h2 style="margin-bottom: 16px;">Login Patudo</h2>
            <form @submit.prevent="login">
                <div style="margin-bottom: 12px;">
                    <label>Email:</label>
                    <input v-model="email" type="email" required style="width: 100%; padding: 8px; margin-top: 4px;" />
                </div>
                <div style="margin-bottom: 16px;">
                    <label>Password:</label>
                    <input v-model="password" type="password" required style="width: 100%; padding: 8px; margin-top: 4px;" />
                </div>
                <button type="submit" style="width: 100%; padding: 10px; background: #2C1810; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Entrar
                </button>
            </form>
            <p v-if="msg" style="margin-top: 12px; font-weight: bold;">{{ msg }}</p>
        </div>
    </div>
</template>
