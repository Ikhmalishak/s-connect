<!-- resources/js/Components/FormHeader.vue -->
<template>
  <Card
    class="mt-4 mx-auto max-w-3xl w-full shadow-lg shadow-opacity-30 mb-4 text-2xl font-bold flex items-center justify-between bg-gray-100"
  >
    <div class="ml-6 flex flex-row space-x-2 items-center">
      <div><img src="/assets/SC-01.png" alt="" class="w-16 h-16" /></div>
      <div class="text-lg">S-CONNECT</div>
    </div>
    <div class="mr-6 text-lg">
      {{ title }}
    </div>
  </Card>
</template>

<script setup lang="ts">
defineProps<{
  title: string;
}>();
</script>
