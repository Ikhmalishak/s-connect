<template>
  <div
    v-if="visible"
    class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
  >
    <div class="bg-white rounded-lg px-6 py-4 flex flex-col items-center shadow-lg">
      <!-- Spinner -->
      <svg
        class="animate-spin h-8 w-8 text-blue-500 mb-3"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
      >
        <circle
          class="opacity-25"
          cx="12"
          cy="12"
          r="10"
          stroke="currentColor"
          stroke-width="4"
        ></circle>
        <path
          class="opacity-75"
          fill="currentColor"
          d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
        ></path>
      </svg>
      <!-- Message -->
      <p class="text-gray-700 font-medium">{{ message }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps({
  visible: { type: Boolean, default: false },
  message: { type: String, default: "Verifying data, please wait..." },
});
</script>
