<script setup>
import { computed } from 'vue';

const props = defineProps({
  count: {
    type: Number,
    required: true,
  },
  max: {
    type: Number,
    default: 99,
  },
});

const displayCount = computed(() => {
  return props.count > props.max ? `${props.max}+` : props.count;
});

</script>

<template>
  <div class="relative inline-block">
    <slot />
    <span
      v-if="count > 0"
      class="absolute -top-1 -right-1 bg-red-600 text-white text-[12px] font-bold h-6 w-6 flex items-center justify-center rounded-full border border-white"
    >
      {{ displayCount }}
    </span>
  </div>
</template>



