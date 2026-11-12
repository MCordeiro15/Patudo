<!-- Componente UI input-otp do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- input-otp UI component from shadcn/ui. Part of the project's base component library. -->
<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { MinusIcon } from "@lucide/vue"
import { useForwardProps } from "reka-ui"

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<{ class?: HTMLAttributes["class"] }>()

const forwarded = useForwardProps(props)
</script>

<template>
  <div
    data-slot="input-otp-separator"
    role="separator"
    v-bind="forwarded"
  >
    <slot>
      <MinusIcon />
    </slot>
  </div>
</template>
