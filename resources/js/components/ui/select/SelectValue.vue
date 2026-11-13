<!-- Componente UI select do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- select UI component from shadcn/ui. Part of the project's base component library. -->
<script setup lang="ts">
import type { SelectValueProps } from "reka-ui"
import { SelectValue } from "reka-ui"

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<SelectValueProps>()
</script>

<template>
  <SelectValue
    data-slot="select-value"
    v-bind="props"
  >
    <slot />
  </SelectValue>
</template>
