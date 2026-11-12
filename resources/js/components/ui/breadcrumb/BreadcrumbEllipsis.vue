<!-- Componente UI breadcrumb do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- breadcrumb UI component from shadcn/ui. Part of the project's base component library. -->
<script lang="ts" setup>
import type { HTMLAttributes } from "vue"
import { MoreHorizontal } from "@lucide/vue"
import { cn } from "@/lib/utils"

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <span
    data-slot="breadcrumb-ellipsis"
    role="presentation"
    aria-hidden="true"
    :class="cn('flex size-9 items-center justify-center', props.class)"
  >
    <slot>
      <MoreHorizontal class="size-4" />
    </slot>
    <span class="sr-only">More</span>
  </span>
</template>
