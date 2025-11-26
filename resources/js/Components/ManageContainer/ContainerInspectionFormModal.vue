<script setup lang="ts">
import { ref, onMounted, watch, computed } from "vue";
import axios from "axios";

// Interfaces for type safety
interface Question {
    id: number;
    question: string;
    category?: string;
    required?: boolean;
}

interface Answer {
    question_id: number;
    passed: boolean | null; // Changed to allow null for neutral state
    remarks?: string;
    photo: File | null;
}

interface FormData {
    received_at: string;
    inspected_at: string;
    answers: Answer[];
}

// Refs
const transportType = ref<"Truck" | "Container" | "">("");
const questions = ref<Question[]>([]);
const formData = ref<FormData>({
    received_at: "",
    inspected_at: "",
    answers: [],
});
const isLoading = ref(false);

// Props and Emits
const props = defineProps<{
  show: boolean;
  id: number | null;
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

// Show all questions if none failed, otherwise only show failed questions
const shouldShowQuestion = (index: number) =>
    !anyFailed.value || formData.value.answers[index]?.passed === false;

// Get visible questions (for validation)
const visibleQuestions = computed(() =>
    questions.value.filter((_, index) => shouldShowQuestion(index))
);

// Get visible answers (for validation)
const visibleAnswers = computed(() =>
    formData.value.answers.filter((_, index) => shouldShowQuestion(index))
);

// Methods
async function fetchQuestions() {
    try {
        const response = await axios.get("/containers/questions");
        console.log("Questions fetched:", response.data);
        questions.value = response.data;
    } catch (error) {
        console.error("Error fetching questions:", error);
        alert("Failed to load questions. Please try again.");
    }
}

const validateDates = (): boolean => {
    if (!formData.value.received_at) {
        alert("Received date is required");
        return false;
    }
    if (!formData.value.inspected_at) {
        alert("Inspected date is required");
        return false;
    }

    const received = new Date(formData.value.received_at);
    const inspected = new Date(formData.value.inspected_at);

    if (inspected < received) {
        alert("Inspected date cannot be before received date");
        return false;
    }

    // Check if dates are within reasonable range
    const today = new Date();
    const minDate = new Date();
    minDate.setFullYear(minDate.getFullYear() - 1); // Allow up to 1 year back

    if (received > today || inspected > today) {
        alert("Dates cannot be in the future");
        return false;
    }

    if (received < minDate || inspected < minDate) {
        alert("Dates are too far in the past");
        return false;
    }

    return true;
};

const validateForm = (): boolean => {
    // If there are any failed questions, validate only the visible (failed) questions
    if (anyFailed.value) {
        for (let i = 0; i < formData.value.answers.length; i++) {
            const answer = formData.value.answers[i];
            // Only validate failed questions (which are visible)
            if (answer.passed === false && !answer.remarks?.trim()) {
                alert(
                    `Remarks are required for failed question: ${questions.value[i].question}`
                );
                return false;
            }
        }
        return true;
    }

    // If no failures, validate all questions are answered
    if (unansweredCount.value > 0) {
        alert(`Please answer all ${unansweredCount.value} remaining questions`);
        return false;
    }

    return true;
};

const handleFileUpload = (index: number, event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] || null;

    if (file) {
        // Validate file type
        if (!file.type.startsWith("image/")) {
            alert("Please upload an image file (JPEG, PNG, etc.)");
            target.value = "";
            return;
        }

        // Validate file size (5MB limit)
        if (file.size > 5 * 1024 * 1024) {
            alert("File size must be less than 5MB");
            target.value = "";
            return;
        }
    }

    handleAnswerChange(index, "photo", file);
};

const handleAnswerChange = (index: number, field: string, value: any) => {
    if (!formData.value.answers[index]) {
        formData.value.answers[index] = {
            question_id: questions.value[index].id,
            passed: null, // Default to null (neutral)
            photo: null,
        };
    }

    formData.value.answers[index] = {
        ...formData.value.answers[index],
        [field]: value,
    };

    // Clear remarks and photo if switching from fail to pass or neutral
    if (field === "passed" && value !== false) {
        formData.value.answers[index].remarks = "";
        formData.value.answers[index].photo = null;

        // Clear file input
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

const handleInputChange = (index: number, event: Event) => {
    const target = event.target as HTMLInputElement;
    handleAnswerChange(index, "remarks", target.value);
};

const handleApiError = (error: any) => {
    if (axios.isAxiosError(error)) {
        const message = error.response?.data?.message || error.message;
        alert(`Submission failed: ${message}`);
    } else {
        alert("An unexpected error occurred. Please try again.");
    }
    console.error("API Error:", error);
};

const onSubmit = async (event: Event) => {
    event.preventDefault();

    if (isLoading.value) return;

    // Validate form
    if (!validateDates() || !validateForm()) return;

    isLoading.value = true;

    try {
        // Prepare form data for file upload
        const submissionData = new FormData();
        submissionData.append("received_at", formData.value.received_at);
        submissionData.append("inspected_at", formData.value.inspected_at);

        // Only submit answers for visible questions (all if no failures, only failed if any failures)
        const answersToSubmit = anyFailed.value
            ? formData.value.answers.filter(
                  (a) => a.passed === false || a.passed === true
              ) // Submit failed and any answered passes
            : formData.value.answers; // Submit all if no failures

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

        // Append photos for failed questions
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

        // Show success message
        alert("Inspection submitted successfully!");

        // Close modal and reset
        emit("close");
        resetForm();
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
            passed: null, // Changed to null for neutral state
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
        passed: null, // Changed to null for neutral state
        photo: null,
    }));
});

// Set default dates when modal opens
watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            const today = getCurrentDate();
            formData.value.received_at = today;
            formData.value.inspected_at = today;
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
                                    Complete all inspection details below
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
                                        <label
                                            class="sr-only"
                                            :for="`status-${question.id}`"
                                        >
                                            Status for {{ question.question }}
                                        </label>
                                        <select
                                            :id="`status-${question.id}`"
                                            :value="
                                                formData.answers[index]?.passed
                                            "
                                            @change="
                                                handleSelectChange(
                                                    index,
                                                    $event
                                                )
                                            "
                                            :disabled="isLoading"
                                            class="flex h-10 w-full max-w-xs rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        >
                                            <option :value="null">
                                                -- Select --
                                            </option>
                                            <option :value="true">
                                                ✅ Pass
                                            </option>
                                            <option :value="false">
                                                ❌ Fail
                                            </option>
                                        </select>
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
                                            :for="`photo-${question.id}`"
                                            class="block text-sm font-medium text-red-700 mb-2"
                                        >
                                            Upload Photo Evidence
                                        </label>
                                        <input
                                            :id="`photo-${question.id}`"
                                            :data-question-index="index"
                                            type="file"
                                            @change="
                                                handleFileUpload(index, $event)
                                            "
                                            accept="image/*"
                                            :disabled="isLoading"
                                            class="flex h-10 w-full rounded-md border border-red-300 bg-white px-3 py-2 text-sm shadow-sm transition-colors file:border-0 file:bg-red-50 file:text-red-700 file:font-medium file:text-sm file:px-4 file:py-2 file:mr-4 file:rounded-md hover:file:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        />
                                        <p class="text-xs text-gray-500 mt-1">
                                            Accepted: JPEG, PNG, GIF. Max size:
                                            5MB
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
