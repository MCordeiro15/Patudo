<!-- Componente UI input-otp do shadcn/ui. Parte da biblioteca de componentes base do projeto. -->
<!-- input-otp UI component from shadcn/ui. Part of the project's base component library. -->
<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import type { OTPInputEmits, OTPInputProps } from "vue-input-otp"
import { reactiveOmit } from "@vueuse/core"
import { useForwardPropsEmits } from "reka-ui"
import { OTPInput } from "vue-input-otp"
import { cn } from "@/lib/utils"

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<OTPInputProps & { class?: HTMLAttributes["class"] }>()

const emits = defineEmits<OTPInputEmits>()

const delegatedProps = reactiveOmit(props, "class")

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <OTPInput
    v-slot="slotProps"
    v-bind="forwarded"
    :container-class="cn('flex items-center gap-2 has-disabled:opacity-50', props.class)"
    data-slot="input-otp"
    class="disabled:cursor-not-allowed"
  >
    <slot v-bind="slotProps" />
  </OTPInput>
</template>
