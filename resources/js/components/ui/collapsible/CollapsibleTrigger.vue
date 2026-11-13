<!-- Componente UI collapsible do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- collapsible UI component from shadcn/ui. Part of the project's base component library. -->
<script setup lang="ts">
import type { CollapsibleTriggerProps } from "reka-ui"
import { CollapsibleTrigger } from "reka-ui"

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<CollapsibleTriggerProps>()
</script>

<template>
  <CollapsibleTrigger
    data-slot="collapsible-trigger"
    v-bind="props"
  >
    <slot />
  </CollapsibleTrigger>
</template>
