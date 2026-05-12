<script setup lang="ts">
import { ref } from "vue";
import axios from "axios";

const props = defineProps<{
    show: boolean;
    pic: any;
}>();

const emit = defineEmits(["close", "deleted"]);

const isLoading = ref(false);
const errorMessage = ref("");

async function confirmDelete() {
    if (!props.pic?.id) return;

    isLoading.value = true;
    errorMessage.value = "";

    try {
        await axios.delete(`/safety/audit-pics/${props.pic.id}`);
        emit("deleted");
        emit("close");
    } catch (error: any) {
        errorMessage.value = error.response?.data?.message || "Failed to remove PIC assignment.";
    } finally {
        isLoading.value = false;
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div
                v-if="show"
                class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[9999] p-4"
                @click.self="emit('close')"
            >
                <Transition name="modal-scale" appear>
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full">
                                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-center text-gray-900 mb-2">Remove PIC Assignment</h3>
                            <p class="text-sm text-gray-600 text-center mb-6">
                                Are you sure you want to remove
                                <strong>{{ pic?.user?.name || 'this user' }}</strong>
                                as PIC for <strong>{{ pic?.site?.name || 'N/A' }}</strong> /
                                <strong>{{ pic?.department?.name || 'N/A' }}</strong>?
                                <br />This action cannot be undone.
                            </p>

                            <div v-if="errorMessage" class="p-3 bg-red-50 border border-red-200 rounded-md mb-4">
                                <p class="text-red-800 text-sm">{{ errorMessage }}</p>
                            </div>

                            <div class="flex justify-end gap-3">
                                <button
                                    @click="emit('close')"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                                >Cancel</button>
                                <button
                                    @click="confirmDelete"
                                    :disabled="isLoading"
                                    class="px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center gap-2"
                                >
                                    <div v-if="isLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                    Remove
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
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.modal-scale-enter-active, .modal-scale-leave-active {
    transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-scale-enter-from, .modal-scale-leave-to {
    opacity: 0;
    transform: scale(0.92) translateY(20px);
}
</style>
