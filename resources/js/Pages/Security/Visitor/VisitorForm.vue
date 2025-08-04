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
import LoadingOverlay from "@/Components/LoadingOverlay.vue";
import axios from "axios";

// Form setup
const { handleSubmit, setFieldValue, values, errors, resetForm } = useForm({
    initialValues: {
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

const visitorType = ref("visitor");
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

const purposes = ref([
    "Meeting",
    "Delivery",
    "Maintenance",
    "Inspection",
    "Training",
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
        );

    const step2Valid =
        !!values.visitor_company?.trim() &&
        !!values.purpose?.trim() &&
        (values.purpose !== "Meeting" || !!values.person_to_meet?.trim());

    return step1Valid && step2Valid;
});

// Step navigation with acknowledgement check
async function nextStep() {
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

            // Simulate at least 2 seconds delay for UX
            await new Promise((resolve) => setTimeout(resolve, 2000));

            // Check if **all visitors are acknowledged**
            const allAcknowledged = results.every(
                (res) => res.data.acknowledged
            );

            if (allAcknowledged) {
                setFieldValue("video_watched", true);
                setFieldValue("security_guidelines_confirmed", true);
                videoEnded.value = true;
                securityGuidelinesConfirmed.value = true;
                currentStep.value = 4;
                return;
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
    1: "Visitor Information",
    2: "Visit Details",
    3: "Security Briefing",
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

    <ResultModal :open="resultModalOpen" @update:open="handleModalClose" />
    <div class="relative container mx-auto px-4 py-8 max-w-6xl">
        <VisitorFormHeader title="Visitor Registration Form" />
        <Card class="relative z-0 mt-4 mx-auto max-w-3xl w-full shadow-2xl">
            <!-- Replace the step progress section in your template with this: -->
            <div class="px-6 py-4 border-b">
                <!-- Step circles and connecting lines -->
                <div class="flex items-center justify-between mb-4">
                    <template v-for="step in 4" :key="step">
                        <!-- Step circle -->
                        <div
                            class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium z-10 relative"
                            :class="
                                step <= currentStep
                                    ? 'bg-green-600 text-white'
                                    : 'bg-gray-200 text-gray-600'
                            "
                        >
                            {{ step }}
                        </div>

                        <!-- Connecting line (not for the last step) -->
                        <div
                            v-if="step < 4"
                            class="flex-1 h-0.5 mx-2"
                            :class="
                                step < currentStep
                                    ? 'bg-green-600'
                                    : 'bg-gray-200'
                            "
                        ></div>
                    </template>
                </div>

                <!-- Step titles -->
                <div class="flex justify-between">
                    <div
                        v-for="step in 4"
                        :key="step"
                        class="flex-1 text-center"
                        :class="{ 'opacity-50': step > currentStep }"
                    >
                        <h3 class="text-xs sm:text-sm font-semibold px-1">
                            {{ stepTitles[step] }}
                        </h3>
                    </div>
                </div>
            </div>

            <form @submit="onSubmit" class="p-6 space-y-6">
                <!-- Step 1 -->
                <PersonalInfoStep
                    v-show="currentStep === 1"
                    :visitors="values.visitors || []"
                    :errors="errors || {}"
                />

                <!-- Step 2 -->
                <VisitDetailsStep
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
                        <Button
                            v-if="currentStep < 4"
                            type="button"
                            @click="nextStep"
                            :disabled="
                                checkingAcknowledgement ||
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
