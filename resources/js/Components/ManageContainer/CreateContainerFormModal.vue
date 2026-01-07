<script setup lang="ts">
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/zod";
import * as z from "zod";
import { Button } from "@/components/ui/button";
import {
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from "@/components/ui/form";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { ref, watch, computed } from "vue";
import axios from "axios";

const transportType = ref<"Truck" | "Container" | "">("");
const isLoading = ref(false);
const errorMessage = ref("");
const successMessage = ref("");
const countryRequirements = ref(null);

const requiresSeals = computed(() => {
    return countryRequirements.value?.requires_seals === true;
});

const formSchema = toTypedSchema(
    z.object({
        transport_type: z.enum(["Truck", "Container"], {
            required_error: "Transport Type is required",
        }),
        transport_number: z.string().min(1, "Transport Number is required"),
        sku_number: z.string().min(1, "SKU Number is required"),
        model_project: z.string().min(1, "Model/Project is required"),
        forwarder: z.string().min(1, "Forwarder is required"),
        country: z.string().min(1, "Country is required"),
        work_order: z.string().min(1, "Work Order is required"),
        hauler: z.string().min(1, "Hauler is required"),
        high_security_seal: z.string().optional(),
        gps: z.string().optional(),
        fork_seal: z.string().optional(),
        temporary_seal: z.string().optional(),
    })
);

const form = useForm({
    validationSchema: formSchema,
});

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits(["close", "save"]);

watch(transportType, (val) => {
    if (val === "Truck") {
        form.setFieldValue("high_security_seal", "");
        form.setFieldValue("gps", "");
        form.setFieldValue("fork_seal", "");
        form.setFieldValue("temporary_seal", "");
    }
});

// Watch for country changes to fetch requirements
watch(() => form.values.country, async (newCountry) => {
    if (newCountry && newCountry.trim()) {
        try {
            const response = await axios.get(`/containers/country-requirements?country=${encodeURIComponent(newCountry)}`);
            countryRequirements.value = response.data.data;
        } catch (error) {
            console.error('Failed to fetch country requirements:', error);
            countryRequirements.value = null;
        }
    } else {
        countryRequirements.value = null;
    }
});

// Clear messages when modal is closed or reopened
watch(() => props.show, (newVal) => {
    if (!newVal) {
        errorMessage.value = "";
        successMessage.value = "";
        isLoading.value = false;
        countryRequirements.value = null;
        transportType.value = "";
    }
});

// Destructure resetForm from form context
const { handleSubmit, resetForm } = form;

// Custom reset function to reset both form and transport type
const resetFormWithTransportType = () => {
    resetForm();
    transportType.value = "";
    countryRequirements.value = null;
};

const onSubmit = handleSubmit(async (values) => {
    // Clear previous messages
    errorMessage.value = "";
    successMessage.value = "";

    isLoading.value = true;

    console.log("Form Values:", values);
    try {
        const response = await axios.post("/containers/create", values);
        const data = response.data;
        console.log("Success:", data);

        successMessage.value = "Container created successfully!";

        // Auto-close after success
        setTimeout(() => {
            emit("close");
            resetFormWithTransportType();
        }, 2000);

    } catch (error: any) {
        console.error("Request failed:", error);
        if (error.response?.data?.message) {
            errorMessage.value = error.response.data.message;
        } else {
            errorMessage.value = "Failed to create container. Please try again.";
        }
    } finally {
        isLoading.value = false;
    }
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
                        class="bg-white p-6 rounded-lg shadow-[0_4px_20px_rgba(255,255,255,0.6)] w-[80%] max-w-2xl max-h-[80vh] overflow-y-auto"
                    >
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold text-red-700">
                                Create New Shipment Container
                            </h2>

                            <button
                                @click="emit('close')"
                                class="text-gray-500 hover:text-gray-700 text-xl font-bold"
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

                        <form @submit.prevent="onSubmit">
                            <FormField
                                v-slot="{ componentField }"
                                name="transport_type"
                            >
                                <FormItem>
                                    <FormLabel>Transport Type <span class="text-red-500">*</span></FormLabel>
                                    <Select
                                        v-bind="componentField"
                                        v-model="transportType"
                                    >
                                        <SelectTrigger>
                                            <SelectValue
                                                placeholder="Select Transport Type"
                                            />
                                        </SelectTrigger>
                                        <SelectContent class="z-[10001]">
                                            <SelectGroup>
                                                <SelectItem value="Truck"
                                                    >Truck</SelectItem
                                                >
                                                <SelectItem value="Container"
                                                    >Container</SelectItem
                                                >
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-slot="{ componentField }"
                                name="country"
                            >
                                <FormItem>
                                    <FormLabel>Country <span class="text-red-500">*</span></FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-slot="{ componentField }"
                                name="transport_number"
                            >
                                <FormItem>
                                    <FormLabel>Transport Number <span class="text-red-500">*</span></FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-slot="{ componentField }"
                                name="sku_number"
                            >
                                <FormItem>
                                    <FormLabel>SKU Number <span class="text-red-500">*</span></FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-slot="{ componentField }"
                                name="model_project"
                            >
                                <FormItem>
                                    <FormLabel>Model Project <span class="text-red-500">*</span></FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-slot="{ componentField }"
                                name="forwarder"
                            >
                                <FormItem>
                                    <FormLabel>Forwarder <span class="text-red-500">*</span></FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-slot="{ componentField }"
                                name="hauler"
                            >
                                <FormItem>
                                    <FormLabel>Hauler <span class="text-red-500">*</span></FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-slot="{ componentField }"
                                name="work_order"
                            >
                                <FormItem>
                                    <FormLabel>Work Order <span class="text-red-500">*</span></FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-if="transportType === 'Container'"
                                v-slot="{ componentField }"
                                name="high_security_seal"
                            >
                                <FormItem>
                                    <FormLabel>High Security Seal <span class="text-red-500">*</span></FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-if="transportType === 'Container' && requiresSeals"
                                v-slot="{ componentField }"
                                name="gps"
                            >
                                <FormItem>
                                    <FormLabel>GPS <span class="text-red-500">*</span></FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-if="transportType === 'Container' && requiresSeals"
                                v-slot="{ componentField }"
                                name="fork_seal"
                            >
                                <FormItem>
                                    <FormLabel>Fork Seal <span class="text-red-500">*</span></FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-if="transportType === 'Container'"
                                v-slot="{ componentField }"
                                name="temporary_seal"
                            >
                                <FormItem>
                                    <FormLabel>Temporary Seal</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <div
                                class="flex flex-row justify-between items-center"
                            >
                                <Button class="mt-4" type="submit" :disabled="isLoading">
                                    <span v-if="isLoading" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Creating...
                                    </span>
                                    <span v-else>Submit</span>
                                </Button>
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
</style>
