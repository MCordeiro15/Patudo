<!-- Componente que exibe mensagens de erro de validação abaixo dos campos de entrada. -->
<!-- Component that displays validation error messages below input fields. -->
<script setup lang="ts">
defineProps<{
    message?: string;
}>();
</script>

<template>
    <div v-show="message">
        <p class="text-sm text-red-600 dark:text-red-500">
            {{ message }}
        </p>
    </div>
</template>
