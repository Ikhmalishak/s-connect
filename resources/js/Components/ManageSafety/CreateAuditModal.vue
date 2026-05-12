<script setup lang="ts">
import { ref, onMounted, watch, computed } from "vue";
import axios from "axios";
import CameraCaptureModal from "@/Components/ManageContainer/CameraCaptureModal.vue";

// Interfaces for type safety
interface Question {
    id: number;
    question_text: string;
    category?: string;
    required?: boolean;
}

interface Section {
    id: number;
    name: string;
    questions: Question[];
}

interface Department {
    id: number;
    name: string;
}

interface Site {
    id: number;
    name: string;
}
// No change needed, but adding Section interface for clarity

interface Answer {
    question_id: number;
    answer: number | null;
    remarks?: string;
    photo: File | null;
}

interface FormData {
    inspected_at: string;
    department: number | null;
    site: number | null;
    answers: Answer[];
}

interface AuditType {
    id: number;
    name: string;
    description?: string;
}

// Refs
const questions = ref<Question[]>([]);
const sections = ref<Section[]>([]);
const sites = ref<Site[]>([]);
const departments = ref<Department[]>([]);
const auditTypes = ref<AuditType[]>([]);
const selectedAuditType = ref<number | null>(null);
const showAuditTypeSelection = ref(true);
const formData = ref<FormData>({
    inspected_at: "",
    department: null,
    site: 2,
    answers: [],
});

const isLoading = ref(false);
const isLoadingAuditTypes = ref(false);
const errorMessage = ref("");
const successMessage = ref("");
const showCameraModal = ref(false);
const currentQuestionIndex = ref<number | null>(null);

// Props and Emits
const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits(["close", "save"]);

// Computed properties — NO (0) = fail, YES (1) = pass, NA (2) = not applicable
const anyFailed = computed(() =>
    formData.value.answers.some((a) => a.answer === 0),
);

// Methods

async function fetchSites() {
    return await axios.get("/api/sites");
}

async function fetchDepartments() {
    return await axios.get("/api/departments");
}

async function loadData() {
    try {
        isLoading.value = true;

        const [siteList, departmentList] = await Promise.all([
            fetchSites(),
            fetchDepartments(),
        ]);

        sites.value = siteList.data;
        departments.value = departmentList.data.data;

        console.log(sites.value);
        console.log(departments.value);
    } catch (error) {
        console.error("Failed to load data:", error);
    } finally {
        isLoading.value = false;
    }
}

async function fetchAuditTypes() {
    isLoadingAuditTypes.value = true;
    try {
        const response = await axios.get("/safety/audit-types");
        auditTypes.value = response.data.audit_types;
        errorMessage.value = "";
    } catch (error) {
        console.error("Error fetching audit types:", error);
        errorMessage.value = "Failed to load audit types. Please try again.";
    } finally {
        isLoadingAuditTypes.value = false;
    }
}

async function fetchQuestions() {
    if (!selectedAuditType.value) return;

    isLoading.value = true;
    try {
        const response = await axios.get(`/safety/question-lists`, {
            params: { audit_type_id: selectedAuditType.value },
        });
        console.log("Sections and questions fetched:", response.data);

        // Handle response structure: if it's {sections: [...]}, use that; otherwise assume array
        let sectionsData: Section[];
        if (response.data.sections && Array.isArray(response.data.sections)) {
            sectionsData = response.data.sections;
        } else if (Array.isArray(response.data)) {
            sectionsData = response.data;
        } else {
            throw new Error(
                "Invalid response format: expected array of sections",
            );
        }

        sections.value = sectionsData;

        // Flatten questions for formData answers array
        const allQuestions = sectionsData.flatMap(
            (section: Section) => section.questions,
        );
        questions.value = allQuestions;

        errorMessage.value = "";

        // Set default dates
        const today = getCurrentDate();
        formData.value.inspected_at = today;

        // Initialize answers array based on total questions
        formData.value.answers = allQuestions.map((q: Question) => ({
            question_id: q.id,
            answer: null,
            remarks: "",
            photo: null,
        }));

        // Move to inspection form
        showAuditTypeSelection.value = false;
    } catch (error) {
        console.error("Error fetching questions:", error);
        errorMessage.value = "Failed to load questions. Please try again.";
    } finally {
        isLoading.value = false;
    }
}

