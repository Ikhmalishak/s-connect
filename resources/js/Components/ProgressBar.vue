<script setup lang="ts">
interface Props {
  percentage: number;
}
const props = defineProps<Props>();
</script>

<template>
  <div class="w-48 bg-white border border-gray-300 rounded shadow p-2">
    <div class="flex justify-between items-center mb-1">
      <span class="text-xs font-medium text-gray-600">Progress</span>
      <span class="text-xs font-medium text-gray-600">{{ percentage }}%</span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
      <div
        class="bg-blue-600 h-2 transition-all duration-300"
        :style="{ width: percentage + '%' }"
      ></div>
    </div>
  </div>
</template>