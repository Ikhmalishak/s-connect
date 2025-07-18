<script setup lang="ts">
import { ref, computed, watch, toRef } from "vue";
import { useForm, Form } from "vee-validate";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import Step1PersonalInfo from "@/Components/VisitorFormComponent/Step1PersonalInfo.vue";
import Step2VisitDetails from "@/Components/VisitorFormComponent/Step2VisitDetails.vue";
import Step3Video from "@/Components/VisitorFormComponent/Step3Video.vue";
import PaxModal from "@/Components/VisitorFormComponent/PaxModal.vue";
import VisitorFormHeader from "@/Components/VisitorFormHeader.vue";

// Form setup
const { handleSubmit, setFieldValue, values, errors } = useForm({
    initialValues: {
        visitors: [],
        vehicle_number: "",
        site: "",
        visitor_company_id: "",
        purpose: "",
        person_to_meet: "",
        visit_date: "",
        visit_time: "",
        remarks: "",
        video_watched: false,
        security_guidelines_confirmed: false,
    },
});

const visitors = toRef(values, "visitors");
const paxCount = ref(1);
const paxModalOpen = ref(true);
const paxInputValue = ref("1");

// Debug: Watch values changes
watch(
    values,
    (newValues) => {
        console.log("Form values changed:", newValues);
    },
    { deep: true }
);

// Confirm Pax Count
function confirmPaxCount() {
    const count = parseInt(paxInputValue.value, 10);
    if (isNaN(count) || count <= 0) {
        alert("Please enter a valid number greater than 0.");
        return;
    }
    paxCount.value = count;

    const visitorArray = Array.from({ length: count }, () => ({
        visitor_name: "",
        id_type: "IC",
        id_number: "",
        phone_number: "",
        pass_number: "",
    }));

    setFieldValue("visitors", visitorArray);
    console.log("Visitor array set:", visitorArray);

    paxModalOpen.value = false;
}

// Video state
const videoEnded = ref(false);
const securityGuidelinesConfirmed = ref(false);

// Step control
const currentStep = ref(1);

const isFormValid = computed(() => {
  // Step 1: Personal Info validation
  const step1Valid =
    values.visitors.length > 0 &&
    values.visitors.every(v =>
      !!v.visitor_name?.trim() &&
      !!v.id_type?.trim() &&
      !!v.id_number?.trim() &&
      !!v.phone_number?.trim()
    );

  // Step 2: Visit Details validation
  const step2Valid =
    !!values.site?.trim() &&
    !!values.visitor_company_id?.trim() && // <-- UNCOMMENT THIS LINE
    !!values.purpose?.trim() &&
    !!values.visit_date?.trim() &&
    !!values.visit_time?.trim() &&
    // Conditionally check 'person_to_meet' if purpose is 'Meeting'
    (values.purpose !== 'Meeting' || !!values.person_to_meet?.trim());

  // Return true only if both Step 1 and Step 2 are valid
  return step1Valid && step2Valid;
});


function nextStep() {
    console.log("Next step clicked, current values:", values);
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
    console.log("Previous step clicked, current values:", values);
    if (currentStep.value > 1) currentStep.value--;
}

function handleVideoEnded() {
    videoEnded.value = true;
    securityGuidelinesConfirmed.value = true;
    setFieldValue("video_watched", true);
    setFieldValue("security_guidelines_confirmed", true);
}

function handleConfirmRead() {
    securityGuidelinesConfirmed.value = true;
    setFieldValue("security_guidelines_confirmed", true);
}

const onSubmit = handleSubmit(async (formValues) => {
    console.log("Submitting:", formValues);
    if (!videoEnded.value) {
        alert("Please watch the security briefing video before submitting.");
        return;
    }
    try {
        console.log("Form submitted successfully:", formValues);
    } catch (error) {
        console.error("Submission error:", error);
    }
});

const stepTitles = {
    1: "Visitor Information",
    2: "Visit Details",
    3: "Security Briefing",
    4: "Review & Submit",
};
</script>

<template>
    <PaxModal :show="paxModalOpen" @close="() => {}">
        <div class="flex items-center justify-center">
            <div class="max-w-md w-full bg-white p-2 rounded">
                <h2 class="text-lg font-semibold mb-6 text-center mx-auto">
                    Number of Visitors
                </h2>
                <div class="flex justify-center items-center">
                    <input
                        v-model="paxInputValue"
                        type="number"
                        min="1"
                        max="5"
                        class="border p-2 rounded w-1/3 text-center text-2xl"
                        placeholder="Enter number of visitors"
                    />
                    <span class="ml-2 text-black text-2xl">Pax</span>
                </div>

                <div class="mt-4 text-right">
                    <Button @click="confirmPaxCount">Confirm</Button>
                </div>
            </div>
        </div>
    </PaxModal>

    <VisitorFormHeader title="Visitor Registration Form" />
{{ values }}
    <Card v-if="!paxModalOpen" class="mt-4 mx-auto max-w-3xl w-full shadow-2xl">
        <div class="px-6 py-4 border-b">
            <div class="flex items-center justify-between">
                <div
                    v-for="step in 4"
                    :key="step"
                    class="flex items-center"
                    :class="{ 'opacity-50': step > currentStep }"
                >
                    <div
                        class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium"
                        :class="
                            step <= currentStep
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-200 text-gray-600'
                        "
                    >
                        {{ step }}
                    </div>
                    <div
                        v-if="step < 4"
                        class="w-12 h-0.5 bg-gray-200 mx-2"
                    ></div>
                </div>
            </div>
            <div class="mt-2">
                <h3 class="text-lg font-semibold">
                    {{ stepTitles[currentStep] }}
                </h3>
            </div>
        </div>

        <form @submit="onSubmit" class="p-6 space-y-6">
            <Step1PersonalInfo
                v-show="currentStep === 1"
                :values="values"
                :errors="errors"
            />

            <Step2VisitDetails
                v-show="currentStep === 2"
                :values="values"
                :errors="errors"
            />

            <Step3Video
                v-show="currentStep === 3"
                :videoEnded="videoEnded"
                :isFormValid="isFormValid"
                @videoEnded="handleVideoEnded"
                @confirmRead="handleConfirmRead"
            />

            <div v-show="currentStep === 4">
                <h3 class="text-lg font-semibold mb-4">Review & Submit</h3>
                <pre class="bg-gray-100 p-4 rounded">{{ values }}</pre>
            </div>

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
                        :disabled="(currentStep === 3 && !videoEnded)"
                    >
                        {{

                                (currentStep === 3 && !videoEnded)
                                ? "Watch Video First"
                                : "Next"
                        }}
                    </Button>
                    <Button
                        v-if="currentStep === 4"
                        type="submit"
                        :disabled="!videoEnded || !securityGuidelinesConfirmed"
                    >
                        Submit Registration
                    </Button>
                </div>
            </div>
        </form>
    </Card>
</template>