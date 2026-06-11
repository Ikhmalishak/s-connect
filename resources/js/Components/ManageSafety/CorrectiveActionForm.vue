<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import Button from '@/components/ui/button/Button.vue';

const props = defineProps<{
    show: boolean;
    session: any;
}>();

const emit = defineEmits(["close", "saved"]);

const isSubmitting = ref(false);
const errorMessage = ref('');

// Get only failed answers
const failedAnswers = computed(() => {
    if (!props.session?.answers) return [];
    return props.session.answers.filter((a: any) => a.answer === 0 || a.answer === '0');
});

// Track per-answer inputs
const actionInputs = ref<Record<number, { description: string; file: File | null; preview: string | null }>>({});

// Initialize inputs when session data is available
function initInputs() {
    const inputs: Record<number, any> = {};
    for (const answer of failedAnswers.value) {
        inputs[answer.id] = { description: '', file: null, preview: null };
    }
    actionInputs.value = inputs;
}

// Watch for show changes
watch(() => props.show, (newVal) => {
    if (newVal) {
        initInputs();
        errorMessage.value = '';
    }
});

function handleFileChange(answerId: number, event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            actionInputs.value[answerId] = {
                ...actionInputs.value[answerId],
                file: input.files![0],
                preview: e.target?.result as string,
            };
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeFile(answerId: number) {
    actionInputs.value[answerId] = {
        ...actionInputs.value[answerId],
        file: null,
        preview: null,
    };
}

async function handleSubmit() {
    // Validate all findings have descriptions
    const missingDescriptions = Object.entries(actionInputs.value)
        .filter(([_, input]) => !input.description.trim());

    if (missingDescriptions.length > 0) {
        errorMessage.value = `Please provide a corrective action description for all findings (${missingDescriptions.length} missing).`;
        return;
    }

    isSubmitting.value = true;
    errorMessage.value = '';

    try {
        const formData = new FormData();
        formData.append('session_id', props.session.id);

        // Append each action as indexed array for proper Laravel validation
        const answerIds = Object.keys(actionInputs.value);
        answerIds.forEach((answerIdStr, index) => {
            const answerId = parseInt(answerIdStr);
            const input = actionInputs.value[answerId];
            formData.append(`actions[${index}][answer_id]`, String(answerId));
            formData.append(`actions[${index}][description]`, input.description);
            if (input.file) {
                formData.append(`actions[${index}][corrective_evidence]`, input.file);
            }
        });

        const res = await axios.post('/safety/corrective-action/submit', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        emit('saved', res.data);
        initInputs();
        emit('close');
    } catch (err: any) {
        errorMessage.value = err.response?.data?.message || 'Failed to submit corrective action.';
    } finally {
        isSubmitting.value = false;
    }
}

// Check if all fields are filled
const allFieldsFilled = computed(() => {
    if (Object.keys(actionInputs.value).length === 0) return false;
    return Object.values(actionInputs.value).every(input => input.description.trim().length > 0);
});

// Image preview lightbox
const showImagePreview = ref(false);
const selectedImage = ref<string | null>(null);

function openImagePreview(photoPath: string) {
    selectedImage.value = photoPath;
    showImagePreview.value = true;
}

function closeImagePreview() {
    showImagePreview.value = false;
    selectedImage.value = null;
}
</script>

<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div
                v-if="show"
                class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[9999] p-4"
                @click.self="emit('close')"
            >
                <Transition name="modal-scale" appear>
                    <div
                        v-if="session"
                        class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden"
                    >
                        <!-- HEADER -->
                        <div class="flex justify-between items-center p-6 border-b bg-gray-50">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">
                                    Corrective Actions
                                </h2>
                                <p class="text-sm text-gray-500">
                                    Submit corrective actions for each failed finding
                                </p>
                            </div>
                            <button
                                @click="emit('close')"
                                class="text-gray-400 hover:text-gray-600 text-3xl w-10 h-10 flex items-center justify-center rounded-full hover:bg-gray-200 transition"
                            >
                                ×
                            </button>
                        </div>

                        <!-- BODY -->
                        <div class="p-6 overflow-y-auto space-y-6">
                            <!-- Session Info -->
                            <div class="grid grid-cols-2 gap-4 bg-blue-50 p-4 rounded-lg border border-blue-100">
                                <div>
                                    <label class="text-xs font-semibold text-blue-700 uppercase">Site</label>
                                    <p class="text-gray-900 font-medium">{{ session.site?.name }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-blue-700 uppercase">Audit Type</label>
                                    <p class="text-gray-900 font-medium">{{ session.audit_type?.name }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-blue-700 uppercase">Department</label>
                                    <p class="text-gray-900 font-medium">{{ session.department?.name }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-blue-700 uppercase">Date</label>
                                    <p class="text-gray-900 font-medium">{{ session.date }}</p>
                                </div>
                            </div>

                            <!-- Per-Finding Corrective Actions -->
                            <div>
                                <h3 class="text-lg font-bold mb-3">
                                    Failed Findings & Your Corrective Actions ({{ failedAnswers.length }})
                                </h3>

                                <div v-if="failedAnswers.length === 0" class="text-sm text-gray-500 italic">
                                    No failed items found.
                                </div>

                                <div v-for="(answer, idx) in failedAnswers" :key="answer.id" class="mb-5 border border-gray-200 rounded-xl overflow-hidden">
                                    <!-- Finding (Read Only) -->
                                    <div class="bg-red-50 p-4 border-b border-red-200">
                                        <div class="flex items-start gap-3">
                                            <div class="w-7 h-7 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0 text-xs font-bold">
                                                {{ idx + 1 }}
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-gray-800 font-semibold text-sm">
                                                    {{ answer.question?.question_text || 'Unknown question' }}
                                                </p>
                                                <p v-if="answer.remarks" class="text-sm text-gray-600 mt-1">
                                                    <span class="font-semibold">Remark:</span> {{ answer.remarks }}
                                                </p>
                                                <div v-if="answer.photo_path" class="mt-2">
                                                    <button @click="openImagePreview(answer.photo_path)" class="block relative overflow-hidden rounded-lg border-2 border-gray-300 hover:border-blue-500 transition-all w-24 h-24">
                                                        <img :src="'/storage/' + answer.photo_path" class="w-full h-full object-cover" alt="Finding photo" />
                                                    </button>
                                                    <p class="text-xs text-gray-400 mt-1">Safety team photo</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- PIC Input Section -->
                                    <div class="p-4 bg-white">
                                        <div class="flex items-start gap-3">
                                            <div class="w-7 h-7 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0 text-xs font-bold mt-0.5">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                                </svg>
                                            </div>
                                            <div class="flex-1 space-y-3">
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                                                        Your Corrective Action <span class="text-red-500">*</span>
                                                    </label>
                                                    <textarea
                                                        :value="actionInputs[answer.id]?.description || ''"
                                                        @input="(e) => { if (actionInputs[answer.id]) actionInputs[answer.id].description = (e.target as HTMLTextAreaElement).value }"
                                                        rows="2"
                                                        class="w-full rounded-lg border-gray-300 border p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                        placeholder="Describe the corrective action taken for this finding..."
                                                    ></textarea>
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                                                        Evidence Photo (Optional)
                                                    </label>
                                                    <div v-if="!actionInputs[answer.id]?.preview" class="flex items-center gap-3">
                                                        <label class="cursor-pointer bg-white border-2 border-dashed border-gray-300 rounded-lg p-3 hover:border-blue-500 transition flex items-center gap-2">
                                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                            </svg>
                                                            <span class="text-xs text-gray-500">Upload photo</span>
                                                            <input type="file" accept="image/*" class="hidden" @change="handleFileChange(answer.id, $event)" />
                                                        </label>
                                                    </div>
                                                    <div v-else class="relative inline-block">
                                                        <img :src="actionInputs[answer.id]?.preview" class="h-24 w-auto object-cover rounded-lg border" alt="Preview" />
                                                        <button @click="removeFile(answer.id)" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600">×</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Error Message -->
                                <div v-if="errorMessage" class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                                    {{ errorMessage }}
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div class="p-4 bg-gray-50 border-t flex justify-end gap-3">
                            <Button variant="outline" @click="emit('close')" class="px-6 py-2">Cancel</Button>
                            <Button @click="handleSubmit" :disabled="isSubmitting || !allFieldsFilled" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ isSubmitting ? 'Submitting...' : 'Submit All Corrective Actions' }}
                            </Button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>

    <!-- Image Preview Lightbox -->
    <Transition name="modal-fade">
        <div v-if="showImagePreview" class="fixed inset-0 bg-black/90 backdrop-blur-sm flex items-center justify-center z-[10000]" @click.self="closeImagePreview">
            <Transition name="modal-scale" appear>
                <div class="relative max-w-4xl max-h-[90vh] mx-4">
                    <button @click="closeImagePreview" class="absolute -top-12 right-0 text-white hover:text-gray-300 text-2xl font-bold w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/10 transition-colors z-10">×</button>
                    <img :src="'/storage/' + selectedImage" alt="Photo" class="max-w-full max-h-[80vh] object-contain rounded-lg shadow-2xl" />
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.modal-scale-enter-active, .modal-scale-leave-active { transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
.modal-scale-enter-from, .modal-scale-leave-to { opacity: 0; transform: scale(0.92) translateY(20px); }
</style>