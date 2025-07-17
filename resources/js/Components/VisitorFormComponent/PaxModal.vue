<!-- components/ui/BasicModal.vue -->
<script setup lang="ts">
const props = defineProps<{
  show: boolean;
}>();
const emit = defineEmits<{
  (e: "close"): void;
}>();
</script>

<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
  >
    <div class="bg-white p-6 rounded shadow max-w-md w-full">
      <slot />
      <div class="mt-4 text-centre">
        <!-- <button
          class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
          @click="$emit('close')"
        >
          Close
        </button> -->
      </div>
    </div>
  </div>
</template>
