<!-- Componente UI avatar do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- avatar UI component from shadcn/ui. Part of the project's base component library. -->
<script setup lang="ts">
import type { AvatarImageProps } from "reka-ui"
import { AvatarImage } from "reka-ui"

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<AvatarImageProps>()
</script>

<template>
  <AvatarImage
    data-slot="avatar-image"
    v-bind="props"
    class="aspect-square size-full"
  >
    <slot />
  </AvatarImage>
</template>
