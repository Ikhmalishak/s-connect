<script setup lang="ts">
import { ref } from "vue";
import axios from "axios";

const props = defineProps<{ show: boolean; id: number | null }>();
const emit = defineEmits(["close", "save"]);
const isLoading = ref(false);

// Define all photo fields
const photoFields = [
    "pallet_condition_photo",
    "pallet_label_photo",
    //     "gps_photo_before_installation",
    //     "container_truck_photo",
    //     "empty_container_photo",
    //     "inside_gps_photo",
    //     "half_loaded_photo",
    //     "one_side_door_closed_with_container_number_photo",
    //     "complete_loaded_photo",
    //     "outside_gps_photo",
    //     "security_seal_photo",
    //     "container_full_seal_photo",
];

// Reactive object to hold files
const formData = ref<Record<string, File | null>>({});
photoFields.forEach((f) => (formData.value[f] = null));

const handleFileUpload = (field: string, event: Event) => {
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

    formData.value[field] = file;
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
    try {
        const data = new FormData();

        // const values = Object.values(formData.value);
        //Object.values will return every values inside object in array
        // console.log(values);

        for (const [key, file] of Object.entries(formData.value)) {
            if (file) {
                console.log("Field:", key, "File:", file);
                // or append to FormData
                data.append(`photos[${key}]`, file);
            }
        }

        data.append("shipment_transport_id", props.id.toString());

        const res = await axios.post("/containers/create-photo", data, {
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
                            <div
                                v-for="field in photoFields"
                                :key="field"
                                class="flex flex-col gap-2"
                            >
                                <label class="font-medium text-black">{{
                                    field.replace(/_/g, " ").toUpperCase()
                                }}</label>
                                <input
                                    type="file"
                                    accept="image/*"
                                    :disabled="isLoading"
                                    @change="handleFileUpload(field, $event)"
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
