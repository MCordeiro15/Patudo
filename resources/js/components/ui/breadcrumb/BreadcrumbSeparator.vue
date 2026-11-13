<!-- Componente UI breadcrumb do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- breadcrumb UI component from shadcn/ui. Part of the project's base component library. -->
<script lang="ts" setup>
import type { HTMLAttributes } from "vue"
import { ChevronRight } from "@lucide/vue"
import { cn } from "@/lib/utils"

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <li
    data-slot="breadcrumb-separator"
    role="presentation"
    aria-hidden="true"
    :class="cn('[&>svg]:size-3.5', props.class)"
  >
    <slot>
      <ChevronRight />
    </slot>
  </li>
</template>
