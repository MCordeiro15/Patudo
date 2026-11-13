<!-- Componente UI select do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- select UI component from shadcn/ui. Part of the project's base component library. -->
<script setup lang="ts">
import type { SelectLabelProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { SelectLabel } from "reka-ui"
import { cn } from "@/lib/utils"

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<SelectLabelProps & { class?: HTMLAttributes["class"] }>()
</script>

<template>
  <SelectLabel
    data-slot="select-label"
    :class="cn('text-muted-foreground px-2 py-1.5 text-xs', props.class)"
  >
    <slot />
  </SelectLabel>
</template>
