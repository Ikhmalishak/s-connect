<script setup lang="ts">
import { ref, computed, watch, nextTick } from "vue";
import { useForm } from "vee-validate";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import PaxModal from "../../../Components/VisitorFormComponent/PaxModal.vue";
import PersonalInfoStep from "../../../Components/VisitorFormComponent/PersonalInfoStep.vue";
import VisitDetailsStep from "../../../Components/VisitorFormComponent/VisitDetailsStep.vue";
import VideoDetailsStep from "../../../Components/VisitorFormComponent/VideoDetailsStep.vue";
import ReviewStep from "../../../Components/VisitorFormComponent/ReviewStep.vue";
import VisitorFormHeader from "@/Components/VisitorFormHeader.vue";
import ResultModal from "../../../Components/VisitorFormComponent/ResultModal.vue";
import AcknowledgementModal from "@/Components/AcknowledgementModal.vue";
import LoadingOverlay from "@/Components/LoadingOverlay.vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";
import { PageProps as InertiaPageProps } from "@inertiajs/core";

interface Site {
    id: number;
    name: string;
    site_code: string;
}

interface PageProps extends InertiaPageProps {
    site: Site;
}

const page = usePage<PageProps>();

const site = computed(() => page.props.site);

// Form setup
const { handleSubmit, setFieldValue, values, errors, resetForm } = useForm({
    initialValues: {
        site_id: site.value?.id ?? "",
        visitors: [],
        vehicle_number: "",
        visitor_company: "",
        purpose: "",
        person_to_meet: "",
        remarks: "",
        video_watched: false,
        security_guidelines_confirmed: false,
        visitor_type: "visitor",
    },
});

const visitorType = ref("");
const paxCount = ref(1);
const paxModalOpen = ref(true);
const paxInputValue = ref("1");
const resultModalOpen = ref(false);

// Video state
const videoEnded = ref(false);
const securityGuidelinesConfirmed = ref(false);
const resetVideoTrigger = ref(false);
const resetReviewTrigger = ref(false);

// Step control
const currentStep = ref(1);

// Loading state for acknowledgement check
const checkingAcknowledgement = ref(false);
const acknowledgementModalOpen = ref(false);
const acknowledgementMessage = ref("");
const isAcknowledged = ref(false);

//form validation
const personalInfoStepRef = ref(null);
const visitDetailsStepRef = ref(null);

const purposes = ref([
    "Meeting",
    "Delivery",
    "Maintenance",
    "Inspection",
    "Training",
    "Shipment",
    "Receiving",
    "Other",
]);

// Watch visitor type
watch(visitorType, (newVal) => {
    setFieldValue("visitor_type", newVal);
});

function handleReset() {
    // Reset vee-validate form fields
    resetForm();

    // Reset your custom states
    paxCount.value = 1;
    paxModalOpen.value = true;
    paxInputValue.value = "1";
    currentStep.value = 1;
    videoEnded.value = false;
    securityGuidelinesConfirmed.value = false;
    resetVideoTrigger.value = true;

    // Optional delay to re-trigger video reset
    nextTick(() => {
        resetVideoTrigger.value = false;
    });
}

// Confirm Pax Count
function confirmPaxCount(count: number) {
    paxCount.value = count;
    const visitorArray = Array.from({ length: count }, () => ({
        visitor_name: "",
        id_type: "IC",
        id_number: "",
        phone_number: "",
        pass_number: "",
        visitor_type: visitorType.value,
    }));
    setFieldValue("visitors", visitorArray);
    paxModalOpen.value = false;
}

// Form validation
const isFormValid = computed(() => {
    const step1Valid =
        values.visitors &&
        values.visitors.length > 0 &&
        values.visitors.every(
            (v) =>
                !!v.visitor_name?.trim() &&
                !!v.id_type?.trim() &&
                !!v.id_number?.trim() &&
                !!v.phone_number?.trim()
        ) &&
        (personalInfoStepRef.value?.isValid() ?? true);

    const step2Valid =
        values.purpose?.trim() &&
        (values.purpose !== "Meeting" || values.person_to_meet?.trim()) &&
        (visitDetailsStepRef.value?.isValid() ?? true);

    return step1Valid && step2Valid;
});

const isStep1Valid = computed(() => {
    const basicValid =
        values.visitors &&
        values.visitors.length > 0 &&
        values.visitors.every(
            (v) =>
                v.visitor_name?.trim() &&
                v.id_type?.trim() &&
                v.id_number?.trim() &&
                v.phone_number?.trim()
        );

    // Check custom validation from child component
    const customValidationPassed = personalInfoStepRef.value?.isValid() ?? true;

    return basicValid && customValidationPassed;
});

const isStep2Valid = computed(() => {
    const basicValid =
        values.purpose?.trim() &&
        (values.purpose !== "Meeting" || values.person_to_meet?.trim());

    // Check custom validation from child component
    const customValidationPassed = visitDetailsStepRef.value?.isValid() ?? true;

    return basicValid && customValidationPassed;
});

