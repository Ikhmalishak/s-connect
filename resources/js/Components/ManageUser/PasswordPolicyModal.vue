<script setup lang="ts">
import { ref, onMounted, watch } from "vue";
import axios from "axios";

interface PasswordPolicy {
    min_length: number;
    require_mixed_case: boolean;
    require_letters: boolean;
    require_numbers: boolean;
    require_symbols: boolean;
}

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits(["close"]);
const policy = ref<PasswordPolicy | null>(null);
const successMessage = ref("");
const errorMessage = ref("");

// Load policy when modal opens
watch(
    () => props.show,
    async (isOpen) => {
        if (isOpen) {
            const res = await axios.get("/password-policy");
            console.log(res.data.data);
            policy.value = res.data.data;
        }
    }
);

async function savePolicy() {
    if (!policy.value) return;

    successMessage.value = "";
    errorMessage.value = "";

    try {
        console.log("Updating Policy...");
        const res = await axios.post("/password-policy", policy.value);

        successMessage.value =
            res.data.message || "Password policy updated successfully!";
        // auto-close after a short delay
        setTimeout(() => emit("close"), 2000);
        setTimeout(() => {
            (successMessage.value = ""), (errorMessage.value = "");
        }, 1500);
    } catch (error: any) {
        errorMessage.value =
            error.response?.data?.message ||
            "Failed to update policy. Please try again.";
    }
}
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
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold text-blue-700">
                                Password Policy
                            </h2>
                            <button
                                @click="emit('close')"
                                class="text-gray-500 hover:text-gray-700 text-xl font-bold"
                            >
                                ×
                            </button>
                        </div>

                        <div v-if="policy" class="space-y-4">
                            <div>
                                <label class="block font-semibold"
                                    >Minimum Length</label
                                >
                                <input
                                    type="number"
                                    v-model="policy.min_length"
                                    class="border rounded p-2 w-24"
                                />
                            </div>

                            <div>
                                <label
                                    ><input
                                        type="checkbox"
                                        v-model="policy.require_letters"
                                    />
                                    Require Letter</label
                                >
                            </div>

                            <div>
                                <label
                                    ><input
                                        type="checkbox"
                                        v-model="policy.require_mixed_case"
                                    />
                                    Require Uppercase</label
                                >
                            </div>

                            <div>
                                <label
                                    ><input
                                        type="checkbox"
                                        v-model="policy.require_numbers"
                                    />
                                    Require Number</label
                                >
                            </div>

                            <div>
                                <label
                                    ><input
                                        type="checkbox"
                                        v-model="policy.require_symbols"
                                    />
                                    Require Special Character</label
                                >
                            </div>

                            <div class="flex flex-row justify-between mt-4">
                                <div>
                                    <div
                                        v-if="successMessage"
                                        class="text-green-600 font-semibold"
                                    >
                                        ✅ {{ successMessage }}
                                    </div>
                                    <div
                                        v-if="errorMessage"
                                        class="text-red-600 font-semibold"
                                    >
                                        ❌ {{ errorMessage }}
                                    </div>
                                </div>

                                <button
                                    @click="savePolicy"
                                    class="bg-blue-600 text-white px-4 py-2 rounded"
                                >
                                    Save
                                </button>
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
