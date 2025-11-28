<script setup lang="ts">
import axios from "axios";
import { ref, watch } from "vue";

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
                                Container Inspection Details
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
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="font-semibold text-gray-700 mb-3">
                                    Transport Details
                                </h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex">
                                        <span class="text-gray-600 w-32"
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
                                        <span class="text-gray-600 w-32"
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
                            </div>

                            <!-- Timestamps -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="font-semibold text-gray-700 mb-3">
                                    Timeline
                                </h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex">
                                        <span class="text-gray-600 w-32"
                                            >Received:</span
                                        >
                                        <span class="text-gray-900">{{
                                            inspectionDetails?.received_at
                                        }}</span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-600 w-32"
                                            >Inspected:</span
                                        >
                                        <span class="text-gray-900">{{
                                            inspectionDetails?.inspected_at
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Checklist -->
                            <div>
                                <h3 class="font-semibold text-gray-700 mb-3">
                                    Inspection Checklist
                                </h3>
                                <div class="space-y-2">
                                    <div
                                        v-for="a in inspectionDetails?.answers"
                                        :key="a.id"
                                        class="items-start gap-3 p-3 rounded border"
                                        :class="
                                            a.passed === 1
                                                ? 'bg-green-50 border-green-200'
                                                : 'bg-red-50 border-red-200'
                                        "
                                    >
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-lg flex-shrink-0"
                                                :class="
                                                    a.passed === 1
                                                        ? 'text-green-600'
                                                        : 'text-red-600'
                                                "
                                            >
                                                {{ a.passed === 1 ? "✓" : "✗" }}
                                            </span>
                                            <span
                                                class="text-sm text-gray-700"
                                                >{{ a.question.question }}</span
                                            >
                                        </div>
                                        <div
                                            v-if="a.remarks"
                                            class="mt-2 flex gap-2 flex-wrap"
                                        >
                                            <span
                                                class="text-sm text-gray-600 font-semibold"
                                                >Remarks:</span
                                            >
                                            <span
                                                class="text-sm text-gray-800"
                                                >{{ a.remarks }}</span
                                            >
                                        </div>
                                        <div
                                            v-if="a.photo_path"
                                            class="mt-2 flex gap-2 flex-wrap"
                                        >
                                            <a
                                                :href="`/storage/${a.photo_path}`"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                <img
                                                    :src="`/storage/${a.photo_path}`"
                                                    alt="Inspection Photo"
                                                    class="w-20 h-20 object-cover rounded border cursor-pointer"
                                                />
                                            </a>
                                        </div>
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