// Step navigation with acknowledgement check
async function nextStep() {
    if (currentStep.value === 1 && !isStep1Valid.value) {
        alert("Please fill all required visitor information.");
        return;
    }

    if (currentStep.value === 2 && !isStep2Valid.value) {
        alert("Please complete visit details before proceeding.");
        return;
    }

    if (currentStep.value === 2) {
        checkingAcknowledgement.value = true;

        try {
            const results = await Promise.all(
                values.visitors.map((visitor) =>
                    axios.post("/visitor/check-acknowledgement", {
                        id_type: visitor.id_type,
                        id_number: visitor.id_number,
                    })
                )
            );

            await new Promise((resolve) => setTimeout(resolve, 2000));

            const allAcknowledged = results.every(
                (res) => res.data.acknowledged
            );

            isAcknowledged.value = allAcknowledged;
            acknowledgementMessage.value = allAcknowledged
                ? "✅ You are already acknowledged. You can directly submit the form."
                : "⚠️ You need to watch the safety video and read the guidelines before you can submit.";
            acknowledgementModalOpen.value = true;

            // Auto-fill flags if acknowledged
            if (allAcknowledged) {
                setFieldValue("video_watched", true);
                setFieldValue("security_guidelines_confirmed", true);
                videoEnded.value = true;
                securityGuidelinesConfirmed.value = true;
            }
        } catch (error) {
            console.error("Acknowledgement check failed:", error);
        } finally {
            checkingAcknowledgement.value = false;
        }
    }

    if (currentStep.value === 3 && !videoEnded.value) {
        alert("Please watch the security briefing video before proceeding.");
        return;
    }

    if (currentStep.value < 4) {
        currentStep.value++;
    }
}

function prevStep() {
    if (currentStep.value > 1) currentStep.value--;
}

const onSubmit = handleSubmit(async (formValues) => {
    console.log("Submitting:", formValues);

    if (!videoEnded.value || !securityGuidelinesConfirmed.value) {
        alert("Please complete all security requirements before submitting.");
        return;
    }

    try {
        const response = await axios.post("/visitor/submit", formValues);
        console.log("Form submitted successfully:", response.data);
        resultModalOpen.value = true;
    } catch (error) {
        console.error("Submission error:", error);
        alert("An error occurred while submitting the form.");
    }
});

async function handleModalClose() {
    resultModalOpen.value = false;

    // Reset form values
    resetForm();

    // Reset steps and state
    currentStep.value = 1;
    videoEnded.value = false;
    securityGuidelinesConfirmed.value = false;

    // Trigger video reset
    resetVideoTrigger.value = true;
    await nextTick();
    resetVideoTrigger.value = false;

    // Trigger review step reset
    resetReviewTrigger.value = true;
    await nextTick();
    resetReviewTrigger.value = false;

    // Reopen pax modal if you want users to input number of visitors again
    paxModalOpen.value = true;
}

const stepTitles = {
    1: "Visitor Details",
    2: "Visit Details",
    3: "Safety & Security",
    4: "Review & Submit",
};

// Handle events from child components
function handleVideoEnded() {
    videoEnded.value = true;
    setFieldValue("video_watched", true);
}

function handleGuidelinesConfirm(confirmed: boolean) {
    securityGuidelinesConfirmed.value = confirmed;
    setFieldValue("security_guidelines_confirmed", confirmed);
}

watch(currentStep, async (newStep) => {
    if (newStep === 4) {
        resetReviewTrigger.value = true;
        await nextTick();
        resetReviewTrigger.value = false;
    }
});
</script>

