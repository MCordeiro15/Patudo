<!-- Componente UI select do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- select UI component from shadcn/ui. Part of the project's base component library. -->
<script setup lang="ts">
import type { SelectItemTextProps } from "reka-ui"
import { SelectItemText } from "reka-ui"

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<SelectItemTextProps>()
</script>

<template>
  <SelectItemText
    data-slot="select-item-text"
    v-bind="props"
  >
    <slot />
  </SelectItemText>
</template>
