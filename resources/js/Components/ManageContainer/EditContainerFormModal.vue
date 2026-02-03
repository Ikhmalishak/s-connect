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

// Simple debounce utility
function debounce(func, delay) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(this, args), delay);
    };
}

const transportType = ref<"Truck" | "Container" | "">("");
const isLoading = ref(false);
const errorMessage = ref("");
const successMessage = ref("");
const countryRequirements = ref(null);
const sites = ref([]);
const countryValidationStatus = ref<"idle" | "loading" | "valid" | "invalid">("idle");

const requiresGPS = computed(() => {
    return countryRequirements.value?.requires_gps === true;
});

const forkSealSize = computed(() => {
    const requirements = countryRequirements.value;
    if (requirements?.requires_fork_seal && requirements?.strength) {
        return `${requirements.strength}mm`;
    }
    return '';
});

const formSchema = toTypedSchema(
    z.object({
        site_id: z.number({
            required_error: "Site is required",
        }),
        transport_type: z.enum(["Truck", "Container"], {
            required_error: "Transport Type is required",
        }),
        size: z.enum(["20GP", "40HC"], {
            required_error: "Container Size is required for containers",
        }).optional(),
        transport_number: z.string().min(1, "Transport Number is required"),
        sku_number: z.string().min(1, "SKU Number is required"),
        model_project: z.string().min(1, "Model/Project is required"),
        forwarder: z.string().min(1, "Forwarder is required"),
        country: z.string().min(1, "Country is required"),
        work_order: z.string().min(1, "Work Order is required"),
        hauler: z.string().min(1, "Hauler is required"),
        high_security_seal_sn: z.string().optional(),
        inside_gps_sn: z.string().optional(),
        outside_gps_sn: z.string().optional(),
        fork_seal_sn: z.string().optional(),
        fork_seal_size: z.string().optional(),
        temporary_seal_sn: z.string().optional(),
    }).refine((data) => {
        // If transport_type is Container, size is required
        if (data.transport_type === "Container") {
            return data.size !== undefined && data.size !== null;
        }
        return true;
    }, {
        message: "Container Size is required when Transport Type is Container",
        path: ["size"],
    })
);

const form = useForm({
    validationSchema: formSchema,
});

const props = defineProps<{
    show: boolean;
    containerId: number | null;
}>();

const emit = defineEmits(["close", "save"]);

watch(transportType, (val) => {
    if (val === "Truck") {
        form.setFieldValue("high_security_seal_sn", "");
        form.setFieldValue("inside_gps_sn", "");
        form.setFieldValue("outside_gps_sn", "");
        form.setFieldValue("fork_seal_sn", "");
        form.setFieldValue("fork_seal_size", "");
        form.setFieldValue("temporary_seal_sn", "");
    }
});

// Auto-populate fork seal size when country requirements change
watch(countryRequirements, (newRequirements) => {
    if (newRequirements?.requires_fork_seal && newRequirements?.strength) {
        form.setFieldValue("fork_seal_size", `${newRequirements.strength}mm`);
    } else {
        form.setFieldValue("fork_seal_size", "");
    }
});

// Debounced function to fetch country requirements
const debouncedFetchRequirements = debounce(async (newCountry) => {
    if (newCountry && newCountry.trim().length >= 2) {
        try {
            const response = await axios.get(`/containers/country-requirements?country=${encodeURIComponent(newCountry)}`);
            countryRequirements.value = response.data.data;
        } catch (error) {
            console.error('Failed to fetch country requirements:', error);
            // Don't clear requirements on error - keep previous valid data
        }
    } else if (!newCountry || !newCountry.trim()) {
        countryRequirements.value = null;
    }
}, 500);

// Watch for country changes to fetch requirements (debounced)
watch(() => form.values.country, debouncedFetchRequirements);

