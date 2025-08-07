<template>
  <div class="relative inline-block" @mouseenter="show = true" @mouseleave="show = false">
    <!-- Tooltip Trigger -->
    <slot></slot>

    <!-- Tooltip Content -->
    <transition name="fade">
      <div
        v-if="show"
        class="absolute z-50 px-2 py-1 text-xs text-white bg-black rounded-md whitespace-nowrap"
        :class="positionClass"
      >
        {{ text }}
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  text: {
    type: String,
    required: true,
  },
  position: {
    type: String,
    default: "top", // top, bottom, left, right
  },
});

const show = ref(false);

const positionClass = computed(() => {
  switch (props.position) {
    case "bottom":
      return "top-full mt-1 left-1/2 -translate-x-1/2";
    case "left":
      return "right-full mr-1 top-1/2 -translate-y-1/2";
    case "right":
      return "left-full ml-1 top-1/2 -translate-y-1/2";
    default:
      return "bottom-full mb-1 left-1/2 -translate-x-1/2";
  }
});
</script>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
