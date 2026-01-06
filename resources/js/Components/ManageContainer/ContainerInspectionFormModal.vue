<script setup lang="ts">
import { ref, onMounted, watch, computed } from "vue";
import axios from "axios";
import CameraCaptureModal from "@/Components/ManageContainer/CameraCaptureModal.vue";

// Interfaces for type safety
interface Question {
    id: number;
    question: string;
    category?: string;
    required?: boolean;
}

interface Answer {
    question_id: number;
    passed: boolean | null;
    remarks?: string;
    photo: File | null;
}

interface FormData {
    received_at: string;
    inspected_at: string;
    answers: Answer[];
}

// Refs
const questions = ref<Question[]>([]);
const formData = ref<FormData>({
    received_at: "",
    inspected_at: "",
    answers: [],
});
const isLoading = ref(false);
const errorMessage = ref("");
const successMessage = ref("");
const showCameraModal = ref(false);
const currentQuestionIndex = ref<number | null>(null);

// Props and Emits
const props = defineProps<{
  show: boolean;
  id: number | null;
  isEditMode?: boolean; // Add this to know if we're editing
}>();

const emit = defineEmits(["close", "save"]);

// Computed properties
const anyFailed = computed(() =>
    formData.value.answers.some((a) => a.passed === false)
);

const anyAnswered = computed(() =>
    formData.value.answers.some((a) => a.passed !== null)
);

const unansweredCount = computed(
    () => formData.value.answers.filter((a) => a.passed === null).length
);

const failedAnswers = computed(() =>
    formData.value.answers.filter((a) => a.passed === false)
);

const shouldShowQuestion = (index: number) => {
    const firstFailedIndex = formData.value.answers.findIndex(a => a.passed === false);
    if (firstFailedIndex !== -1) {
        return index <= firstFailedIndex;
    }
    const firstUnansweredIndex = formData.value.answers.findIndex(a => a.passed === null);
    if (firstUnansweredIndex !== -1) {
        return index <= firstUnansweredIndex;
    }
    return true; // all questions answered
};

const visibleQuestions = computed(() =>
    questions.value.filter((_, index) => shouldShowQuestion(index))
);

const visibleAnswers = computed(() =>
    formData.value.answers.filter((_, index) => shouldShowQuestion(index))
);

// Methods
async function fetchQuestions() {
    try {
        const response = await axios.get("/containers/questions");
        console.log("Questions fetched:", response.data);
        questions.value = response.data;
        errorMessage.value = ""; // Clear any previous errors
    } catch (error) {
        console.error("Error fetching questions:", error);
        errorMessage.value = "Failed to load questions. Please try again.";
    }
}

// NEW: Fetch existing inspection answers for pre-fill
// UPDATED: Fetch existing inspection answers for pre-fill
async function fetchInspectionAnswers(containerId: number) {
    console.log('testttttttgwefbvhbf');
    try {
        isLoading.value = true;
        const response = await axios.get(
            `/containers/inspection-answer`
        );
        
        // Your API returns { data: [...], messages: "..." }
        const inspectionData = response.data.data[0]; // Get first inspection
        
        console.log("Inspection answers fetched:", inspectionData);
        
        // Pre-fill dates
        if (inspectionData.received_at) {
            formData.value.received_at = inspectionData.received_at;
        }
        if (inspectionData.inspected_at) {
            formData.value.inspected_at = inspectionData.inspected_at;
        }
        
        // Pre-fill answers
        if (inspectionData.answers && inspectionData.answers.length > 0) {
            inspectionData.answers.forEach((existingAnswer: any) => {
                // Find the question index in your questions array
                const questionIndex = questions.value.findIndex(
                    (q) => q.id === existingAnswer.inspection_question_id
                );
                if (questionIndex !== -1 && formData.value.answers[questionIndex]) {
                    formData.value.answers[questionIndex] = {
                        question_id: existingAnswer.inspection_question_id,
                        passed: existingAnswer.passed === 1 ? true : existingAnswer.passed === 0 ? false : null,
                        remarks: existingAnswer.remarks || "",
                        photo: null, // Can't pre-fill file input
                    };
                }
            });
        }
        
        console.log("Form pre-filled successfully");
    } catch (error) {
        console.error("Error fetching inspection answers:", error);
        errorMessage.value = "Failed to load inspection data. Please try again.";
    } finally {
        isLoading.value = false;
    }
}

