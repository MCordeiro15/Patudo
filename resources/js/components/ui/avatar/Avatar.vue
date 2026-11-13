<!-- Componente UI avatar do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- avatar UI component from shadcn/ui. Part of the project's base component library. -->
<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { AvatarRoot } from "reka-ui"
import { cn } from "@/lib/utils"

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <AvatarRoot
    data-slot="avatar"
    :class="cn('relative flex size-8 shrink-0 overflow-hidden rounded-full', props.class)"
  >
    <slot />
  </AvatarRoot>
</template>
