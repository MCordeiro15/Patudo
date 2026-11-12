<!-- Componente UI dropdown-menu do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- dropdown-menu UI component from shadcn/ui. Part of the project's base component library. -->
<script setup lang="ts">
import type { DropdownMenuTriggerProps } from "reka-ui"
import { DropdownMenuTrigger, useForwardProps } from "reka-ui"

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<DropdownMenuTriggerProps>()

const forwardedProps = useForwardProps(props)
</script>

<template>
  <DropdownMenuTrigger
    data-slot="dropdown-menu-trigger"
    v-bind="forwardedProps"
  >
    <slot />
  </DropdownMenuTrigger>
</template>