const validateDates = (): boolean => {
    errorMessage.value = ""; // Clear previous messages

    if (!formData.value.received_at) {
        errorMessage.value = "Received date is required";
        return false;
    }
    if (!formData.value.inspected_at) {
        errorMessage.value = "Inspected date is required";
        return false;
    }

    const received = new Date(formData.value.received_at);
    const inspected = new Date(formData.value.inspected_at);

    if (inspected < received) {
        errorMessage.value = "Inspected date cannot be before received date";
        return false;
    }

    const today = new Date();
    const minDate = new Date();
    minDate.setFullYear(minDate.getFullYear() - 1);

    if (received > today || inspected > today) {
        errorMessage.value = "Dates cannot be in the future";
        return false;
    }

    if (received < minDate || inspected < minDate) {
        errorMessage.value = "Dates are too far in the past";
        return false;
    }

    return true;
};

const validateForm = (): boolean => {
    errorMessage.value = ""; // Clear previous messages

    if (anyFailed.value) {
        for (let i = 0; i < formData.value.answers.length; i++) {
            const answer = formData.value.answers[i];
            if (answer.passed === false && !answer.remarks?.trim()) {
                errorMessage.value = `Remarks are required for failed question: ${questions.value[i].question}`;
                return false;
            }
        }
        return true;
    }

    if (unansweredCount.value > 0) {
        errorMessage.value = `Please answer all ${unansweredCount.value} remaining questions`;
        return false;
    }

    return true;
};

const handleFileUpload = (index: number, event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] || null;

    if (file) {
        if (!file.type.startsWith("image/")) {
            errorMessage.value = "Please upload an image file (JPEG, PNG, etc.)";
            target.value = "";
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            errorMessage.value = "File size must be less than 5MB";
            target.value = "";
            return;
        }
    }

    errorMessage.value = ""; // Clear error if file is valid
    handleAnswerChange(index, "photo", file);
};

const handleAnswerChange = (index: number, field: string, value: any) => {
    if (!formData.value.answers[index]) {
        formData.value.answers[index] = {
            question_id: questions.value[index].id,
            passed: null,
            photo: null,
        };
    }

    formData.value.answers[index] = {
        ...formData.value.answers[index],
        [field]: value,
    };

    if (field === "passed" && value !== false) {
        formData.value.answers[index].remarks = "";
        formData.value.answers[index].photo = null;

        const fileInput = document.querySelector(
            `[data-question-index="${index}"]`
        ) as HTMLInputElement;
        if (fileInput) {
            fileInput.value = "";
        }
    }
};

const handleSelectChange = (index: number, event: Event) => {
    const target = event.target as HTMLSelectElement;
    const value = target.value === "" ? null : target.value === "true";
    handleAnswerChange(index, "passed", value);
};

const handleRadioChange = (index: number, value: boolean) => {
    handleAnswerChange(index, "passed", value);
};

const handleInputChange = (index: number, event: Event) => {
    const target = event.target as HTMLInputElement;
    handleAnswerChange(index, "remarks", target.value);
};

const openCameraModal = (index: number) => {
    currentQuestionIndex.value = index;
    showCameraModal.value = true;
};

const handleCameraCapture = (imageData: string) => {
    if (currentQuestionIndex.value !== null) {
        // Convert base64 to File object
        const byteString = atob(imageData.split(',')[1]);
        const mimeString = imageData.split(',')[0].split(':')[1].split(';')[0];
        const ab = new ArrayBuffer(byteString.length);
        const ia = new Uint8Array(ab);
        for (let i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }
        const blob = new Blob([ab], { type: mimeString });
        const file = new File([blob], `camera_capture_${Date.now()}.jpg`, { type: mimeString });

        handleAnswerChange(currentQuestionIndex.value, "photo", file);
    }
    showCameraModal.value = false;
    currentQuestionIndex.value = null;
};

