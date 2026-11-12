<!-- Componente UI dialog do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- dialog UI component from shadcn/ui. Part of the project's base component library. -->
<script setup lang="ts">
import type { DialogTriggerProps } from "reka-ui"
import { DialogTrigger } from "reka-ui"

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<DialogTriggerProps>()
</script>

<template>
  <DialogTrigger
    data-slot="dialog-trigger"
    v-bind="props"
  >
    <slot />
  </DialogTrigger>
</template>
