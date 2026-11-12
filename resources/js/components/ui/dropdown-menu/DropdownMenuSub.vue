<!-- Componente UI dropdown-menu do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- dropdown-menu UI component from shadcn/ui. Part of the project's base component library. -->
<script setup lang="ts">
import type { DropdownMenuSubEmits, DropdownMenuSubProps } from "reka-ui"
import {
  DropdownMenuSub,
  useForwardPropsEmits,
} from "reka-ui"

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<DropdownMenuSubProps>()
const emits = defineEmits<DropdownMenuSubEmits>()

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <DropdownMenuSub v-slot="slotProps" data-slot="dropdown-menu-sub" v-bind="forwarded">
    <slot v-bind="slotProps" />
  </DropdownMenuSub>
</template>