const closeCameraModal = () => {
    showCameraModal.value = false;
    currentQuestionIndex.value = null;
};

const handleApiError = (error: any) => {
    if (axios.isAxiosError(error)) {
        const message = error.response?.data?.message || error.message;
        errorMessage.value = `Submission failed: ${message}`;
    } else {
        errorMessage.value = "An unexpected error occurred. Please try again.";
    }
    console.error("API Error:", error);
};

const onSubmit = async (event: Event) => {
    event.preventDefault();

    if (isLoading.value) return;

    errorMessage.value = ""; // Clear previous messages
    successMessage.value = "";

    if (!validateDates() || !validateForm()) return;

    isLoading.value = true;

    try {
        const submissionData = new FormData();
        submissionData.append("received_at", formData.value.received_at);
        submissionData.append("inspected_at", formData.value.inspected_at);

        const answersToSubmit = anyFailed.value
            ? formData.value.answers.filter(
                  (a) => a.passed === false || a.passed === true
              )
            : formData.value.answers;

        submissionData.append(
            "answers",
            JSON.stringify(
                answersToSubmit.map((a) => ({
                    question_id: a.question_id,
                    passed: a.passed,
                    remarks: a.remarks,
                }))
            )
        );

        formData.value.answers.forEach((answer) => {
            if (answer.photo && answer.passed === false) {
                submissionData.append(
                    `photo_${answer.question_id}`,
                    answer.photo
                );
            }
        });

        const response = await axios.post(
            `/containers/update-inspection/${props.id}`,
            submissionData,
            {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            }
        );

        const data = response.data;
        console.log("Success:", data);

        successMessage.value = "Inspection submitted successfully!";

        // Auto-close after success
        setTimeout(() => {
            emit("close");
            resetForm();
        }, 2000);
    } catch (error) {
        handleApiError(error);
    } finally {
        isLoading.value = false;
    }
};

const resetForm = () => {
    formData.value = {
        received_at: "",
        inspected_at: "",
        answers: questions.value.map((q) => ({
            question_id: q.id,
            passed: null,
            photo: null,
        })),
    };
};

const getCurrentDate = (): string => {
    const now = new Date();
    return now.toISOString().split("T")[0];
};

const getMinDate = (): string => {
    const minDate = new Date();
    minDate.setFullYear(minDate.getFullYear() - 1);
    return minDate.toISOString().split("T")[0];
};

const getStatusBadge = (passed: boolean | null): string => {
    if (passed === null) return "⏳ Not Set";
    if (passed === true) return "✅ Pass";
    return "❌ Fail";
};

const getStatusClass = (passed: boolean | null): string => {
    if (passed === null) return "bg-gray-100 text-gray-800";
    if (passed === true) return "bg-green-100 text-green-800";
    return "bg-red-100 text-red-800";
};

const getBorderClass = (passed: boolean | null): string => {
    if (passed === null) return "border-gray-200 bg-white";
    if (passed === true) return "border-green-200 bg-green-50";
    return "border-red-300 bg-red-50 shadow-sm";
};

// Initialize answers when questions are loaded
watch(questions, () => {
    formData.value.answers = questions.value.map((q) => ({
        question_id: q.id,
        passed: null,
        photo: null,
    }));
});

// NEW: Watch for modal open and handle pre-fill
watch(
    () => props.show,
    async (newVal) => {
        console.log('Modal show changed:', newVal);
        console.log('isEditMode:', props.isEditMode);
        console.log('id:', props.id);
        if (newVal) {
            // First, make sure questions are loaded
            if (questions.value.length === 0) {
                await fetchQuestions();
            }
            
            // Initialize answers array
            formData.value.answers = questions.value.map((q) => ({
                question_id: q.id,
                passed: null,
                photo: null,
            }));
            
            // If edit mode and has ID, fetch existing data
            if (props.isEditMode && props.id) {
                console.log('true');
                await fetchInspectionAnswers(props.id);
            } else {
                // New inspection - set default dates
                const today = getCurrentDate();
                formData.value.received_at = today;
                formData.value.inspected_at = today;
            }
        }
    }
);

