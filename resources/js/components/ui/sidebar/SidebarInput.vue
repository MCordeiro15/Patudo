<!-- Componente UI sidebar do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- sidebar UI component from shadcn/ui. Part of the project's base component library. -->
<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { Input } from '@/components/ui/input'

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <Input
    data-slot="sidebar-input"
    data-sidebar="input"
    :class="cn(
      'bg-background h-8 w-full shadow-none',
      props.class,
    )"
  >
    <slot />
  </Input>
</template>
