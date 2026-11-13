<!-- Componente UI collapsible do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- collapsible UI component from shadcn/ui. Part of the project's base component library. -->
<script setup lang="ts">
import type { CollapsibleContentProps } from "reka-ui"
import { CollapsibleContent } from "reka-ui"

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<CollapsibleContentProps>()
</script>

<template>
  <CollapsibleContent
    data-slot="collapsible-content"
    v-bind="props"
  >
    <slot />
  </CollapsibleContent>
</template>
