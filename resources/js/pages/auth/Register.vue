<script setup lang="ts">
import { ref } from 'vue';

const name = ref('');
const email = ref('');
const phone = ref('');
const password = ref('');
const password_confirmation = ref('');
const msg = ref('');

const register = async () => {
    msg.value = 'A registar...';
    const res = await fetch('/api/register', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
        body: JSON.stringify({
            name: name.value,
            email: email.value,
            phone: phone.value,
            password: password.value,
            password_confirmation: password_confirmation.value,
        }),
    });
    const data = await res.json();
    msg.value = res.ok
        ? 'Conta criada!'
        : 'Erro: ' + (data.message || JSON.stringify(data.errors));
};
</script>

<template>
    <div style="padding: 20px; max-width: 320px; margin: auto">
        <h2>Registar</h2>
        <form @submit.prevent="register">
            <input
                v-model="name"
                type="text"
                placeholder="Nome"
                required
                style="width: 100%; margin-bottom: 10px"
            />
            <input
                v-model="email"
                type="email"
                placeholder="Email"
                required
                style="width: 100%; margin-bottom: 10px"
            />
            <input
                v-model="phone"
                type="text"
                placeholder="Telemóvel (opcional)"
                maxlength="9"
                style="width: 100%; margin-bottom: 10px"
            />
            <input
                v-model="password"
                type="password"
                placeholder="Password (mín 8)"
                required
                minlength="8"
                style="width: 100%; margin-bottom: 10px"
            />
            <input
                v-model="password_confirmation"
                type="password"
                placeholder="Confirmar Password"
                required
                minlength="8"
                style="width: 100%; margin-bottom: 10px"
            />
            <button type="submit" style="width: 100%">Criar Conta</button>
        </form>
        <p v-if="msg">{{ msg }}</p>
    </div>
</template>
