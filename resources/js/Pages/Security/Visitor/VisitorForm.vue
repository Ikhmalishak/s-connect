<script setup lang="ts">
import { ref, computed, watch, toRef } from "vue";
import { useForm } from "vee-validate";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Play, CheckCircle, Clock } from "lucide-vue-next";
import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

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

// Mock data
const sites = ref(["Site A", "Site B", "Site C", "Main Office", "Warehouse"]);
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

// Video state
const videoRef = ref<HTMLVideoElement | null>(null);
const videoEnded = ref(false);
const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const hasStarted = ref(false);
const showConfirmation = ref(false);
const securityGuidelinesConfirmed = ref(false);
const hasScrolledToBottom = ref(false);
const securityGuidelinesSecondConfirmed = ref(false);

const handleScroll = (e: Event) => {
    const el = e.target as HTMLElement;
    if (el.scrollHeight - el.scrollTop <= el.clientHeight + 10) {
        hasScrolledToBottom.value = true;
        // Optionally auto-check the box
        securityGuidelinesSecondConfirmed.value = true;
    }
};

// Step control
const currentStep = ref(1);

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
    paxModalOpen.value = false;
}

// Video methods
const progress = computed(() => {
    return duration.value > 0 ? (currentTime.value / duration.value) * 100 : 0;
});

const formattedTime = computed(() => {
    const current = formatTime(currentTime.value);
    const total = formatTime(duration.value);
    return `${current} / ${total}`;
});

const canConfirm = computed(() => {
    return videoEnded.value && hasStarted.value;
});

