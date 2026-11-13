<!-- Componente UI collapsible do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- collapsible UI component from shadcn/ui. Part of the project's base component library. -->
<script setup lang="ts">
import type { CollapsibleRootEmits, CollapsibleRootProps } from "reka-ui"
import { CollapsibleRoot, useForwardPropsEmits } from "reka-ui"

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<CollapsibleRootProps>()
const emits = defineEmits<CollapsibleRootEmits>()

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <CollapsibleRoot
    v-slot="slotProps"
    data-slot="collapsible"
    v-bind="forwarded"
  >
    <slot v-bind="slotProps" />
  </CollapsibleRoot>
</template>
