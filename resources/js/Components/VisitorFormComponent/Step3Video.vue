<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Play, CheckCircle, Clock } from "lucide-vue-next";

// Props
const props = defineProps<{
  videoEnded: boolean;
  isFormValid?: boolean;
}>();

// Emits
const emit = defineEmits<{
  (e: "videoEnded"): void;
  (e: "confirmRead"): void;
}>();

// Video state
const videoRef = ref<HTMLVideoElement | null>(null);
const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const hasStarted = ref(false);
const showConfirmation = ref(false);

// Computed
const progress = computed(() => {
  return duration.value > 0 ? (currentTime.value / duration.value) * 100 : 0;
});

const formattedTime = computed(() => {
  const current = formatTime(currentTime.value);
  const total = formatTime(duration.value);
  return `${current} / ${total}`;
});

const canConfirm = computed(() => {
  return props.videoEnded && hasStarted.value;
});

// Methods
function formatTime(seconds: number): string {
  const mins = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);
  return `${mins}:${secs.toString().padStart(2, '0')}`;
}

function playVideo() {
  if (videoRef.value) {
    hasStarted.value = true;
    videoRef.value.play();
    isPlaying.value = true;
  }
}

function pauseVideo() {
  if (videoRef.value) {
    videoRef.value.pause();
    isPlaying.value = false;
  }
}

function handleVideoEnded() {
  isPlaying.value = false;
  showConfirmation.value = true;
  emit("videoEnded");
}

function handleTimeUpdate() {
  if (videoRef.value) {
    currentTime.value = videoRef.value.currentTime;
  }
}

function handleLoadedMetadata() {
  if (videoRef.value) {
    duration.value = videoRef.value.duration;
  }
}

function handleConfirmRead() {
  emit("confirmRead");
}

// Lifecycle
onMounted(() => {
  if (videoRef.value) {
    videoRef.value.addEventListener('timeupdate', handleTimeUpdate);
    videoRef.value.addEventListener('loadedmetadata', handleLoadedMetadata);
    videoRef.value.addEventListener('ended', handleVideoEnded);
  }
});
</script>

<template>
  <div class="space-y-6">
    <div class="text-center">
      <h2 class="text-2xl font-bold mb-2">Security Briefing</h2>
      <p class="text-gray-600">
        Please watch the security briefing video before proceeding
      </p>
    </div>

    <!-- Form Validation Message -->
    <div 
      v-if="!isFormValid" 
      class="bg-amber-50 border border-amber-200 rounded-lg p-4 flex items-center gap-2"
    >
      <Clock class="h-5 w-5 text-amber-600" />
      <span class="text-amber-800">
        Please complete all required fields in the previous steps before watching the video
      </span>
    </div>

    <!-- Video Section -->
    <Card class="max-w-4xl mx-auto">
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Play class="h-5 w-5" />
          SKP Security Guidelines Video
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div class="relative">
          <!-- Video Player -->
          <div class="relative bg-black rounded-lg overflow-hidden">
            <video
              ref="videoRef"
              class="w-full h-auto"
              :class="{ 'opacity-50': !isFormValid }"
              controls
              :disabled="!isFormValid"
              preload="metadata"
              @play="isPlaying = true"
              @pause="isPlaying = false"
            >
              <source src="/assets/short.mp4" type="video/mp4">
              Your browser does not support the video tag.
            </video>
            
            <!-- Overlay for disabled state -->
            <div 
              v-if="!isFormValid"
              class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center"
            >
              <div class="text-center text-white">
                <Clock class="h-12 w-12 mx-auto mb-2" />
                <p class="text-lg font-semibold">Complete form first</p>
                <p class="text-sm">Fill in all required fields to unlock the video</p>
              </div>
            </div>
          </div>

          <!-- Video Controls Info -->
          <div v-if="isFormValid && hasStarted" class="mt-4 space-y-2">
            <!-- Progress Bar -->
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div 
                class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                :style="`width: ${progress}%`"
              ></div>
            </div>
            
            <!-- Time Display -->
            <div class="flex justify-between items-center text-sm text-gray-600">
              <span>{{ formattedTime }}</span>
              <span v-if="isPlaying" class="text-blue-600 font-medium">Playing...</span>
              <span v-else-if="videoEnded" class="text-green-600 font-medium flex items-center gap-1">
                <CheckCircle class="h-4 w-4" />
                Completed
              </span>
            </div>
          </div>

          <!-- Play Button for Initial State -->
          <div 
            v-if="isFormValid && !hasStarted" 
            class="absolute inset-0 flex items-center justify-center"
          >
            <Button
              @click="playVideo"
              size="lg"
              class="rounded-full w-20 h-20 bg-blue-600 hover:bg-blue-700"
            >
              <Play class="h-8 w-8 text-white" />
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Confirmation Section -->
    <Card v-if="showConfirmation" class="max-w-2xl mx-auto">
      <CardContent class="p-6">
        <div class="space-y-4">
          <div class="flex items-center gap-2 text-green-600">
            <CheckCircle class="h-6 w-6" />
            <span class="font-semibold">Video completed successfully!</span>
          </div>
          
          <div class="flex items-start gap-3 p-4 bg-gray-50 rounded-lg">
            <input
              type="checkbox"
              :checked="videoEnded"
              disabled
              class="mt-1 h-4 w-4 border-gray-300 rounded focus:ring-blue-500"
            />
            <label class="text-sm font-medium text-gray-900">
              I hereby confirm that I have read and understood the SKP security guidelines 
              and will comply with all safety protocols during my visit.
            </label>
          </div>
          
          <div class="text-xs text-gray-500 mt-2">
            * This confirmation is automatically checked after watching the complete video
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Instructions -->
    <div class="text-center space-y-2">
      <div class="flex items-center justify-center gap-2">
        <img
          src="/assets/icon/play.png"
          alt="Play Icon"
          class="w-4 h-4"
        />
        <span class="text-sm text-gray-600">
          Click the play button to start the security briefing video
        </span>
      </div>
      
      <p class="text-xs text-gray-500">
        You must watch the complete video before proceeding to the next step
      </p>
    </div>
  </div>
</template>

<style scoped>
video::-webkit-media-controls {
  display: none !important;
}

video::-webkit-media-controls-enclosure {
  display: none !important;
}
</style>