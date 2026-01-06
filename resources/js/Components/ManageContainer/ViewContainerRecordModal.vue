<script setup lang="ts">
import axios from "axios";
import { ref, watch, computed } from "vue";

const props = defineProps<{
    show: boolean;
    id: number | null;
}>();

async function fetchInspectionDetails(id: number) {
    console.log("Fetching inspection details for ID:", id);
    const res = await axios.get(`/containers/inspection-details/${id}`);
    console.log("Inspection details response:", res.data.data);
    inspectionDetails.value = res.data.data;
}

const emit = defineEmits(["close", "save"]);
const inspectionDetails = ref(null);
const excludeLabels = ["security_checking_photo"];

const filteredPhotos = computed(() => {
    return (
        inspectionDetails.value?.transport?.photo?.filter(
            (p: any) => !excludeLabels.includes(p.label)
        ) || []
    );
});

watch(
    () => props.id,
    (newId) => {
        if (newId !== null) {
            fetchInspectionDetails(newId);
        }
    }
);
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
                        v-if="show"
                        class="bg-white p-6 rounded-lg shadow-lg w-[80%] max-w-2xl max-h-[80vh] overflow-y-auto"
                    >
                        <div
                            class="flex justify-between items-center mb-6 pb-4 border-b"
                        >
                            <h2 class="text-xl font-bold text-red-700">
                                Container Report
                            </h2>
                            <button
                                @click="emit('close')"
                                class="text-gray-400 hover:text-gray-600 text-2xl font-bold w-8 h-8 flex items-center justify-center rounded hover:bg-gray-100 transition-colors"
                            >
                                ×
                            </button>
                        </div>

                        <div class="space-y-4">
                            <!-- Transport Details -->
                            <div
                                class="bg-gray-50 p-4 rounded-lg grid grid-cols-1 md:grid-cols-2 gap-6 text-sm"
                            >
                                <!-- Left Column: Transport -->
                                <div class="space-y-2">
                                    <h3
                                        class="font-semibold text-gray-700 mb-2"
                                    >
                                        Transport
                                    </h3>
                                    <div class="flex">
                                        <span class="text-gray-600 w-24"
                                            >Type:</span
                                        >
                                        <span class="text-gray-900">
                                            {{
                                                inspectionDetails?.transport
                                                    ?.transport_type
                                            }}
                                        </span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-600 w-24"
                                            >Number:</span
                                        >
                                        <span class="text-gray-900">
                                            {{
                                                inspectionDetails?.transport
                                                    ?.transport_number
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Right Column: Timeline -->
                                <div class="space-y-2">
                                    <h3
                                        class="font-semibold text-gray-700 mb-2"
                                    >
                                        Timeline
                                    </h3>
                                    <div class="flex">
                                        <span class="text-gray-600 w-28"
                                            >Received:</span
                                        >
                                        <span class="text-gray-900">{{
                                            inspectionDetails?.received_at
                                        }}</span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-600 w-28"
                                            >Inspected:</span
                                        >
                                        <span class="text-gray-900">{{
                                            inspectionDetails?.inspected_at
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Container Photos -->
                            <div
                                v-if="
                                    inspectionDetails?.transport?.photo?.length
                                "
                            >
                                <div class="flex items-center gap-3 mb-4">
                                    <h3 class="font-bold text-gray-800 text-lg">
                                        Container Photos
                                    </h3>
                                    <span class="text-sm text-gray-500">
                                        ({{ filteredPhotos.length }} photos)
                                    </span>
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <div
                                        v-for="(value, index) in filteredPhotos"
                                        :key="index"
                                        class="group relative"
                                    >
                                        <a
                                            :href="'/storage/' + value.photo_path"
                                            rel="noopener noreferrer"
                                            class="block relative overflow-hidden rounded-lg border-2 border-gray-300 hover:border-orange-500 transition-all duration-200"
                                        >
                                            <img
                                                :src="
                                                    '/storage/' + value.photo_path
                                                "
                                                :alt="value.label"
                                                class="w-full h-32 object-cover group-hover:scale-105 transition-transform duration-200"
                                            />
                                            <div
                                                class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors duration-200 flex items-center justify-center"
                                            >
                                                <svg
                                                    class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"
                                                    />
                                                </svg>
                                            </div>
                                        </a>
                                        <p
                                            class="text-xs font-medium text-gray-600 mt-2 text-center truncate"
                                            :title="value.label.replace(/_/g, ' ')"
                                        >
                                            {{
                                                value.label
                                                    .replace(/_/g, " ")
                                                    .toUpperCase()
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
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
