<script setup lang="ts">
import { ref, watch } from "vue";
import axios from "axios";
import CameraCaptureModal from "@/Components/ManageContainer/CameraCaptureModal.vue";

const props = defineProps<{ show: boolean; id: number | null }>();
const emit = defineEmits(["close", "save"]);
const isLoading = ref(false);
const errorMessage = ref("");
const successMessage = ref("");
const selectedPhoto = ref("");
const showCameraModal = ref(false);

// Reactive object to hold files
const formData = ref<Record<string, File | null>>({});

// File input ref
const fileInputRef = ref<HTMLInputElement | null>(null);

// Clear messages when modal is closed or reopened
watch(() => props.show, (newVal) => {
    if (!newVal) {
        errorMessage.value = "";
        successMessage.value = "";
        isLoading.value = false;
    }
});

const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] || null;

    if (!file) return;

    // File type validation
    if (!file.type.startsWith("image/")) {
        errorMessage.value = "Only image files allowed!";
        target.value = "";
        return;
    }

    // File size limit 5MB
    if (file.size > 5 * 1024 * 1024) {
        errorMessage.value = "File must be less than 5MB!";
        target.value = "";
        return;
    }

    const key = target.name;
    formData.value[key] = file;
    selectedPhoto.value = URL.createObjectURL(file);
    errorMessage.value = ""; // Clear error on successful file selection
    console.log(formData.value);
};

const takePhoto = () => {
    showCameraModal.value = true;
};

const handleCameraCapture = (imageData: string) => {
    // Convert base64 to File object
    const byteString = atob(imageData.split(',')[1]);
    const mimeString = imageData.split(',')[0].split(':')[1].split(';')[0];
    const ab = new ArrayBuffer(byteString.length);
    const ia = new Uint8Array(ab);
    for (let i = 0; i < byteString.length; i++) {
        ia[i] = byteString.charCodeAt(i);
    }
    const blob = new Blob([ab], { type: mimeString });
    const file = new File([blob], `security_check_${Date.now()}.jpg`, { type: mimeString });

    formData.value['security_checking_photo'] = file;
    selectedPhoto.value = URL.createObjectURL(file);
    showCameraModal.value = false;
};

const closeCameraModal = () => {
    showCameraModal.value = false;
};

const onSubmit = async () => {
    // Clear previous messages
    errorMessage.value = "";
    successMessage.value = "";

    if (Object.values(formData.value).some((f) => !f)) {
        errorMessage.value = "All photos are required!";
        return;
    }

    if (!props.id) {
        errorMessage.value = "Container ID is missing!";
        return;
    }

    isLoading.value = true;

    console.log(formData.value);
    try {
        const data = new FormData();
        data.append("shipment_transport_id", props.id.toString());

        for (const [key, file] of Object.entries(formData.value)) {
            if (file) data.append(key, file);
        }

        const res = await axios.post("/containers/submit-security-checking", data, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        console.log("Success:", res.data);

        successMessage.value = "Security checking completed successfully!";

        // Auto-close after success
        setTimeout(() => {
            emit("close");
        }, 2000);

    } catch (err: any) {
        console.error("Error:", err);
        if (err.response?.data?.message) {
            errorMessage.value = err.response.data.message;
        } else {
            errorMessage.value = "Failed to submit security checking. Please try again.";
        }
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div
                v-if="show"
                class="fixed inset-0 bg-black/50 backdrop-blur-md flex items-center justify-center z-[9999]"
                @click.self="emit('close')"
            >
                <Transition name="modal-scale" appear>
                    <div
                        class="bg-white p-6 rounded-lg shadow w-[80%] max-w-3xl max-h-[80vh] overflow-y-auto"
                    >
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold text-black">
                                Create Container Record
                            </h2>
                            <button
                                @click="emit('close')"
                                class="text-gray-500 hover:text-gray-700 text-xl font-bold"
                            >
                                ×
                            </button>
                        </div>

                        <!-- Error Message -->
                        <div v-if="errorMessage" class="p-4 bg-red-50 border border-red-200 rounded-md mb-4">
                            <p class="text-red-800 text-sm">{{ errorMessage }}</p>
                        </div>

                        <!-- Success Message -->
                        <div v-if="successMessage" class="p-4 bg-green-50 border border-green-200 rounded-md mb-4">
                            <p class="text-green-800 text-sm">{{ successMessage }}</p>
                        </div>

                        <form @submit.prevent="onSubmit" class="space-y-4">
                            <div class="flex flex-col gap-2">
                                <label class="font-medium text-black">Security Check Photo</label>

                                <!-- Photo Upload Options -->
                                <div class="flex gap-3 mb-3">
                                    <button
                                        type="button"
                                        @click="takePhoto"
                                        :disabled="isLoading"
                                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-xs font-medium rounded transition-colors"
                                    >
                                        Take Photo
                                    </button>

                                    <div class="relative">
                                        <input
                                            ref="fileInputRef"
                                            type="file"
                                            name="security_checking_photo"
                                            accept="image/*"
                                            :disabled="isLoading"
                                            @change="handleFileUpload($event)"
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                        />
                                        <button
                                            type="button"
                                            :disabled="isLoading"
                                            class="px-3 py-1.5 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white text-xs font-medium rounded transition-colors"
                                        >
                                            Upload
                                        </button>
                                    </div>
                                </div>

                                <!-- Photo Preview -->
                                <div v-if="selectedPhoto" class="mt-3">
                                    <img :src="selectedPhoto" class="max-h-40 mx-auto rounded border" />
                                </div>

                                <p class="text-xs text-gray-600">
                                    Take a photo with your camera or upload from gallery. Max size: 5MB
                                </p>
                            </div>

                            <div class="flex justify-end mt-4">
                                <button
                                    type="submit"
                                    :disabled="isLoading || !selectedPhoto"
                                    class="bg-red-700 text-white px-6 py-2 rounded-md hover:bg-red-800 disabled:opacity-50"
                                >
                                    {{
                                        isLoading
                                            ? "Uploading..."
                                            : "Submit Inspection"
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>

        <!-- Camera Capture Modal -->
        <CameraCaptureModal
            :show="showCameraModal"
            @close="closeCameraModal"
            @capture="handleCameraCapture"
        />
    </Teleport>
</template>

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
    transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.modal-scale-enter-from,
.modal-scale-leave-to {
    opacity: 0;
    transform: perspective(1000px) rotateX(-90deg) scale(0.3) translateY(-200px);
}
</style>