const validateForm = (): boolean => {
    errorMessage.value = ""; // Clear previous messages

    if (anyFailed.value) {
        for (let i = 0; i < formData.value.answers.length; i++) {
            const answer = formData.value.answers[i];
            if (answer.answer === 0 && !answer.remarks?.trim()) {
                errorMessage.value = `Remarks are required for failed question: ${questions.value[i].question_text}`;
                return false;
            }
        }
        return true;
    }

    return true;
};

const handleFileUpload = (index: number, event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] || null;

    if (file) {
        if (!file.type.startsWith("image/")) {
            errorMessage.value =
                "Please upload an image file (JPEG, PNG, etc.)";
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
    if (formData.value.answers[index]) {
        formData.value.answers[index] = {
            ...formData.value.answers[index],
            [field]: value,
        };

        // Clear remarks & photo only when answer is NOT fail (NO = 0)
        if (field === "answer" && value !== 0) {
            formData.value.answers[index].remarks = "";
            formData.value.answers[index].photo = null;

            const fileInput = document.querySelector(
                `[data-question-index="${index}"]`,
            ) as HTMLInputElement;
            if (fileInput) {
                fileInput.value = "";
            }
        }
    }
};
// Removed unnecessary initialization since answers are pre-initialized

const handleRadioChange = (index: number, value: number) => {
    handleAnswerChange(index, "answer", value);
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
        const byteString = atob(imageData.split(",")[1]);
        const mimeString = imageData.split(",")[0].split(":")[1].split(";")[0];
        const ab = new ArrayBuffer(byteString.length);
        const ia = new Uint8Array(ab);
        for (let i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }
        const blob = new Blob([ab], { type: mimeString });
        const file = new File([blob], `camera_capture_${Date.now()}.jpg`, {
            type: mimeString,
        });

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

    if (!validateForm()) return;

    isLoading.value = true;

    try {
        const submissionData = new FormData();
        submissionData.append(
            "audit_type_id",
            selectedAuditType.value?.toString() || "",
        );

        submissionData.append("site_id", formData.value.site?.toString() ?? "");
        submissionData.append(
            "department_id",
            formData.value.department?.toString() ?? "",
        );

        const answersToSubmit = anyFailed.value
            ? formData.value.answers.filter((a) => a.answer !== null)
            : formData.value.answers;

        submissionData.append(
            "answers",
            JSON.stringify(
                answersToSubmit.map((a) => ({
                    question_id: a.question_id,
                    answer: a.answer,
                    remarks: a.remarks,
                })),
            ),
        );

        formData.value.answers.forEach((answer) => {
            if (answer.photo && answer.answer === 0) {
                submissionData.append(
                    `photo_${answer.question_id}`,
                    answer.photo,
                );
            }
        });

        const response = await axios.post(
            "/safety/submit-inspection",
            submissionData,
            {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            },
        );

        successMessage.value = "Inspection submitted successfully!";

        // Auto-close after success
        setTimeout(() => {
            emit("save", response.data);
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
    selectedAuditType.value = null;
    showAuditTypeSelection.value = true;
    questions.value = [];
    sections.value = [];
    formData.value = {
        inspected_at: "",
        department: null,
        site: null,
        answers: [],
    };
    errorMessage.value = "";
    successMessage.value = "";
};

// Helper functions for section-based layout
const getPreviousQuestionsCount = (sectionIndex: number): number => {
    return sections.value
        .slice(0, sectionIndex)
        .reduce((acc, section) => acc + section.questions.length, 0);
};

const getQuestionNumber = (
    sectionIndex: number,
    questionIndex: number,
): number => {
    return getPreviousQuestionsCount(sectionIndex) + questionIndex + 1;
};

const getAnswerForQuestion = (globalIndex: number) => {
    return formData.value.answers[globalIndex];
};
// Added helper functions for proper question indexing across sections

const getCurrentDate = (): string => {
    const now = new Date();
    return now.toISOString().split("T")[0];
};

const getMinDate = (): string => {
    const minDate = new Date();
    minDate.setFullYear(minDate.getFullYear() - 1);
    return minDate.toISOString().split("T")[0];
};

const getStatusBadge = (answer: number | null): string => {
    if (answer === null) return "⏳ Not Set";
    if (answer === 2) return "⚪ N/A";
    if (answer === 1) return "✅ Pass";
    return "❌ Fail";
};

const getStatusClass = (answer: number | null): string => {
    if (answer === null) return "bg-gray-100 text-gray-800";
    if (answer === 2) return "bg-gray-100 text-gray-800";
    if (answer === 1) return "bg-green-100 text-green-800";
    return "bg-red-100 text-red-800";
};

const getBorderClass = (answer: number | null): string => {
    if (answer === null) return "border-gray-200 bg-white";
    if (answer === 2) return "border-gray-200 bg-white";
    if (answer === 1) return "border-green-200 bg-green-50";
    return "border-red-300 bg-red-50 shadow-sm";
};

const selectAuditType = (typeId: number) => {
    selectedAuditType.value = typeId;
    fetchQuestions();
};

const goBackToAuditSelection = () => {
    showAuditTypeSelection.value = true;
    selectedAuditType.value = null;
    questions.value = [];
    errorMessage.value = "";
};

// Watch for modal open and handle pre-fill
watch(
    () => props.show,
    async (value) => {
        if (value) {
            await fetchAuditTypes();
            await loadData();
            const today = getCurrentDate();
            resetForm();
            formData.value.inspected_at = today;
        } else {
            resetForm();
        }
    },
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
                                    {{
                                        showAuditTypeSelection
                                            ? "Select an audit type to begin"
                                            : "Complete all inspection details below"
                                    }}
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
                        <div
                            v-if="errorMessage"
                            class="p-4 bg-red-50 border border-red-200 rounded-md mb-4"
                        >
                            <p class="text-red-800 text-sm">
                                {{ errorMessage }}
                            </p>
                        </div>

                        <!-- Success Message -->
                        <div
                            v-if="successMessage"
                            class="p-4 bg-green-50 border border-green-200 rounded-md mb-4"
                        >
                            <p class="text-green-800 text-sm">
                                {{ successMessage }}
                            </p>
                        </div>

                        <!-- Audit Type Selection -->
                        <div v-if="showAuditTypeSelection" class="space-y-6">
                            <div class="text-center mb-8">
                                <div
                                    class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4"
                                >
                                    <svg
                                        class="w-10 h-10 text-red-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                        ></path>
                                    </svg>
                                </div>
                                <h3
                                    class="text-xl font-semibold text-gray-900 mb-2"
                                >
                                    Choose Audit Type
                                </h3>
                                <p class="text-gray-600">
                                    Please select the type of audit you want to
                                    perform
                                </p>
                            </div>

                            <div
                                v-if="isLoadingAuditTypes"
                                class="text-center py-12"
                            >
                                <div
                                    class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-red-700"
                                ></div>
                                <p class="mt-2 text-gray-600">
                                    Loading audit types...
                                </p>
                            </div>

                            <div
                                v-else
                                class="grid grid-cols-1 md:grid-cols-2 gap-4"
                            >
                                <div
                                    v-for="auditType in auditTypes"
                                    :key="auditType.id"
                                    @click="selectAuditType(auditType.id)"
                                    class="p-6 border-2 border-gray-200 rounded-lg cursor-pointer transition-all hover:border-red-300 hover:shadow-lg hover:scale-105"
                                >
                                    <div
                                        class="flex items-start justify-between"
                                    >
                                        <div class="flex-1">
                                            <h4
                                                class="text-lg font-semibold text-gray-900 mb-2"
                                            >
                                                {{ auditType.name }}
                                            </h4>
                                            <p
                                                v-if="auditType.description"
                                                class="text-gray-600 text-sm"
                                            >
                                                {{ auditType.description }}
                                            </p>
                                        </div>
                                        <div class="ml-4">
                                            <svg
                                                class="w-6 h-6 text-gray-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M9 5l7 7-7 7"
                                                ></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Inspection Form -->
                        <form v-else @submit="onSubmit" class="space-y-6">
                            <!-- Selected Audit Type Info -->
                            <div
                                class="bg-blue-50 border border-blue-200 rounded-md p-3 mb-4"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-sm text-blue-800"
                                            >Audit Type:</span
                                        >
                                        <span
                                            class="font-medium text-blue-900 ml-2"
                                        >
                                            {{
                                                auditTypes.find(
                                                    (t) =>
                                                        t.id ===
                                                        selectedAuditType,
                                                )?.name
                                            }}
                                        </span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="goBackToAuditSelection"
                                        class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                                    >
                                        Change
                                    </button>
                                </div>
                            </div>

                            <div
                                class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-50 rounded-lg"
                            >
                                <!-- Received Date -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Inspected At
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

                                <!-- Site -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Site
                                    </label>
                                    <select
                                        v-model="formData.site"
                                        :disabled="isLoading"
                                        class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        required
                                    >
                                        <option disabled value="">
                                            Select Site
                                        </option>
                                        <option
                                            v-for="site in sites"
                                            :key="site.id"
                                            :value="site.id"
                                        >
                                            {{ site.name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Department -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Department
                                    </label>
                                    <select
                                        v-model="formData.department"
                                        :disabled="isLoading"
                                        class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        required
                                    >
                                        <option disabled value="">
                                            Select Department
                                        </option>
                                        <option
                                            v-for="department in departments"
                                            :key="department.id"
                                            :value="department.id"
                                        >
                                            {{ department.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Questions Section -->
                            <div class="space-y-6">
                                <div class="flex justify-between items-center">
                                    <h3
                                        class="text-lg font-semibold text-gray-900"
                                    >
                                        Inspection Questions
                                    </h3>
                                </div>

                                <div v-if="isLoading" class="text-center py-8">
                                    <div
                                        class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-red-700"
                                    ></div>
                                    <p class="mt-2 text-gray-600">
                                        Loading questions...
                                    </p>
                                </div>

                                <div
                                    v-else-if="sections.length === 0"
                                    class="text-center py-8 text-gray-500"
                                >
                                    No sections available for this audit type.
                                </div>

                                <template v-else>
                                    <div
                                        v-for="(
                                            section, sectionIndex
                                        ) in sections"
                                        :key="section.id"
                                        class="mb-8"
                                    >
                                        <div
                                            class="flex items-center mb-4 p-3 bg-gray-50 rounded-lg"
                                        >
                                            <h4
                                                class="text-md font-semibold text-gray-800 flex-1"
                                            >
                                                {{ section.name }}
                                            </h4>
                                            <span class="text-sm text-gray-500">
                                                {{ section.questions.length }}
                                                questions
                                            </span>
                                        </div>

                                        <div
                                            v-for="(
                                                question, questionIndex
                                            ) in section.questions"
                                            :key="question.id"
                                            class="mb-4 p-4 border rounded-lg transition-all duration-200"
                                            :class="
                                                getBorderClass(
                                                    getAnswerForQuestion(
                                                        questionIndex +
                                                            getPreviousQuestionsCount(
                                                                sectionIndex,
                                                            ),
                                                    )?.answer,
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
                                                    {{
                                                        getQuestionNumber(
                                                            sectionIndex,
                                                            questionIndex,
                                                        )
                                                    }}.
                                                    {{ question.question_text }}
                                                    <span
                                                        v-if="question.required"
                                                        class="text-red-500 ml-1"
                                                        >*</span
                                                    >
                                                </label>

                                                <span
                                                    class="ml-3 px-2.5 py-1 text-xs font-medium rounded-full shrink-0"
                                                    :class="
                                                        getStatusClass(
                                                            getAnswerForQuestion(
                                                                questionIndex +
                                                                    getPreviousQuestionsCount(
                                                                        sectionIndex,
                                                                    ),
                                                            )?.answer,
                                                        )
                                                    "
                                                >
                                                    {{
                                                        getStatusBadge(
                                                            getAnswerForQuestion(
                                                                questionIndex +
                                                                    getPreviousQuestionsCount(
                                                                        sectionIndex,
                                                                    ),
                                                            )?.answer,
                                                        )
                                                    }}
                                                </span>
                                            </div>

                                            <!-- PASS / FAIL / NA Selection -->
                                            <div class="mb-3">
                                                <div
                                                    class="flex items-center space-x-4"
                                                >
                                                    <label
                                                        class="flex items-center"
                                                    >
                                                        <input
                                                            type="radio"
                                                            :name="`status-${question.id}`"
                                                            :value="1"
                                                            :checked="
                                                                getAnswerForQuestion(
                                                                    questionIndex +
                                                                        getPreviousQuestionsCount(
                                                                            sectionIndex,
                                                                        ),
                                                                )?.answer === 1
                                                            "
                                                            @change="
                                                                handleRadioChange(
                                                                    questionIndex +
                                                                        getPreviousQuestionsCount(
                                                                            sectionIndex,
                                                                        ),
                                                                    1,
                                                                )
                                                            "
                                                            :disabled="
                                                                isLoading
                                                            "
                                                            class="mr-2"
                                                        />
                                                        ✅ Pass
                                                    </label>
                                                    <label
                                                        class="flex items-center"
                                                    >
                                                        <input
                                                            type="radio"
                                                            :name="`status-${question.id}`"
                                                            :value="0"
                                                            :checked="
                                                                getAnswerForQuestion(
                                                                    questionIndex +
                                                                        getPreviousQuestionsCount(
                                                                            sectionIndex,
                                                                        ),
                                                                )?.answer === 0
                                                            "
                                                            @change="
                                                                handleRadioChange(
                                                                    questionIndex +
                                                                        getPreviousQuestionsCount(
                                                                            sectionIndex,
                                                                        ),
                                                                    0,
                                                                )
                                                            "
                                                            :disabled="
                                                                isLoading
                                                            "
                                                            class="mr-2"
                                                        />
                                                        ❌ Fail
                                                    </label>
                                                    <label
                                                        class="flex items-center"
                                                    >
                                                        <input
                                                            type="radio"
                                                            :name="`status-${question.id}`"
                                                            :value="2"
                                                            :checked="
                                                                getAnswerForQuestion(
                                                                    questionIndex +
                                                                        getPreviousQuestionsCount(
                                                                            sectionIndex,
                                                                        ),
                                                                )?.answer === 2
                                                            "
                                                            @change="
                                                                handleRadioChange(
                                                                    questionIndex +
                                                                        getPreviousQuestionsCount(
                                                                            sectionIndex,
                                                                        ),
                                                                    2,
                                                                )
                                                            "
                                                            :disabled="
                                                                isLoading
                                                            "
                                                            class="mr-2"
                                                        />
                                                        ⚪ N/A
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- REMARKS if failed -->
                                            <div
                                                v-if="
                                                    getAnswerForQuestion(
                                                        questionIndex +
                                                            getPreviousQuestionsCount(
                                                                sectionIndex,
                                                            ),
                                                    )?.answer === 0
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
                                                        getAnswerForQuestion(
                                                            questionIndex +
                                                                getPreviousQuestionsCount(
                                                                    sectionIndex,
                                                                ),
                                                        )?.remarks
                                                    "
                                                    @input="
                                                        handleInputChange(
                                                            questionIndex +
                                                                getPreviousQuestionsCount(
                                                                    sectionIndex,
                                                                ),
                                                            $event,
                                                        )
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
                                                    getAnswerForQuestion(
                                                        questionIndex +
                                                            getPreviousQuestionsCount(
                                                                sectionIndex,
                                                            ),
                                                    )?.answer === 0
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
                                                        @click="
                                                            openCameraModal(
                                                                questionIndex +
                                                                    getPreviousQuestionsCount(
                                                                        sectionIndex,
                                                                    ),
                                                            )
                                                        "
                                                        :disabled="isLoading"
                                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                                    >
                                                        Take Photo
                                                    </button>

                                                    <div class="relative">
                                                        <input
                                                            :id="`photo-${question.id}`"
                                                            :data-question-index="
                                                                questionIndex +
                                                                getPreviousQuestionsCount(
                                                                    sectionIndex,
                                                                )
                                                            "
                                                            type="file"
                                                            @change="
                                                                handleFileUpload(
                                                                    questionIndex +
                                                                        getPreviousQuestionsCount(
                                                                            sectionIndex,
                                                                        ),
                                                                    $event,
                                                                )
                                                            "
                                                            accept="image/*"
                                                            :disabled="
                                                                isLoading
                                                            "
                                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                                        />
                                                        <button
                                                            type="button"
                                                            :disabled="
                                                                isLoading
                                                            "
                                                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                                        >
                                                            Upload from Gallery
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Selected file display -->
                                                <div
                                                    v-if="
                                                        getAnswerForQuestion(
                                                            questionIndex +
                                                                getPreviousQuestionsCount(
                                                                    sectionIndex,
                                                                ),
                                                        )?.photo
                                                    "
                                                    class="text-sm text-gray-600 mb-2"
                                                >
                                                    Selected:
                                                    {{
                                                        getAnswerForQuestion(
                                                            questionIndex +
                                                                getPreviousQuestionsCount(
                                                                    sectionIndex,
                                                                ),
                                                        )!.photo!.name
                                                    }}
                                                </div>

                                                <p
                                                    class="text-xs text-gray-500"
                                                >
                                                    Take a photo with your
                                                    camera or upload from
                                                    gallery. Max size: 5MB
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
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
