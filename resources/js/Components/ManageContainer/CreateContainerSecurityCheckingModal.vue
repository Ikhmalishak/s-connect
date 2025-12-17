<script setup lang="ts">
import { ref } from "vue";
import axios from "axios";

const props = defineProps<{ show: boolean; id: number | null }>();
const emit = defineEmits(["close", "save"]);
const isLoading = ref(false);

// Reactive object to hold files
const formData = ref<Record<string, File | null>>({});

const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] || null;

    if (!file) return;

    // File type validation
    if (!file.type.startsWith("image/")) {
        alert("Only image files allowed!");
        target.value = "";
        return;
    }

    // File size limit 5MB
    if (file.size > 5 * 1024 * 1024) {
        alert("File must be less than 5MB!");
        target.value = "";
        return;
    }
    const key = target.name;

    formData.value[key] = file;
    console.log(formData.value);
};

const onSubmit = async () => {
    if (Object.values(formData.value).some((f) => !f)) {
        alert("All photos are required!");
        return;
    }

    if (!props.id) {
        alert("Container ID is missing!");
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
        
        const res = await axios.post("/test", data, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        console.log("Success:", res.data);
        emit("close");
    } catch (err) {
        console.error("Error:", err);
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

                        <form @submit.prevent="onSubmit" class="space-y-4">
                            <div class="flex flex-col gap-2">
                                <label class="font-medium text-black"></label>
                                <input
                                    type="file"
                                    name="security_checking_photo"
                                    accept="image/*"
                                    :disabled="isLoading"
                                    @change="handleFileUpload($event)"
                                    class="file:border-0 file:bg-blue-50 file:text-blue-700 file:font-medium file:px-4 file:py-2 file:rounded-md hover:file:bg-blue-100"
                                />
                            </div>

                            <div class="flex justify-end mt-4">
                                <button
                                    type="submit"
                                    :disabled="isLoading"
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
