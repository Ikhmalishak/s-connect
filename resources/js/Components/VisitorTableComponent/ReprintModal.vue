<script setup lang="ts">
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { ref } from "vue";
import axios from "axios";

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits(["close"]);

//variable for visitor sticker number
const ackNumber = ref("");

//alert messages
const alertMessage = ref("");

//function to reprint visitor sticker
async function reprintSticker() {
    try {
        const res = await axios.get(`/visitor/reprint-visitor-sticker/${ackNumber.value}`);

        console.log("Successfully reprinted sticker", res.data);

        alertMessage.value = res.data.status; // "success"
        ackNumber.value = "";
    } catch (error) {
        console.error("Reprint error:", error);

        if (error.response && error.response.data) {
            alertMessage.value = error.response.data.status; // "error"
            console.log("Output:", error.response.data.output);
        } else {
            alertMessage.value = "Unexpected error occurred";
        }
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div
                v-if="show"
                class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[9999]"
                @click.self="emit('close')"
            >
                <Transition name="slide-fade" appear>
                    <div
                        v-if="show"
                        class="bg-white rounded-2xl shadow-2xl w-[90%] max-w-xl p-6"
                    >
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold">
                                Reprint Sticker
                            </h2>
                            <button
                                @click="emit('close')"
                                class="text-gray-400 hover:text-gray-600 text-2xl leading-none"
                            >
                                ×
                            </button>
                        </div>

                        <!-- Description -->
                        <p class="text-sm text-gray-600 mb-4">
                            Reprint a visitor verification sticker if the
                            original is missing. Enter the verification number
                            below. You can also hover over the visitor’s name in
                            the list to see the number.
                        </p>

                        <!-- Input -->
                        <Input
                            placeholder="Enter verification number"
                            class="w-full mb-4"
                            v-model="ackNumber"
                        />

                        <!-- Feedback message -->
                        <p
                            v-if="alertMessage"
                            :class="
                                alertMessage === 'success'
                                    ? 'text-green-600 font-medium mb-3'
                                    : 'text-red-600 font-medium mb-3'
                            "
                        >
                            {{
                                alertMessage === "success"
                                    ? "Sticker reprinted successfully!"
                                    : "Failed to reprint sticker."
                            }}
                        </p>

                        <!-- Actions -->
                        <div class="flex justify-end gap-2">
                            <Button @click="reprintSticker"> Reprint </Button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.8s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateX(20px);
    opacity: 0;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}
</style>
