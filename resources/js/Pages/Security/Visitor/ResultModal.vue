<script setup>
defineProps({
  open: Boolean
});

const emit = defineEmits(["update:open"]);

function closeModal() {
  emit("update:open", false);
}
</script>

<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
  >
    <div class="bg-white p-6 rounded shadow text-center">
      <h2 class="text-lg font-bold mb-2">Submission Successful!</h2>
      <p class="mb-4">Thank you. Your form has been submitted.</p>
      <button
        class="bg-green-600 text-white px-4 py-2 rounded"
        @click="closeModal"
      >
        Close
      </button>
    </div>
  </div>
</template>
