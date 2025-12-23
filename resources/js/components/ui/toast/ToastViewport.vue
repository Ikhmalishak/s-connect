<script setup>
import { reactiveOmit } from "@vueuse/core";
import { ToastViewport } from "reka-ui";
import { cn } from "@/lib/utils";

const props = defineProps({
    hotkey: { type: Array, required: false },
    label: { type: [String, Function], required: false },
    asChild: { type: Boolean, required: false },
    as: { type: null, required: false },
    class: { type: null, required: false },
});

const delegatedProps = reactiveOmit(props, "class");
</script>

<template>
    <ToastViewport
        v-bind="delegatedProps"
        :class="
            cn(
                'fixed top-5 right-5 z-[100] flex flex-col gap-2 w-80 p-4',
                props.class
            )
        "
    />
</template>
