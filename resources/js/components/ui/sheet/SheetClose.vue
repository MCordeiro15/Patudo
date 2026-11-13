<!-- Componente UI sheet do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- sheet UI component from shadcn/ui. Part of the project's base component library. -->
<script setup lang="ts">
import type { DialogCloseProps } from "reka-ui"
import { DialogClose } from "reka-ui"

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<DialogCloseProps>()
</script>

<template>
  <DialogClose
    data-slot="sheet-close"
    v-bind="props"
  >
    <slot />
  </DialogClose>
</template>
