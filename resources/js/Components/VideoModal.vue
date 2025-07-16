<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from "vue";

interface Props {
  visible: boolean;
}
const props = defineProps<Props>();
const emit = defineEmits<{
  (e: "close"): void;
  (e: "ended"): void;
}>();

const videoPlayer = ref<HTMLVideoElement | null>(null);

watch(() => props.visible, (visible) => {
  if (!visible && videoPlayer.value) {
    videoPlayer.value.pause();
    videoPlayer.value.currentTime = 0;
  }
});

function handleEnded() {
  emit("ended");
}
</script>

<template>
  <div
    v-if="visible"
    class="fixed inset-0 bg-black/70 flex items-center justify-center z-50"
  >
    <div class="bg-white rounded-lg overflow-hidden shadow-lg w-full max-w-2xl relative">
      <button
        @click="$emit('close')"
        class="absolute top-2 right-2 text-gray-500 hover:text-gray-700"
        aria-label="Close"
      >
        ✕
      </button>
      <video
        ref="videoPlayer"
        controls
        @ended="handleEnded"
        class="w-full h-auto"
      >
        <source src="/assets/short.mp4" type="video/mp4" />
        Your browser does not support the video tag.
      </video>
    </div>
  </div>
</template>
