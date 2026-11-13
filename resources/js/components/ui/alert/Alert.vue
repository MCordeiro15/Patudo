<!-- Componente UI alert do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- alert UI component from shadcn/ui. Part of the project's base component library. -->
<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import type { AlertVariants } from "."
import { cn } from "@/lib/utils"
import { alertVariants } from "."

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<{
  class?: HTMLAttributes["class"]
  variant?: AlertVariants["variant"]
}>()
</script>

<template>
  <div
    data-slot="alert"
    :class="cn(alertVariants({ variant }), props.class)"
    role="alert"
  >
    <slot />
  </div>
</template>
