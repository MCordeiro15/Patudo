<!-- Componente UI navigation-menu do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- navigation-menu UI component from shadcn/ui. Part of the project's base component library. -->
<script setup lang="ts">
import type { NavigationMenuTriggerProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { ChevronDown } from "@lucide/vue"
import { reactiveOmit } from "@vueuse/core"
import {
  NavigationMenuTrigger,
  useForwardProps,
} from "reka-ui"
import { cn } from "@/lib/utils"
import { navigationMenuTriggerStyle } from "."

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<NavigationMenuTriggerProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <NavigationMenuTrigger
    data-slot="navigation-menu-trigger"
    v-bind="forwardedProps"
    :class="cn(navigationMenuTriggerStyle(), 'group', props.class)"
  >
    <slot />
    <ChevronDown
      class="relative top-[1px] ml-1 size-3 transition duration-300 group-data-[state=open]:rotate-180"
      aria-hidden="true"
    />
  </NavigationMenuTrigger>
</template>
