<script setup lang="ts">
import { ref, computed, watch, toRef } from "vue";
import { useForm } from "vee-validate";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import PaxModal from "./PaxModal.vue";
import PersonalInfoStep from "./PersonalInfoStep.vue";
import VisitDetailsStep from "./VisitDetailsStep.vue";
import VideoDetailsStep from "./VideoDetailsStep.vue";
import ReviewStep from "./ReviewStep.vue";
import VisitorFormHeader from "@/Components/VisitorFormHeader.vue";
import ResultModal from "./ResultModal.vue";
import axios from "axios";
import Step1PersonalInfo from "@/Components/VisitorFormComponent/Step1PersonalInfo.vue";

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
    },
});

const paxCount = ref(1);
const paxModalOpen = ref(true);
const paxInputValue = ref("1");
const resultModalOpen = ref(false);

// Video state
const videoEnded = ref(false);
const securityGuidelinesConfirmed = ref(false);

// Step control
const currentStep = ref(1);

// Mock data
// const sites = ref(["Site A", "Site B", "Site C", "Main Office", "Warehouse"]);
const visitorCompany = ref([
    { id: 1, name: "Company A" },
    { id: 2, name: "Company B" },
    { id: 3, name: "Company C" },
    { id: 4, name: "Contractor XYZ" },
    { id: 5, name: "Vendor ABC" },
]);
const purposes = ref([
    "Meeting",
    "Delivery",
    "Maintenance",
    "Inspection",
    "Training",
    "Other",
]);

// Debug: Watch values changes
watch(
    values,
    (newValues) => {
        console.log("Form values changed:", newValues);
    },
    { deep: true }
);

// Confirm Pax Count
function confirmPaxCount(count: number) {
    paxCount.value = count;
    const visitorArray = Array.from({ length: count }, () => ({
        visitor_name: "",
        id_type: "IC",
        id_number: "",
        phone_number: "",
        pass_number: "",
    }));
    setFieldValue("visitors", visitorArray);
    paxModalOpen.value = false;
}

// Form validation
const isFormValid = computed(() => {
    const step1Valid =
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

// Step navigation
function nextStep() {
    if (currentStep.value < 4) {
        if (currentStep.value === 3 && !videoEnded.value) {
            alert(
                "Please watch the security briefing video before proceeding."
            );
            return;
        }
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

function handleModalClose() {
    resultModalOpen.value = false;

    // Reset form values
    resetForm();

    // Reset steps and state
    currentStep.value = 1;
    videoEnded.value = false;
    securityGuidelinesConfirmed.value = false;

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
</script>

<template>
    <PaxModal
        v-model:open="paxModalOpen"
        v-model:pax-input="paxInputValue"
        @confirm="confirmPaxCount"
    />

    <ResultModal :open="resultModalOpen" @update:open="handleModalClose" />

    <div class="relative container mx-auto px-4 py-8 max-w-6xl">
        <VisitorFormHeader title="Visitor Registration Form" />
        {{ values }}
        <Card class="relative z-0 mt-4 mx-auto max-w-3xl w-full shadow-2xl">
            <div class="px-6 py-4 border-b">
                <div class="flex items-center justify-between">
                    <div
                        v-for="step in 4"
                        :key="step"
                        class="flex items-center"
                        :class="{ 'opacity-50': step > currentStep }"
                    >
                        <div
                            class="w-8 h-8 rounded-full flex items-center justify-center text- font-medium"
                            :class="
                                step <= currentStep
                                    ? 'bg-green-600 text-white'
                                    : 'bg-gray-200 text-gray-600'
                            "
                        >
                            {{ step }}
                        </div>
                        <div
                            v-if="step < 4"
                            :class="[
                                'ml-1 mr-1 w-48 h-0.5',
                                step < currentStep
                                    ? 'bg-green-600'
                                    : 'bg-gray-200',
                            ]"
                        ></div>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div
                        v-for="step in 4"
                        :key="step"
                        class="flex items-center"
                        :class="{ 'opacity-50': step > currentStep }"
                    >
                        <div class="mt-2">
                            <h3 class="text-sm font-semibold">
                                {{ stepTitles[step] }}
                            </h3>
                        </div>
                    </div>
                </div>
            </div>

            <form @submit="onSubmit" class="p-6 space-y-6">
                <!-- Step 1 -->
                <Step1PersonalInfo
                    v-show="currentStep === 1"
                    :visitors="values.visitors"
                    :errors="errors"
                />

                <!-- Step 2 -->
                <VisitDetailsStep
                    v-show="currentStep === 2"
                    :values="values"
                    :errors="errors"
                    :visitor-company="visitorCompany"
                    :purposes="purposes"
                />

                <!-- Step 3 -->
                <VideoDetailsStep
                    v-show="currentStep === 3"
                    :is-form-valid="isFormValid"
                    :video-ended="videoEnded"
                    @video-ended="handleVideoEnded"
                />

                <!-- Step 4 -->
                <ReviewStep
                    v-show="currentStep === 4"
                    :values="values"
                    :video-ended="videoEnded"
                    :security-guidelines-confirmed="securityGuidelinesConfirmed"
                    @update:security-guidelines-confirmed="
                        handleGuidelinesConfirm
                    "
                />

                <!-- Navigation Buttons -->
                <div class="flex justify-between items-center pt-6 border-t">
                    <Button
                        type="button"
                        variant="outline"
                        @click="prevStep"
                        :disabled="currentStep === 1"
                    >
                        Back
                    </Button>
                    <div class="flex gap-2">
                        <Button
                            v-if="currentStep < 4"
                            type="button"
                            @click="nextStep"
                            :disabled="currentStep === 3 && !videoEnded"
                        >
                            {{
                                currentStep === 3 && !videoEnded
                                    ? "Watch Video First"
                                    : "Next"
                            }}
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
