<script setup lang="ts">
import { ref, computed } from "vue";
import { useForm, useFieldArray } from "vee-validate";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import Step1PersonalInfo from "@/Components/VisitorFormComponent/Step1PersonalInfo.vue";
import VisitorFormHeader from "@/Components/VisitorFormHeader.vue";
import Step2Pax from "@/Components/VisitorFormComponent/Step2Pax.vue";
import Step3VisitDetails from "@/Components/VisitorFormComponent/Step3VisitDetails.vue";
import Step4Video from "@/Components/VisitorFormComponent/Step4Video.vue";

// Basic form setup
const { handleSubmit, setFieldValue, values, meta, errors, resetForm } =
    useForm({
        initialValues: {
            visitor_name: "",
            id_type: "",
            id_number: "",
            phone_number: "",
            pass_number: "",
            pax: [],
            vehicle_number: "",
            site: "",
            visitor_company_id: "",
            purpose: "",
            person_to_meet: "",
            visit_date: "",
            visit_time: "",
            expected_duration: "",
            remarks: "",
            video_watched: false,
            security_guidelines_confirmed: false,
        },
    });
    
// Pax handling
const {
    fields: paxFields,
    push: addPax,
    remove: removePax,
} = useFieldArray("pax");

// Handle pax count changes from child
function handlePaxCountChange(newCount: number) {
    const current = paxFields.value.length;
    if (newCount > current) {
        for (let i = current; i < newCount; i++) {
            addPax({
                visitor_name: "",
                id_type: "IC",
                id_number: "",
                phone_number: "",
            });
        }
    } else if (newCount < current) {
        for (let i = current - 1; i >= newCount; i--) {
            removePax(i);
        }
    }
}

// Handle removing individual pax
function handleRemovePax(index: number) {
    removePax(index);
}

// Video state
const videoEnded = ref(false);
const securityGuidelinesConfirmed = ref(false);

// Step control
const currentStep = ref(1);

// Form validation for video step
const isFormValid = computed(() => {
    // Check if required fields from previous steps are filled
    const requiredFields = [
        'visitor_name',
        'id_type', 
        'id_number',
        'phone_number',
        'site',
        'visitor_company_id',
        'purpose',
        'visit_date',
        'visit_time'
    ];
    
    return requiredFields.every(field => values[field] && values[field].toString().trim() !== '');
});

// Navigation functions
function nextStep() {
    if (currentStep.value < 5) {
        // Special validation for step 4 (video)
        if (currentStep.value === 4 && !videoEnded.value) {
            alert('Please watch the security briefing video before proceeding.');
            return;
        }
        currentStep.value++;
    }
}

function prevStep() {
    if (currentStep.value > 1) currentStep.value--;
}

// Video event handlers
function handleVideoEnded() {
    videoEnded.value = true;
    securityGuidelinesConfirmed.value = true;
    setFieldValue('video_watched', true);
    setFieldValue('security_guidelines_confirmed', true);
}

function handleConfirmRead() {
    securityGuidelinesConfirmed.value = true;
    setFieldValue('security_guidelines_confirmed', true);
}

// Submit handler
const onSubmit = handleSubmit(async (formValues) => {
    console.log("Submitting:", formValues);
    
    // Validate video was watched
    if (!videoEnded.value) {
        alert('Please watch the security briefing video before submitting.');
        return;
    }
    
    // TODO: your axios.post here
    try {
        // const response = await axios.post('/api/visitor-registration', formValues);
        console.log('Form submitted successfully:', formValues);
        // Handle success (redirect, show success message, etc.)
    } catch (error) {
        console.error('Submission error:', error);
        // Handle error
    }
});

// Step titles for better UX
const stepTitles = {
    1: "Personal Information",
    2: "Accompanying Persons (Pax)",
    3: "Visit Details", 
    4: "Security Briefing",
    5: "Review & Submit"
};
</script>

<template>
    <VisitorFormHeader title="Visitor Registration Form" />
    <Card class="mt-4 mx-auto max-w-3xl w-full shadow-2xl shadow-opacity-30">
        <!-- Step Progress Indicator -->
        <div class="px-6 py-4 border-b">
            <div class="flex items-center justify-between">
                <div 
                    v-for="step in 5" 
                    :key="step"
                    class="flex items-center"
                    :class="{ 'opacity-50': step > currentStep }"
                >
                    <div 
                        class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium"
                        :class="step <= currentStep ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600'"
                    >
                        {{ step }}
                    </div>
                    <div v-if="step < 5" class="w-12 h-0.5 bg-gray-200 mx-2"></div>
                </div>
            </div>
            <div class="mt-2">
                <h3 class="text-lg font-semibold">{{ stepTitles[currentStep] }}</h3>
            </div>
        </div>

        <form @submit="onSubmit" class="p-6 space-y-6">
            <!-- Steps -->
            <Step1PersonalInfo
                v-if="currentStep === 1"
                :values="values"
                :errors="errors"
            />
            
            <Step2Pax 
                v-if="currentStep === 2" 
                :paxFields="paxFields"
                :paxCount="paxFields.length"
                @update:paxCount="handlePaxCountChange"
                @removePax="handleRemovePax"
            />
            
            <Step3VisitDetails
                v-if="currentStep === 3"
                :values="values"
                :errors="errors"
            />
            
            <Step4Video
                v-if="currentStep === 4"
                :videoEnded="videoEnded"
                :isFormValid="isFormValid"
                @videoEnded="handleVideoEnded"
                @confirmRead="handleConfirmRead"
            />
            
            <Step5ReviewSubmit 
                v-if="currentStep === 5" 
                :values="values"
                :paxFields="paxFields"
                :videoWatched="videoEnded"
                :securityConfirmed="securityGuidelinesConfirmed"
            />

            <!-- Navigation -->
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
                        v-if="currentStep < 5" 
                        type="button" 
                        @click="nextStep"
                        :disabled="currentStep === 4 && !videoEnded"
                    >
                        {{ currentStep === 4 && !videoEnded ? 'Watch Video First' : 'Next' }}
                    </Button>
                    
                    <Button 
                        v-if="currentStep === 5" 
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