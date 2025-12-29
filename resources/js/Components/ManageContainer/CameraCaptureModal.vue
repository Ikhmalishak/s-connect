<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div
                v-if="show"
                class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-[9999]"
                @click.self="closeModal"
            >
                <Transition name="modal-scale" appear>
                    <div
                        v-if="show"
                        class="bg-white rounded-lg shadow-2xl w-full max-w-md mx-4 overflow-hidden"
                    >
                        <!-- Header -->
                        <div class="flex justify-between items-center p-4 border-b bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Take Photo
                            </h3>
                            <button
                                @click="closeModal"
                                class="text-gray-400 hover:text-gray-600 text-2xl font-bold w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-200 transition-colors"
                            >
                                ×
                            </button>
                        </div>

                        <!-- Camera Content -->
                        <div class="p-4">
                            <!-- Camera Feed -->
                            <div v-if="!error && !capturedImage" class="space-y-4">
                                <div class="relative bg-black rounded-lg overflow-hidden aspect-video">
                                    <video
                                        ref="videoRef"
                                        autoplay
                                        playsinline
                                        muted
                                        class="w-full h-full object-cover"
                                    ></video>

                                    <!-- Camera overlay instructions -->
                                    <div class="absolute bottom-2 left-2 right-2 bg-black/50 text-white text-sm p-2 rounded">
                                        Position your camera and tap capture when ready
                                    </div>
                                </div>

                                <!-- Camera Controls -->
                                <div class="flex justify-center gap-3">
                                    <button
                                        @click="capturePhoto"
                                        :disabled="!streamActive"
                                        class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white px-6 py-3 rounded-full font-medium transition-colors flex items-center gap-2"
                                    >
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 5a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V7a2 2 0 00-2-2h-1.586a1 1 0 01-.707-.293l-1.414-1.414A1 1 0 0010.586 3H7.414a1 1 0 00-.707.293L5.293 4.707A1 1 0 014.586 5H4zm8 9a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                                        </svg>
                                        Capture
                                    </button>

                                    <button
                                        @click="switchCamera"
                                        v-if="hasMultipleCameras"
                                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-3 rounded-full font-medium transition-colors"
                                        title="Switch Camera"
                                    >
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Captured Image Preview -->
                            <div v-if="capturedImage && !error" class="space-y-4">
                                <div class="relative bg-gray-100 rounded-lg overflow-hidden aspect-video">
                                    <img
                                        :src="capturedImage"
                                        alt="Captured photo"
                                        class="w-full h-full object-cover"
                                    />
                                </div>

                                <!-- Preview Controls -->
                                <div class="flex justify-center gap-3">
                                    <button
                                        @click="retakePhoto"
                                        class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-medium transition-colors"
                                    >
                                        Retake
                                    </button>
                                    <button
                                        @click="confirmPhoto"
                                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors"
                                    >
                                        Use Photo
                                    </button>
                                </div>
                            </div>

                            <!-- Error State -->
                            <div v-if="error" class="text-center py-8">
                                <div class="text-red-500 mb-4">
                                    <svg class="w-12 h-12 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">
                                    Camera Access Required
                                </h3>
                                <p class="text-gray-600 mb-4">
                                    {{ error }}
                                </p>
                                <button
                                    @click="requestPermissions"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                                >
                                    Try Again
                                </button>
                            </div>

                            <!-- Loading State -->
                            <div v-if="isInitializing" class="text-center py-8">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
                                <p class="text-gray-600">Initializing camera...</p>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick, watch } from "vue";

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "capture", imageData: string): void;
}>();

// Refs
const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const streamRef = ref<MediaStream | null>(null);

// State
const isInitializing = ref(false);
const error = ref("");
const capturedImage = ref("");
const streamActive = ref(false);
const hasMultipleCameras = ref(false);
const currentFacingMode = ref<"user" | "environment">("environment");