onMounted(() => {
    fetchQuestions();
});
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
                        class="bg-white p-6 rounded-lg shadow-[0_4px_20px_rgba(255,255,255,0.6)] w-[90%] max-w-4xl max-h-[90vh] overflow-y-auto"
                    >
                        <div
                            class="flex justify-between items-center mb-6 pb-4 border-b"
                        >
                            <div>
                                <h2 class="text-2xl font-bold text-red-700">
                                    Container Inspection Form
                                </h2>
                                <p class="text-sm text-gray-600 mt-1">
                                    Complete all inspection details below {{ show }} {{ isEditMode }} {{ id }}
                                </p>
                            </div>
                            <button
                                @click="emit('close')"
                                class="text-gray-500 hover:text-gray-700 text-2xl font-bold w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors"
                                :disabled="isLoading"
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

                        <form @submit="onSubmit" class="space-y-6">
                            <!-- Date Section -->
                            <div
                                class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg"
                            >
                                <!-- Received Date -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Container/Truck Received At *
                                    </label>
                                    <input
                                        type="date"
                                        v-model="formData.received_at"
                                        :max="getCurrentDate()"
                                        :min="getMinDate()"
                                        :disabled="isLoading"
                                        class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm transition-colors placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        required
                                    />
                                </div>

                                <!-- Inspected Date -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Container/Truck Inspected At *
                                    </label>
                                    <input
                                        type="date"
                                        v-model="formData.inspected_at"
                                        :max="getCurrentDate()"
                                        :min="getMinDate()"
                                        :disabled="isLoading"
                                        class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm transition-colors placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        required
                                    />
                                </div>
                            </div>

                            <!-- Questions Section -->
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <h3
                                        class="text-lg font-semibold text-gray-900"
                                    >
                                        Inspection Questions
                                    </h3>
                                    <div class="flex gap-2 items-center">
                                        <div
                                            v-if="unansweredCount > 0"
                                            class="px-3 py-1 bg-yellow-100 text-yellow-800 text-sm font-medium rounded-full"
                                        >
                                            {{ unansweredCount }} unanswered
                                        </div>
                                        <div
                                            v-if="anyFailed"
                                            class="px-3 py-1 bg-red-100 text-red-800 text-sm font-medium rounded-full"
                                        >
                                            {{ failedAnswers.length }} failed
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-for="(question, index) in questions"
                                    :key="question.id"
                                    v-show="shouldShowQuestion(index)"
                                    class="p-4 border rounded-lg transition-all duration-200"
                                    :class="
                                        getBorderClass(
                                            formData.answers[index]?.passed
                                        )
                                    "
                                >
                                    <div
                                        class="flex items-start justify-between mb-3"
                                    >
                                        <label
                                            :for="`question-${question.id}`"
                                            class="block text-sm font-medium text-gray-700 flex-1"
                                        >
                                            {{ index + 1 }}.
                                            {{ question.question }}
                                        </label>

                                        <span
                                            class="ml-3 px-2.5 py-1 text-xs font-medium rounded-full shrink-0"
                                            :class="
                                                getStatusClass(
                                                    formData.answers[index]
                                                        ?.passed
                                                )
                                            "
                                        >
                                            {{
                                                getStatusBadge(
                                                    formData.answers[index]
                                                        ?.passed
                                                )
                                            }}
                                        </span>
                                    </div>

                                    <!-- PASS / FAIL Selection -->
                                    <div class="mb-3">
                                        <div class="flex items-center space-x-4">
                                            <label class="flex items-center">
                                                <input
                                                    type="radio"
                                                    :name="`status-${question.id}`"
                                                    :value="true"
                                                    :checked="formData.answers[index]?.passed === true"
                                                    @change="handleRadioChange(index, true)"
                                                    :disabled="isLoading"
                                                    class="mr-2"
                                                />
                                                ✅ Pass
                                            </label>
                                            <label class="flex items-center">
                                                <input
                                                    type="radio"
                                                    :name="`status-${question.id}`"
                                                    :value="false"
                                                    :checked="formData.answers[index]?.passed === false"
                                                    @change="handleRadioChange(index, false)"
                                                    :disabled="isLoading"
                                                    class="mr-2"
                                                />
                                                ❌ Fail
                                            </label>
                                        </div>
                                    </div>

                                    <!-- REMARKS if failed -->
                                    <div
                                        v-if="
                                            formData.answers[index]?.passed ===
                                            false
                                        "
                                        class="mb-3 transition-all duration-200"
                                    >
                                        <label
                                            :for="`remarks-${question.id}`"
                                            class="block text-sm font-medium text-red-700 mb-2"
                                        >
                                            Remarks *
                                        </label>
                                        <input
                                            :id="`remarks-${question.id}`"
                                            type="text"
                                            :value="
                                                formData.answers[index]?.remarks
                                            "
                                            @input="
                                                handleInputChange(index, $event)
                                            "
                                            placeholder="Please provide details about the issue..."
                                            :disabled="isLoading"
                                            class="flex h-10 w-full rounded-md border border-red-300 bg-white px-3 py-2 text-sm shadow-sm transition-colors placeholder:text-red-300 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                                            required
                                        />
                                    </div>

                                    <!-- PHOTO UPLOAD if failed -->
                                    <div
                                        v-if="
                                            formData.answers[index]?.passed ===
                                            false
                                        "
                                        class="transition-all duration-200"
                                    >
                                        <label
                                            class="block text-sm font-medium text-red-700 mb-2"
                                        >
                                            Upload Photo Evidence
                                        </label>

                                        <!-- Photo Upload Options -->
                                        <div class="flex gap-2 mb-3">
                                            <button
                                                type="button"
                                                @click="openCameraModal(index)"
                                                :disabled="isLoading"
                                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                            >
                                                Take Photo
                                            </button>

                                            <div class="relative">
                                                <input
                                                    :id="`photo-${question.id}`"
                                                    :data-question-index="index"
                                                    type="file"
                                                    @change="handleFileUpload(index, $event)"
                                                    accept="image/*"
                                                    :disabled="isLoading"
                                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                                />
                                                <button
                                                    type="button"
                                                    :disabled="isLoading"
                                                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                                >
                                                    Upload from Gallery
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Selected file display -->
                                        <div v-if="formData.answers[index]?.photo" class="text-sm text-gray-600 mb-2">
                                            Selected: {{ formData.answers[index]?.photo.name }}
                                        </div>

                                        <p class="text-xs text-gray-500">
                                            Take a photo with your camera or upload from gallery. Max size: 5MB
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Section -->
                            <div
                                class="flex flex-row justify-between items-center pt-6 mt-6 border-t"
                            >
                                <div class="text-sm text-gray-500">
                                    * Required fields
                                </div>
                                <div class="flex gap-3">
                                    <button
                                        type="button"
                                        @click="emit('close')"
                                        :disabled="isLoading"
                                        class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 h-10 px-6 py-2"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="isLoading"
                                        class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 disabled:pointer-events-none disabled:opacity-50 bg-red-700 text-white shadow hover:bg-red-800 h-10 px-6 py-2 min-w-24"
                                    >
                                        <span
                                            v-if="isLoading"
                                            class="flex items-center"
                                        >
                                            <svg
                                                class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                            >
                                                <circle
                                                    class="opacity-25"
                                                    cx="12"
                                                    cy="12"
                                                    r="10"
                                                    stroke="currentColor"
                                                    stroke-width="4"
                                                ></circle>
                                                <path
                                                    class="opacity-75"
                                                    fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                ></path>
                                            </svg>
                                            Processing...
                                        </span>
                                        <span v-else> Submit Inspection </span>
                                    </button>
                                </div>
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

/* Custom scrollbar for modal */
.modal-content::-webkit-scrollbar {
    width: 6px;
}

.modal-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.modal-content::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.modal-content::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
