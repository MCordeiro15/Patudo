<!-- Componente UI breadcrumb do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- breadcrumb UI component from shadcn/ui. Part of the project's base component library. -->
<script lang="ts" setup>
import type { HTMLAttributes } from "vue"

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <!-- Barra de navegação com links principais. -->
  <!-- Navigation bar with main links. -->
  <nav
    aria-label="breadcrumb"
    data-slot="breadcrumb"
    :class="props.class"
  >
    <slot />
  </nav>
</template>