// Debounced function to validate country
const debouncedValidateCountry = debounce(async (country) => {
    // Only validate country for Container transport type
    if (transportType.value !== "Container") {
        countryValidationStatus.value = "idle";
        return;
    }

    if (!country || country.trim().length < 2) {
        countryValidationStatus.value = "idle";
        return;
    }

    countryValidationStatus.value = "loading";

    try {
        const response = await axios.get(`/containers/country-requirements?country=${encodeURIComponent(country.trim())}`);
        if (response.data.data) {
            countryValidationStatus.value = "valid";
        } else {
            countryValidationStatus.value = "invalid";
        }
    } catch (error) {
        countryValidationStatus.value = "invalid";
    }
}, 500);

// Watch for country changes to validate (debounced)
watch(() => form.values.country, (newCountry) => {
    debouncedValidateCountry(newCountry);
});

// Also watch for transport type changes to re-validate country
watch(transportType, (newTransportType) => {
    if (form.values.country) {
        debouncedValidateCountry(form.values.country);
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
    } else if (newVal) {
        // Modal is opening - fetch sites
        fetchSites();
        if (props.containerId) {
            // Modal is opening with a container ID - fetch data
            fetchContainerData();
        }
    }
});

// Watch for containerId changes (when switching between different containers)
watch(() => props.containerId, (newId) => {
    if (newId && props.show) {
        fetchContainerData();
    }
});

// Fetch sites for the dropdown
async function fetchSites() {
    try {
        const response = await axios.get('/api/sites');
        sites.value = response.data;
    } catch (error) {
        console.error('Failed to fetch sites:', error);
    }
}

