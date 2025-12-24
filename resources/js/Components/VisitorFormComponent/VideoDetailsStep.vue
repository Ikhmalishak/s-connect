<script setup lang="ts">
import { ref, onMounted, computed, watch } from "vue";
import { Play, CheckCircle, Clock } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps<{
    isFormValid: boolean;
    videoEnded: boolean;
    resetVideo?: boolean; // Add this prop
}>();

const emit = defineEmits<{
    (e: "video-ended"): void;
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const hasStarted = ref(false);
const showConfirmation = ref(false);

// Watch for reset trigger
watch(
    () => props.resetVideo,
    (newVal) => {
        if (newVal) {
            resetVideoState();
        }
    }
);

function resetVideoState() {
    isPlaying.value = false;
    currentTime.value = 0;
    hasStarted.value = false;
    showConfirmation.value = false;

    if (videoRef.value) {
        videoRef.value.currentTime = 0;
        videoRef.value.pause();
    }
}

const progress = computed(() => {
    return duration.value > 0 ? (currentTime.value / duration.value) * 100 : 0;
});

const formattedTime = computed(() => {
    const current = formatTime(currentTime.value);
    const total = formatTime(duration.value);
    return `${current} / ${total}`;
});

function formatTime(seconds: number): string {
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, "0")}`;
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
    emit("video-ended");
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

onMounted(() => {
    if (videoRef.value) {
        videoRef.value.addEventListener("timeupdate", handleTimeUpdate);
        videoRef.value.addEventListener("loadedmetadata", handleLoadedMetadata);
        videoRef.value.addEventListener("ended", handleVideoEnded);
    }
});
</script>

<template>
    <div class="space-y-2">
            <h3 class="text-lg font-semibold mb-4">{{ t('visitor.video.title') }}</h3>

        <div
            v-if="!isFormValid"
            class="bg-amber-50 border border-amber-200 rounded-lg p-4 flex items-center gap-2"
        >
            <Clock class="h-5 w-5 text-amber-600" />
            <span class="text-amber-800">
                {{ t('visitor.video.completeFormFirst') }}
            </span>
        </div>

        <Card class="max-w-4xl mx-auto">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <Play class="h-5 w-5" />
                    {{ t('visitor.video.videoTitle') }}
                </h3>
            </div>
            <div class="p-6">
                <div class="relative">
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
                            <source src="/assets/short.mp4" type="video/mp4" />
                            Your browser does not support the video tag.
                        </video>

                        <div
                            v-if="!isFormValid"
                            class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center"
                        >
                            <div class="text-center text-white">
                                <Clock class="h-12 w-12 mx-auto mb-2" />
                                <p class="text-lg font-semibold">
                                    {{ t('visitor.video.completeForm') }}
                                </p>
                                <p class="text-sm">
                                    {{ t('visitor.video.fillFields') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="isFormValid && hasStarted"
                        class="mt-4 space-y-2"
                    >
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div
                                class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                :style="`width: ${progress}%`"
                            ></div>
                        </div>

                        <div
                            class="flex justify-between items-center text-sm text-gray-600"
                        >
                            <span>{{ formattedTime }}</span>
                            <span
                                v-if="isPlaying"
                                class="text-blue-600 font-medium"
                                >{{ t('visitor.video.playing') }}</span
                            >
                            <span
                                v-else-if="videoEnded"
                                class="text-green-600 font-medium flex items-center gap-1"
                            >
                                <CheckCircle class="h-4 w-4" />
                                {{ t('visitor.video.completed') }}
                            </span>
                        </div>
                    </div>

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
            </div>
        </Card>

        <Card v-if="showConfirmation" class="max-w-2xl mx-auto">
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center gap-2 text-green-600">
                        <CheckCircle class="h-6 w-6" />
                        <span class="font-semibold"
                            >{{ t('visitor.video.videoCompleted') }}</span
                        >
                    </div>
                </div>
            </div>
        </Card>

        <div class="text-center space-y-2">
            <div class="flex items-center justify-center gap-2">
                <Play class="h-4 w-4 text-gray-600" />
                <span class="text-sm text-gray-600">
                    {{ t('visitor.video.clickPlay') }}
                </span>
            </div>

            <p class="text-xs text-gray-500">
                {{ t('visitor.video.mustWatch') }}
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