function formatTime(seconds: number): string {
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, "0")}`;
}

function playVideo() {
    if (videoRef.value) {
        hasStarted.value = true;
        videoRef.value.play();
        isPlaying.value = true;
    }
}

function pauseVideo() {
    if (videoRef.value) {
        videoRef.value.pause();
        isPlaying.value = false;
    }
}

function handleVideoEnded() {
    isPlaying.value = false;
    showConfirmation.value = true;
    videoEnded.value = true;
    securityGuidelinesConfirmed.value = true;
    setFieldValue("video_watched", true);
    setFieldValue("security_guidelines_confirmed", true);
}

function handleTimeUpdate() {
    if (videoRef.value) {
        currentTime.value = videoRef.value.currentTime;
    }
}

function handleLoadedMetadata() {
    if (videoRef.value) {
        duration.value = videoRef.value.duration;
    }
}

function handleConfirmRead() {
    securityGuidelinesConfirmed.value = true;
    setFieldValue("security_guidelines_confirmed", true);
}

// Form validation
const isFormValid = computed(() => {
    // Step 1: Personal Info validation
    const step1Valid =
        values.visitors.length > 0 &&
        values.visitors.every(
            (v) =>
                !!v.visitor_name?.trim() &&
                !!v.id_type?.trim() &&
                !!v.id_number?.trim() &&
                !!v.phone_number?.trim()
        );

    // Step 2: Visit Details validation
    const step2Valid =
        !!values.site?.trim() &&
        !!values.visitor_company_id?.trim() &&
        !!values.purpose?.trim() &&
        !!values.visit_date?.trim() &&
        !!values.visit_time?.trim() &&
        (values.purpose !== "Meeting" || !!values.person_to_meet?.trim());

    return step1Valid && step2Valid;
});

// Step navigation
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

const onSubmit = handleSubmit(async (formValues) => {
    console.log("Submitting:", formValues);
    if (!videoEnded.value) {
        alert("Please watch the security briefing video before submitting.");
        return;
    }
    try {
        console.log("Form submitted successfully:", formValues);
        // Add your submission logic here
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
    <!-- Pax Modal -->
    <div
        v-if="paxModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    >
        <div class="max-w-md w-full bg-white p-6 rounded-xl shadow-xl">
            <h2 class="text-xl font-semibold mb-6 text-center">
                Number of Visitors
            </h2>

            <div class="flex justify-center items-center gap-3">
                <input
                    v-model="paxInputValue"
                    type="number"
                    min="1"
                    max="5"
                    class="border border-gray-300 p-3 rounded-md w-24 text-center text-2xl"
                    placeholder="1-5"
                />
                <span class="text-black text-2xl font-medium">Pax</span>
            </div>

            <div class="mt-6 flex justify-end">
                <Button @click="confirmPaxCount">Confirm</Button>
            </div>
        </div>
    </div>

    <!-- Main Form -->
    <div class="relative container mx-auto px-4 py-8 max-w-6xl">
        <h1 class="text-2xl font-bold text-center mb-8">
            Visitor Registration Form
        </h1>

        <!-- Disabled overlay when modal is open -->
        <div
            v-if="paxModalOpen"
            class="absolute inset-0 bg-white bg-opacity-70 z-10 pointer-events-none"
        ></div>

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
                <!-- Step 1: Personal Info -->
                <div
                    v-show="currentStep === 1"
                    class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full"
                >
                    <div
                        v-for="(visitor, i) in values.visitors"
                        :key="i"
                        class="border p-4 rounded-md bg-gray-50"
                    >
                        <h3 class="font-semibold text-gray-700 mb-2">
                            Visitor #{{ i + 1 }}
                        </h3>

                        <FormField
                            :name="`visitors[${i}].visitor_name`"
                            v-slot="{ componentField }"
                        >
                            <FormItem>
                                <FormLabel
                                    >Visitor Name
                                    <span class="text-red-500"
                                        >*</span
                                    ></FormLabel
                                >
                                <FormControl
                                    ><Input v-bind="componentField"
                                /></FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>

                        <FormField
                            :name="`visitors[${i}].id_type`"
                            v-slot="{ componentField }"
                        >
                            <FormItem>
                                <FormLabel
                                    >ID Type
                                    <span class="text-red-500"
                                        >*</span
                                    ></FormLabel
                                >
                                <Select v-bind="componentField">
                                    <FormControl>
                                        <SelectTrigger
                                            ><SelectValue
                                                placeholder="Select ID Type"
                                        /></SelectTrigger>
                                    </FormControl>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem value="IC"
                                                >Identification Card</SelectItem
                                            >
                                            <SelectItem value="Passport"
                                                >Passport</SelectItem
                                            >
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <FormMessage />
                            </FormItem>
                        </FormField>

                        <FormField
                            :name="`visitors[${i}].id_number`"
                            v-slot="{ componentField }"
                        >
                            <FormItem>
                                <FormLabel
                                    >ID Number
                                    <span class="text-red-500"
                                        >*</span
                                    ></FormLabel
                                >
                                <FormControl
                                    ><Input v-bind="componentField"
                                /></FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>

                        <FormField
                            :name="`visitors[${i}].phone_number`"
                            v-slot="{ componentField }"
                        >
                            <FormItem>
                                <FormLabel
                                    >Phone Number
                                    <span class="text-red-500"
                                        >*</span
                                    ></FormLabel
                                >
                                <FormControl
                                    ><Input v-bind="componentField"
                                /></FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>
                    </div>
                </div>

                <!-- Step 2: Visit Details -->
                <div v-show="currentStep === 2" class="space-y-6">
                    <h2 class="text-xl font-semibold">Step 2: Visit Details</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <FormField
                            v-slot="{ componentField }"
                            name="vehicle_number"
                        >
                            <FormItem>
                                <FormLabel>Vehicle Number</FormLabel>
                                <FormControl>
                                    <Input
                                        type="text"
                                        v-bind="componentField"
                                        placeholder="Enter vehicle number"
                                    />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>

                        <FormField v-slot="{ componentField }" name="site">
                            <FormItem>
                                <FormLabel
                                    >Site
                                    <span class="text-red-500"
                                        >*</span
                                    ></FormLabel
                                >
                                <Select v-bind="componentField">
                                    <FormControl>
                                        <SelectTrigger>
                                            <SelectValue
                                                placeholder="Select Site"
                                            />
                                        </SelectTrigger>
                                    </FormControl>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem
                                                v-for="s in sites"
                                                :key="s"
                                                :value="s"
                                            >
                                                {{ s }}
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <FormMessage />
                            </FormItem>
                        </FormField>

                        <FormField
                            v-slot="{ componentField }"
                            name="visitor_company_id"
                        >
                            <FormItem>
                                <FormLabel
                                    >Visitor Company
                                    <span class="text-red-500"
                                        >*</span
                                    ></FormLabel
                                >
                                <Select v-bind="componentField">
                                    <FormControl>
                                        <SelectTrigger>
                                            <SelectValue
                                                placeholder="Select Company"
                                            />
                                        </SelectTrigger>
                                    </FormControl>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem
                                                v-for="company in visitorCompany"
                                                :key="company.id"
                                                :value="company.id.toString()"
                                            >
                                                {{ company.name }}
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <FormMessage />
                            </FormItem>
                        </FormField>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <FormField v-slot="{ componentField }" name="purpose">
                            <FormItem>
                                <FormLabel
                                    >Purpose
                                    <span class="text-red-500"
                                        >*</span
                                    ></FormLabel
                                >
                                <Select v-bind="componentField">
                                    <FormControl>
                                        <SelectTrigger>
                                            <SelectValue
                                                placeholder="Select Purpose"
                                            />
                                        </SelectTrigger>
                                    </FormControl>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem
                                                v-for="p in purposes"
                                                :key="p"
                                                :value="p"
                                            >
                                                {{ p }}
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <FormMessage />
                            </FormItem>
                        </FormField>

                        <FormField
                            v-if="values.purpose === 'Meeting'"
                            v-slot="{ componentField }"
                            name="person_to_meet"
                        >
                            <FormItem>
                                <FormLabel>Person to Meet</FormLabel>
                                <FormControl>
                                    <Input
                                        type="text"
                                        v-bind="componentField"
                                        placeholder="Enter person's name"
                                    />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>

                        <FormField
                            v-slot="{ componentField }"
                            name="visit_date"
                        >
                            <FormItem>
                                <FormLabel
                                    >Visit Date
                                    <span class="text-red-500"
                                        >*</span
                                    ></FormLabel
                                >
                                <FormControl>
                                    <Input
                                        type="date"
                                        v-bind="componentField"
                                    />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>

                        <FormField
                            v-slot="{ componentField }"
                            name="visit_time"
                        >
                            <FormItem>
                                <FormLabel
                                    >Visit Time
                                    <span class="text-red-500"
                                        >*</span
                                    ></FormLabel
                                >
                                <FormControl>
                                    <Input
                                        type="time"
                                        v-bind="componentField"
                                    />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>
                    </div>

                    <FormField v-slot="{ componentField }" name="remarks">
                        <FormItem>
                            <FormLabel>Remarks</FormLabel>
                            <FormControl>
                                <Textarea
                                    v-bind="componentField"
                                    class="h-[100px]"
                                    placeholder="Additional notes or special requirements..."
                                />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>
                </div>

                <!-- Step 3: Video -->
                <div v-show="currentStep === 3" class="space-y-6">
                    <div class="text-center">
                        <h2 class="text-2xl font-bold mb-2">
                            Security Briefing
                        </h2>
                        <p class="text-gray-600">
                            Please watch the security briefing video before
                            proceeding
                        </p>
                    </div>

                    <!-- Form Validation Message -->
                    <div
                        v-if="!isFormValid"
                        class="bg-amber-50 border border-amber-200 rounded-lg p-4 flex items-center gap-2"
                    >
                        <Clock class="h-5 w-5 text-amber-600" />
                        <span class="text-amber-800">
                            Please complete all required fields in the previous
                            steps before watching the video
                        </span>
                    </div>

                    <!-- Video Section -->
                    <Card class="max-w-4xl mx-auto">
                        <div class="px-6 py-4 border-b">
                            <h3
                                class="text-lg font-semibold flex items-center gap-2"
                            >
                                <Play class="h-5 w-5" />
                                SKP Security Guidelines Video
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="relative">
                                <!-- Video Player -->
                                <div
                                    class="relative bg-black rounded-lg overflow-hidden"
                                >
                                    <video
                                        ref="videoRef"
                                        class="w-full h-auto"
                                        :class="{ 'opacity-50': !isFormValid }"
                                        controls
                                        :disabled="!isFormValid"
                                        preload="metadata"
                                        @play="isPlaying = true"
                                        @pause="isPlaying = false"
                                        @timeupdate="handleTimeUpdate"
                                        @loadedmetadata="handleLoadedMetadata"
                                        @ended="handleVideoEnded"
                                    >
                                        <source
                                            src="/assets/short.mp4"
                                            type="video/mp4"
                                        />
                                        Your browser does not support the video
                                        tag.
                                    </video>

                                    <!-- Overlay for disabled state -->
                                    <div
                                        v-if="!isFormValid"
                                        class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center"
                                    >
                                        <div class="text-center text-white">
                                            <Clock
                                                class="h-12 w-12 mx-auto mb-2"
                                            />
                                            <p class="text-lg font-semibold">
                                                Complete form first
                                            </p>
                                            <p class="text-sm">
                                                Fill in all required fields to
                                                unlock the video
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Video Controls Info -->
                                <div
                                    v-if="isFormValid && hasStarted"
                                    class="mt-4 space-y-2"
                                >
                                    <!-- Progress Bar -->
                                    <div
                                        class="w-full bg-gray-200 rounded-full h-2"
                                    >
                                        <div
                                            class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                            :style="`width: ${progress}%`"
                                        ></div>
                                    </div>

                                    <!-- Time Display -->
                                    <div
                                        class="flex justify-between items-center text-sm text-gray-600"
                                    >
                                        <span>{{ formattedTime }}</span>
                                        <span
                                            v-if="isPlaying"
                                            class="text-blue-600 font-medium"
                                            >Playing...</span
                                        >
                                        <span
                                            v-else-if="videoEnded"
                                            class="text-green-600 font-medium flex items-center gap-1"
                                        >
                                            <CheckCircle class="h-4 w-4" />
                                            Completed
                                        </span>
                                    </div>
                                </div>

                                <!-- Play Button for Initial State -->
                                <div
                                    v-if="isFormValid && !hasStarted"
                                    class="absolute inset-0 flex items-center justify-center"
                                >
                                    <Button
                                        @click="playVideo"
                                        size="lg"
                                        class="rounded-full w-20 h-20 bg-blue-600 hover:bg-blue-700"
                                    >
                                        <Play class="h-8 w-8 text-white" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <!-- Confirmation Section -->
                    <Card v-if="showConfirmation" class="max-w-2xl mx-auto">
                        <div class="p-6">
                            <div class="space-y-4">
                                <div
                                    class="flex items-center gap-2 text-green-600"
                                >
                                    <CheckCircle class="h-6 w-6" />
                                    <span class="font-semibold"
                                        >Video completed successfully!</span
                                    >
                                </div>
                            </div>
                        </div>
                    </Card>

                    <!-- Instructions -->
                    <div class="text-center space-y-2">
                        <div class="flex items-center justify-center gap-2">
                            <Play class="h-4 w-4 text-gray-600" />
                            <span class="text-sm text-gray-600">
                                Click the play button to start the security
                                briefing video
                            </span>
                        </div>

                        <p class="text-xs text-gray-500">
                            You must watch the complete video before proceeding
                            to the next step
                        </p>
                    </div>
                </div>

                <!-- Step 4: Review -->
                <div v-show="currentStep === 4">
                    <h3 class="text-lg font-semibold mb-4">Review & Submit</h3>
                    <div>
                        <!-- Guidelines Scroll Box -->
                        <div
                            class="h-64 overflow-y-auto border p-4 rounded mb-4"
                            @scroll="handleScroll"
                        >
                            <h3 class="text-lg font-semibold mb-2">
                                Security Guidelines
                            </h3>
                            <p class="mb-2">
                                1. Please wear your visitor tag at all times
                                while on premises.
                            </p>
                            <p class="mb-2">
                                2. Photography and video recording are strictly
                                prohibited unless authorized.
                            </p>
                            <p class="mb-2">
                                3. You must be escorted by an employee at all
                                times.
                            </p>
                            <p class="mb-2">
                                4. Emergency exits are marked clearly in all
                                buildings.
                            </p>
                            <p class="mb-2">
                                5. Your visit may be terminated if any guideline
                                is violated.
                            </p>
                            <!-- Add more lines to make scrollable -->
                            <p v-for="i in 20" :key="i" class="mb-2">
                                Additional policy content line {{ i }}.
                            </p>
                        </div>

                        <!-- Checkbox appears only after scroll -->
                        <div
                            v-if="hasScrolledToBottom"
                            class="flex items-center gap-2 mb-4"
                        >
                            <input
                                type="checkbox"
                                id="confirm"
                                v-model="securityGuidelinesConfirmed"
                                class="w-5 h-5"
                            />
                            <label for="confirm" class="text-sm">
                                I have read and understood the security
                                guidelines.
                            </label>
                        </div>
                    </div>
                </div>

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

<style scoped>
video::-webkit-media-controls {
    display: none !important;
}

video::-webkit-media-controls-enclosure {
    display: none !important;
}
</style>