// Fetch container data for editing
async function fetchContainerData() {
    if (!props.containerId) return;

    isLoading.value = true;
    errorMessage.value = "";
    successMessage.value = "";

    try {
        const response = await axios.get(`/containers/${props.containerId}`);
        const container = response.data;

        // Pre-fill form with container data
        form.setValues({
            site_id: container.site_id,
            transport_type: container.transport_type,
            size: container.size || undefined,
            transport_number: container.transport_number,
            sku_number: container.sku_number,
            model_project: container.model_project,
            forwarder: container.forwarder,
            country: container.country,
            work_order: container.work_order,
            hauler: container.hauler,
            high_security_seal_sn: container.high_security_seal_sn || "",
            inside_gps_sn: container.inside_gps_sn || "",
            outside_gps_sn: container.outside_gps_sn || "",
            fork_seal_sn: container.fork_seal_sn || "",
            fork_seal_size: container.fork_seal_size || "",
            temporary_seal_sn: container.temporary_seal_sn || "",
        });

        transportType.value = container.transport_type;

        // Fetch country requirements if country is set
        if (container.country) {
            debouncedFetchRequirements(container.country);
        }

    } catch (error: any) {
        console.error("Failed to fetch container data:", error);
        errorMessage.value = "Failed to load container data. Please try again.";
    } finally {
        isLoading.value = false;
    }
}

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
        let response;
        if (props.containerId) {
            // Update existing container
            response = await axios.put(`/containers/${props.containerId}`, values);
            successMessage.value = "Container updated successfully!";
        } else {
            // Create new container (fallback)
            response = await axios.post("/containers/create", values);
            successMessage.value = "Container created successfully!";
        }

        const data = response.data;
        console.log("Success:", data);

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
            errorMessage.value = props.containerId
                ? "Failed to update container. Please try again."
                : "Failed to create container. Please try again.";
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
                                Edit Shipment Container
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
                                name="site_id"
                            >
                                <FormItem>
                                    <FormLabel>Site <span class="text-red-500">*</span></FormLabel>
                                    <Select v-bind="componentField">
                                        <SelectTrigger>
                                            <SelectValue
                                                placeholder="Select Site"
                                            />
                                        </SelectTrigger>
                                        <SelectContent class="z-[10001]">
                                            <SelectGroup>
                                                <SelectItem
                                                    v-for="site in sites"
                                                    :key="site.id"
                                                    :value="site.id"
                                                >
                                                    {{ site.name }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

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
                                        <div class="relative">
                                            <Input
                                                type="text"
                                                v-bind="componentField"
                                                :class="{
                                                    'pr-10': countryValidationStatus !== 'idle'
                                                }"
                                            />
                                            <!-- Loading indicator -->
                                            <div
                                                v-if="countryValidationStatus === 'loading'"
                                                class="absolute right-3 top-1/2 transform -translate-y-1/2"
                                            >
                                                <svg class="animate-spin h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </div>
                                            <!-- Valid indicator -->
                                            <div
                                                v-else-if="countryValidationStatus === 'valid'"
                                                class="absolute right-3 top-1/2 transform -translate-y-1/2"
                                            >
                                                <svg class="h-4 w-4 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                            <!-- Invalid indicator -->
                                            <div
                                                v-else-if="countryValidationStatus === 'invalid'"
                                                class="absolute right-3 top-1/2 transform -translate-y-1/2"
                                            >
                                                <svg class="h-4 w-4 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </FormControl>
                                    <FormMessage />
                                    <!-- Validation status message -->
                                    <div v-if="countryValidationStatus === 'valid'" class="text-sm text-green-600 mt-1">
                                        ✓ Country found in shipping requirements
                                    </div>
                                    <div v-else-if="countryValidationStatus === 'invalid'" class="text-sm text-red-600 mt-1">
                                        ✗ Country not found in shipping requirements
                                    </div>
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
                                v-if="transportType === 'Container'"
                                v-slot="{ componentField }"
                                name="size"
                            >
                                <FormItem>
                                    <FormLabel>Container Size <span class="text-red-500">*</span></FormLabel>
                                    <Select v-bind="componentField">
                                        <SelectTrigger>
                                            <SelectValue
                                                placeholder="Select Container Size"
                                            />
                                        </SelectTrigger>
                                        <SelectContent class="z-[10001]">
                                            <SelectGroup>
                                                <SelectItem value="20GP"
                                                    >20GP</SelectItem
                                                >
                                                <SelectItem value="40HC"
                                                    >40HC</SelectItem
                                                >
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
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
                                name="high_security_seal_sn"
                            >
                                <FormItem>
                                    <FormLabel>High Security Seal Serial Number <span class="text-red-500">*</span></FormLabel>
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
                                v-if="transportType === 'Container' && requiresGPS"
                                v-slot="{ componentField }"
                                name="inside_gps_sn"
                            >
                                <FormItem>
                                    <FormLabel>Inside GPS Serial Number <span class="text-red-500">*</span></FormLabel>
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
                                v-if="transportType === 'Container' && requiresGPS"
                                v-slot="{ componentField }"
                                name="outside_gps_sn"
                            >
                                <FormItem>
                                    <FormLabel>Outside GPS Serial Number<span class="text-red-500">*</span></FormLabel>
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
                                v-if="transportType === 'Container' && countryRequirements?.requires_fork_seal"
                                v-slot="{ componentField }"
                                name="fork_seal_sn"
                            >
                                <FormItem>
                                    <FormLabel>Fork Seal Serial Number <span class="text-red-500">*</span></FormLabel>
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
                                v-if="transportType === 'Container' && countryRequirements?.requires_fork_seal"
                                v-slot="{ componentField }"
                                name="fork_seal_size"
                            >
                                <FormItem>
                                    <FormLabel>Fork Seal Size</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                            readonly
                                            placeholder="Auto-populated from country requirements"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-if="transportType === 'Container'"
                                v-slot="{ componentField }"
                                name="temporary_seal_sn"
                            >
                                <FormItem>
                                    <FormLabel>Temporary Seal Serial Number</FormLabel>
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
                                        {{ containerId ? 'Updating...' : 'Creating...' }}
                                    </span>
                                    <span v-else>{{ containerId ? 'Update' : 'Submit' }}</span>
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