// Methods
const closeModal = () => {
    stopCamera();
    capturedImage.value = "";
    error.value = "";
    emit("close");
};

const requestPermissions = async () => {
    error.value = "";
    await initializeCamera();
};

const initializeCamera = async () => {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        error.value = "Camera is not supported on this device";
        return;
    }

    isInitializing.value = true;
    error.value = "";

    try {
        // Check for multiple cameras
        const devices = await navigator.mediaDevices.enumerateDevices();
        const videoDevices = devices.filter(device => device.kind === 'videoinput');
        hasMultipleCameras.value = videoDevices.length > 1;

        // Request camera access
        const constraints: MediaStreamConstraints = {
            video: {
                facingMode: currentFacingMode.value,
                width: { ideal: 1280 },
                height: { ideal: 720 }
            },
            audio: false
        };

        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        streamRef.value = stream;
        streamActive.value = true;

        await nextTick();
        if (videoRef.value) {
            videoRef.value.srcObject = stream;

            // Wait for video to be ready
            await new Promise((resolve) => {
                if (videoRef.value) {
                    videoRef.value.onloadedmetadata = resolve;
                }
            });
        }

    } catch (err: any) {
        console.error("Camera initialization error:", err);
        streamActive.value = false;

        if (err.name === "NotAllowedError") {
            error.value = "Camera access denied. Please allow camera permissions and try again.";
        } else if (err.name === "NotFoundError") {
            error.value = "No camera found on this device.";
        } else if (err.name === "NotReadableError") {
            error.value = "Camera is already in use by another application.";
        } else {
            error.value = "Unable to access camera. Please check your device settings.";
        }
    } finally {
        isInitializing.value = false;
    }
};

const stopCamera = () => {
    if (streamRef.value) {
        streamRef.value.getTracks().forEach(track => track.stop());
        streamRef.value = null;
    }
    streamActive.value = false;

    if (videoRef.value) {
        videoRef.value.srcObject = null;
    }
};

const switchCamera = async () => {
    currentFacingMode.value = currentFacingMode.value === "user" ? "environment" : "user";
    stopCamera();
    await initializeCamera();
};

const capturePhoto = () => {
    if (!videoRef.value || !streamActive.value) return;

    try {
        // Create canvas if it doesn't exist
        if (!canvasRef.value) {
            canvasRef.value = document.createElement('canvas');
        }

        const canvas = canvasRef.value;
        const video = videoRef.value;

        // Set canvas dimensions to match video
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        // Draw current video frame to canvas
        const ctx = canvas.getContext('2d');
        if (ctx) {
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            capturedImage.value = canvas.toDataURL('image/jpeg', 0.9);
        }

    } catch (err) {
        console.error("Photo capture error:", err);
        error.value = "Failed to capture photo. Please try again.";
    }
};

const retakePhoto = async () => {
    capturedImage.value = "";

    // Ensure camera is still active and video is playing
    if (streamActive.value && videoRef.value) {
        try {
            // Try to restart video playback
            await videoRef.value.play();
        } catch (err) {
            console.log("Video play error, reinitializing camera...");
            // If video play fails, reinitialize the entire camera
            await initializeCamera();
        }
    } else {
        // If stream is not active, reinitialize camera
        await initializeCamera();
    }
};

const confirmPhoto = () => {
    if (capturedImage.value) {
        emit("capture", capturedImage.value);
        closeModal();
    }
};

// Lifecycle
onMounted(() => {
    // Canvas will be created when needed
});

onUnmounted(() => {
    stopCamera();
});

// Watch for modal show/hide
watch(() => props.show, async (newVal) => {
    if (newVal) {
        await initializeCamera();
    } else {
        stopCamera();
        capturedImage.value = "";
        error.value = "";
    }
});
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.modal-scale-enter-active,
.modal-scale-leave-active {
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.modal-scale-enter-from,
.modal-scale-leave-to {
    opacity: 0;
    transform: scale(0.8) translateY(-20px);
}
</style>
