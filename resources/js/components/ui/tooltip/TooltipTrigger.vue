<!-- Componente UI tooltip do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- tooltip UI component from shadcn/ui. Part of the project's base component library. -->
<script setup lang="ts">
import type { TooltipTriggerProps } from "reka-ui"
import { TooltipTrigger } from "reka-ui"

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<TooltipTriggerProps>()
</script>

<template>
  <TooltipTrigger
    data-slot="tooltip-trigger"
    v-bind="props"
  >
    <slot />
  </TooltipTrigger>
</template>
