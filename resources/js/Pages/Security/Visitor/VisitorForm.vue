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

const page = usePage<PageProps>();
const site = computed(() => page.props.site);

interface Site {
  id: number;
  name: string;
  site_code: string;
}

interface PageProps extends InertiaPageProps {
  site: Site;
}

type FormField =
  | "site_id"
  | "vehicle_number"
  | "visitor_company"
  | "purpose"
  | "person_to_meet"
  | "remarks"
  | "video_watched"
  | "security_guidelines_confirmed"
  | "visitor_type"
  | `visitors[${number}].visitor_name`
  | `visitors[${number}].id_type`
  | `visitors[${number}].id_number`
  | `visitors[${number}].phone_number`
  | `visitors[${number}].pass_number`
  | `visitors[${number}].visitor_type`;

interface Visitor {
  visitor_name: string;
  id_type: string;
  id_number: string;
  phone_number: string;
  pass_number: string;
  visitor_type: string;
}

interface Values {
  site_id: number | "";
  visitors: Visitor[];
  vehicle_number: string;
  visitor_company: string;
  purpose: string;
  person_to_meet: string;
  remarks: string;
  video_watched: boolean;
  security_guidelines_confirmed: boolean;
  visitor_type: string;
}

// Form setup
const { handleSubmit, setFieldValue, values, errors, resetForm } = useForm<Values>({
  initialValues: {
    site_id: site.value?.id ?? "",
    visitors: [] as Visitor[],
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
const videoEnded = ref(false);
const securityGuidelinesConfirmed = ref(false);
const resetVideoTrigger = ref(false);
const resetReviewTrigger = ref(false);
const currentStep = ref(1);
const checkingAcknowledgement = ref(false);
const acknowledgementModalOpen = ref(false);
const acknowledgementMessage = ref("");
const isAcknowledged = ref(false);

const personalInfoStepRef = ref<{ isValid: () => boolean } | null>(null);
const visitDetailsStepRef = ref<{ isValid: () => boolean } | null>(null);

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

watch(visitorType, (newVal) => {
  setFieldValue("visitor_type", newVal);
});

function handleReset() {
  resetForm();
  paxCount.value = 1;
  paxModalOpen.value = true;
  paxInputValue.value = "1";
  currentStep.value = 1;
  videoEnded.value = false;
  securityGuidelinesConfirmed.value = false;
  resetVideoTrigger.value = true;
  visitorType.value = "";
  nextTick(() => {
    resetVideoTrigger.value = false;
  });
}

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

  const customValidationPassed = personalInfoStepRef.value?.isValid() ?? true;

  return basicValid && customValidationPassed;
});

const isStep2Valid = computed(() => {
  const basicValid =
    values.purpose?.trim() &&
    (values.purpose !== "Meeting" || values.person_to_meet?.trim());

  const customValidationPassed = visitDetailsStepRef.value?.isValid() ?? true;

  return basicValid && customValidationPassed;
});

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

      const allAcknowledged = results.every((res) => res.data.acknowledged);

      isAcknowledged.value = allAcknowledged;
      acknowledgementMessage.value = allAcknowledged
        ? "✅ You are already acknowledged. You can directly submit the form."
        : "⚠️ You need to watch the safety video and read the guidelines before you can submit.";
      acknowledgementModalOpen.value = true;

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

const handleVisitorUpdate = ({
  index,
  field,
  value,
}: {
  index: number;
  field: keyof Visitor;
  value: string;
}) => {
  setFieldValue(`visitors[${index}].${field}` as const, value as never);
};

const handleVisitUpdate = ({
  field,
  value,
}: {
  field: FormField;
  value: string;
}) => {
  setFieldValue(field, value);
};

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
  resetForm();
  currentStep.value = 1;
  videoEnded.value = false;
  securityGuidelinesConfirmed.value = false;
  resetVideoTrigger.value = true;
  await nextTick();
  resetVideoTrigger.value = false;
  resetReviewTrigger.value = true;
  await nextTick();
  resetReviewTrigger.value = false;
  paxModalOpen.value = true;
  visitorType.value = "";
}

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

const stepTitles = {
  1: "Visitor Details",
  2: "Visit Details",
  3: "Safety & Security",
  4: "Review & Submit",
};
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
      <div class="absolute -top-4 -right-2 z-10">
        <div
          class="w-10 h-10 bg-red-600 text-white rounded-full flex items-center justify-center shadow-lg border-4 border-white"
        >
          <span class="text-md font-bold text-center leading-tight px-1">
            {{ site.site_code || site.name }}
          </span>
        </div>
      </div>

      <div class="px-6 py-4 border-b">
        <div class="mb-1">
          <div class="flex items-center justify-between">
            <template v-for="step in 4" :key="step">
              <div class="flex flex-col items-center flex-shrink-0">
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
                <h3
                  class="text-xs sm:text-sm font-semibold text-center px-1 max-w-20 leading-tight"
                  :class="{
                    'opacity-50': step > currentStep,
                  }"
                >
                  {{ stepTitles[step] }}
                </h3>
              </div>
              <div
                v-if="step < 4"
                class="flex-1 h-0.5 mt-[-1rem]"
                :class="
                  step < currentStep ? 'bg-green-600' : 'bg-gray-200'
                "
              ></div>
            </template>
          </div>
        </div>
      </div>

      <form @submit="onSubmit" class="px-6 py-2 mb-2 space-y-6">
        <PersonalInfoStep
          ref="personalInfoStepRef"
          v-show="currentStep === 1"
          :visitors="values.visitors || []"
          :errors="errors || {}"
          @update="handleVisitorUpdate"
        />

        <VisitDetailsStep
          ref="visitDetailsStepRef"
          v-show="currentStep === 2"
          :values="values || {}"
          :errors="errors || {}"
          :purposes="purposes || []"
          @update="handleVisitUpdate"
        />

        <VideoDetailsStep
          v-show="currentStep === 3"
          :is-form-valid="isFormValid ?? false"
          :video-ended="videoEnded ?? false"
          :reset-video="resetVideoTrigger ?? false"
          @video-ended="handleVideoEnded"
        />

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