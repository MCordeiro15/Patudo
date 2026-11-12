<!-- Componente UI skeleton do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- skeleton UI component from shadcn/ui. Part of the project's base component library. -->
<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

interface SkeletonProps {
  class?: HTMLAttributes["class"]
}

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<SkeletonProps>()
</script>

<template>
  <div
    data-slot="skeleton"
    :class="cn('animate-pulse rounded-md bg-primary/10', props.class)"
  />
</template>
