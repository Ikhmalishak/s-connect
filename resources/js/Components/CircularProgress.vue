<script setup lang="ts">
import { computed, toRefs } from "vue";

interface Props {
  percentage: number;
}

const props = defineProps<Props>();

// Pie chart circumference
const circumference = 2 * Math.PI * 15.9155;

const strokeDashoffset = computed(() => {
  return circumference * (1 - props.percentage / 100);
});
</script>

<template>
  <div class="flex flex-col items-center bg-white border border-gray-300 rounded shadow p-2 w-24 h-24">
    <svg viewBox="0 0 36 36" class="w-20 h-20 transform">
      <!-- Background Circle -->
      <path
        class="text-gray-200"
        d="M18 2.0845
           a 15.9155 15.9155 0 0 1 0 31.831
           a 15.9155 15.9155 0 0 1 0 -31.831"
        fill="none"
        stroke="currentColor"
        stroke-width="3"
      />
      <!-- Progress Circle -->
      <path
        class="text-blue-600 transition-all duration-300"
        d="M18 2.0845
           a 15.9155 15.9155 0 0 1 0 31.831
           a 15.9155 15.9155 0 0 1 0 -31.831"
        fill="none"
        stroke="currentColor"
        stroke-width="3"
        stroke-linecap="round"
        :stroke-dasharray="circumference"
        :stroke-dashoffset="strokeDashoffset"
        transform="rotate(-90 18 18)"
      />
      <!-- Text -->
      <text
        x="18"
        y="20.35"
        class="text-xs fill-gray-700 font-medium"
        text-anchor="middle"
      >
        {{ percentage }}%
      </text>
    </svg>
    <span class="text-xs mt-1 text-gray-600">Progress</span>
  </div>
</template>
