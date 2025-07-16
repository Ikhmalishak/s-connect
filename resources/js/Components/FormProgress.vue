<template>
  <div class="absolute bottom-4 right-4 space-y-2">
    <CircularProgress :percentage="progressPercentage" />
    <ProgressBar :percentage="progressPercentage" />
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import ProgressBar from "./ProgressBar.vue";
import CircularProgress from "./CircularProgress.vue";

const props = defineProps<{
  values: Record<string, any>;
  requiredFields: string[];
}>();

const progressPercentage = computed(() => {
  const filledCount = props.requiredFields.filter((field) => {
    const value = props.values[field];
    return value !== undefined && value !== null && value !== "";
  }).length;
  return Math.round((filledCount / props.requiredFields.length) * 100);
});
</script>