<template>
    <LoadingOverlay
        :visible="checkingAcknowledgement"
        message="Verifying visitor details, please wait..."
    />

    <PaxModal
        v-model:open="paxModalOpen"
        v-model:pax-input="paxInputValue"
        v-model:visitor-type="visitorType"
        @confirm="confirmPaxCount"
    />

    <AcknowledgementModal
        :open="acknowledgementModalOpen"
        :message="acknowledgementMessage"
        :acknowledged="isAcknowledged"
        @close="acknowledgementModalOpen = false"
        @proceed="
            currentStep = 4;
            acknowledgementModalOpen = false;
        "
        @watch-video="
            currentStep = 3;
            acknowledgementModalOpen = false;
        "
    />

    <ResultModal :open="resultModalOpen" @update:open="handleModalClose" />
    <div class="relative container mx-auto px-4 py-8 max-w-6xl">
        <VisitorFormHeader title="Visitor Registration Form" />
        <Card class="relative z-0 mt-4 mx-auto max-w-3xl w-full shadow-2xl">
            <!-- Site name circle badge at top right -->
            <div class="absolute -top-4 -right-2 z-10">
                <div
                    class="w-10 h-10 bg-red-600 text-white rounded-full flex items-center justify-center shadow-lg border-4 border-white"
                >
                    <span
                        class="text-md font-bold text-center leading-tight px-1"
                    >
                        {{ site.site_code || site.name }}
                    </span>
                </div>
            </div>

            <div class="px-6 py-4 border-b">
                <!-- Step circles and connecting lines -->
                <div class="mb-1">
                    <div class="flex items-center justify-between">
                        <template v-for="step in 4" :key="step">
                            <!-- Step container -->
                            <div
                                class="flex flex-col items-center flex-shrink-0"
                            >
                                <!-- Step circle -->
                                <div
                                    class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium z-10 relative mb-2"
                                    :class="
                                        step <= currentStep
                                            ? 'bg-green-600 text-white'
                                            : 'bg-gray-200 text-gray-600'
                                    "
                                >
                                    {{ step }}
                                </div>

                                <!-- Step title -->
                                <h3
                                    class="text-xs sm:text-sm font-semibold text-center px-1 max-w-20 leading-tight"
                                    :class="{
                                        'opacity-50': step > currentStep,
                                    }"
                                >
                                    {{ stepTitles[step] }}
                                </h3>
                            </div>

                            <!-- Connecting line -->
                            <div
                                v-if="step < 4"
                                class="flex-1 h-0.5 mt-[-1rem]"
                                :class="
                                    step < currentStep
                                        ? 'bg-green-600'
                                        : 'bg-gray-200'
                                "
                            ></div>
                        </template>
                    </div>
                </div>
            </div>

            <form @submit="onSubmit" class="p-6 space-y-6">
                <!-- Step 1 -->
                <PersonalInfoStep
                    ref="personalInfoStepRef"
                    v-show="currentStep === 1"
                    :visitors="values.visitors || []"
                    :errors="errors || {}"
                />

                <!-- Step 2 -->
                <VisitDetailsStep
                    ref="visitDetailsStepRef"
                    v-show="currentStep === 2"
                    :values="values || {}"
                    :errors="errors || {}"
                    :purposes="purposes || []"
                />

                <!-- Step 3 -->
                <VideoDetailsStep
                    v-show="currentStep === 3"
                    :is-form-valid="isFormValid ?? false"
                    :video-ended="videoEnded ?? false"
                    :reset-video="resetVideoTrigger ?? false"
                    @video-ended="handleVideoEnded"
                />

                <!-- Step 4 -->
                <ReviewStep
                    v-show="currentStep === 4"
                    :values="values || {}"
                    :video-ended="videoEnded ?? false"
                    :security-guidelines-confirmed="
                        securityGuidelinesConfirmed ?? false
                    "
                    :reset-review="resetReviewTrigger ?? false"
                    @update:security-guidelines-confirmed="
                        handleGuidelinesConfirm
                    "
                    :visitor-type="visitorType || ''"
                />

                <!-- Navigation Buttons -->
                <div class="flex justify-between items-center pt-6 border-t">
                    <div class="flex gap-2">
                        <Button
                            v-if="currentStep === 1"
                            type="button"
                            variant="outline"
                            @click="handleReset"
                            :disabled="checkingAcknowledgement"
                        >
                            Reset
                        </Button>
                        <Button
                            v-if="currentStep != 1"
                            type="button"
                            variant="outline"
                            @click="prevStep"
                            :disabled="
                                currentStep === 1 || checkingAcknowledgement
                            "
                        >
                            Back
                        </Button>
                    </div>

                    <div class="flex gap-2">
                        <p
                            v-if="
                                (currentStep === 1 && !isStep1Valid) ||
                                (currentStep === 2 && !isStep2Valid)
                            "
                            class="text-red-500 text-sm mt-2 text-center"
                        >
                            ⚠️ Please fill in all required fields before
                            proceeding.
                        </p>

                        <Button
                            v-if="currentStep < 4"
                            type="button"
                            @click="nextStep"
                            :disabled="
                                checkingAcknowledgement ||
                                (currentStep === 1 && !isStep1Valid) ||
                                (currentStep === 2 && !isStep2Valid) ||
                                (currentStep === 3 && !videoEnded)
                            "
                        >
                            <span
                                v-if="checkingAcknowledgement"
                                class="flex items-center gap-2"
                            >
                                <svg
                                    class="animate-spin h-4 w-4 text-white"
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
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                    ></path>
                                </svg>
                                Checking Acknowledgement...
                            </span>

                            <span v-else>
                                {{
                                    currentStep === 3 && !videoEnded
                                        ? "Watch Video First"
                                        : "Next"
                                }}
                            </span>
                        </Button>
                        <Button
                            v-if="currentStep === 4"
                            type="submit"
                            :disabled="
                                !videoEnded || !securityGuidelinesConfirmed
                            "
                        >
                            Submit Registration
                        </Button>
                    </div>
                </div>
            </form>
        </Card>
    </div>
</template>
