<!-- Componente de título de secção. Exibe cabeçalhos com estilo consistente. -->
<!-- Section heading component. Displays headers with consistent styling. -->
<script setup lang="ts">
// Tipo TypeScript para Props.
// TypeScript type for Props.
type Props = {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
};

withDefaults(defineProps<Props>(), {
    variant: 'default',
});
</script>

<template>
    <!-- Cabeçalho da página com navegação principal e logotipo. -->
    <!-- Page header with main navigation and logo. -->
    <header :class="variant === 'small' ? '' : 'mb-8 space-y-0.5'">
        <h2
            :class="
                variant === 'small'
                    ? 'mb-0.5 text-base font-medium'
                    : 'text-xl font-semibold tracking-tight'
            "
        >
            {{ title }}
        </h2>
        <p v-if="description" class="text-sm text-muted-foreground">
            {{ description }}
        </p>
    </header>
</template>
