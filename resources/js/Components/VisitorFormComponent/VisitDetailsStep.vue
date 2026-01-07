<script setup lang="ts">
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
import { ref, watch, onMounted } from "vue";
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const { t } = useI18n()

// Function to get the correct translation key for purposes
const getPurposeTranslation = (purpose: string): string => {
    const purposeMap: { [key: string]: string } = {
        "Meeting": "meeting",
        "Delivery": "delivery",
        "Service/Installation/Maintenance": "service",
        "Sorting/Rework": "sorting",
        "Interview": "interview",
        "Training": "training",
        "Shipping - Inbound": "shippingInbound",
        "Shipping - Outbound": "shippingOutbound",
        "Other": "other"
    };

    const key = purposeMap[purpose] || purpose.toLowerCase().replace(/\s+/g, '').replace('/', '').replace('-', '');
    return t(`visitor.visitDetails.purposes.${key}`);
};

interface FormValues {
    vehicle_number?: string;
    visitor_company?: string;
    purpose?: string;
    person_to_meet?: string;
    other_reasons?: string;
    remarks?: string;
    visitor_type?: string;
    shipment_transport_id?: string;
}

interface ShipmentTransport {
    id: number;
    transport_number: string;
    sku_number: string;
    model_project: string;
}

const props = defineProps<{
    values: FormValues;
    errors: any;
    purposes: string[];
    siteId?: number;
}>();

const emit = defineEmits(["update"]);

// Custom validation errors
const validationErrors = ref<{ [key: string]: string }>({});

// Shipment transports data
const shipmentTransports = ref<ShipmentTransport[]>([]);
const loadingShipments = ref(false);

// Fetch shipment transports for the site
const fetchShipmentTransports = async () => {
    if (!props.siteId) return;

    loadingShipments.value = true;
    try {
        const response = await axios.get(`/containers/for-visitor?site_id=${props.siteId}`);
        shipmentTransports.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to fetch shipment transports:', error);
        shipmentTransports.value = [];
    } finally {
        loadingShipments.value = false;
    }
};

// Fetch shipments when component mounts or siteId changes
onMounted(() => {
    if (props.siteId) {
        fetchShipmentTransports();
    }
});

watch(() => props.siteId, (newSiteId) => {
    if (newSiteId) {
        fetchShipmentTransports();
    }
});

// Validation functions
const validateVehicleNumber = (vehicleNumber: string): string => {
    if (!vehicleNumber) return "";

    const cleanNumber = vehicleNumber.replace(/\s+/g, "").toUpperCase();

    const patterns = [
        /^[A-Z]{1,3}\d{1,4}[A-Z]?$/,
        /^[A-Z]{2}\d{1,4}[A-Z]{1,2}$/,
        /^W[A-Z]{1,2}\d{1,4}[A-Z]?$/,
        /^[A-Z]\d{1,4}[A-Z]{2,3}$/,
    ];

    const isValidFormat = patterns.some((pattern) => pattern.test(cleanNumber));

    if (!isValidFormat) {
        return t('visitor.visitDetails.validation.vehicleInvalid');
    }

    if (cleanNumber.length < 4 || cleanNumber.length > 8) {
        return t('visitor.visitDetails.validation.vehicleLength');
    }

    return "";
};

const validateCompanyName = (company: string): string => {
    if (!company?.trim()) return " ";

    if (!/^[a-zA-Z0-9\s&.,()'-]+$/.test(company.trim())) {
        return t('visitor.visitDetails.validation.companyInvalid');
    }

    if (company.trim().length < 2) {
        return t('visitor.visitDetails.validation.companyTooShort');
    }

    if (company.trim().length > 100) {
        return t('visitor.visitDetails.validation.companyTooLong');
    }

    return "";
};

const validatePersonToMeet = (person: string, purpose: string): string => {
    if (purpose === "Meeting") {
        if (!person?.trim()) return t('visitor.visitDetails.validation.personRequired');

        if (!/^[a-zA-Z\s'-]+$/.test(person.trim())) {
            return t('visitor.visitDetails.validation.personInvalid');
        }

        if (person.trim().length < 2) {
            return t('visitor.visitDetails.validation.personTooShort');
        }

        if (person.trim().length > 100) {
            return t('visitor.visitDetails.validation.personTooLong');
        }
    }

    return "";
};

const validateOtherReason = (reason: string, purpose: string): string => {
    if (purpose === "Meeting") {
        if (!reason?.trim()) return t('visitor.visitDetails.validation.reasonRequired');

        if (reason.trim().length < 2) {
            return t('visitor.visitDetails.validation.reasonTooShort');
        }

        if (reason.trim().length > 100) {
            return t('visitor.visitDetails.validation.reasonTooLong');
        }
    }

    return "";
};


const validateContainerNumber = (containerNumber: string, visitorType: string): string => {
    if (visitorType === "shipping") {
        if (!containerNumber?.trim()) {
            return t('visitor.visitDetails.validation.containerNumberRequired');
        }

        // Basic container number validation (ISO 6346 format)
        const containerPattern = /^[A-Z]{4}\d{7}$/;
        if (!containerPattern.test(containerNumber.trim().toUpperCase())) {
            return t('visitor.visitDetails.validation.containerNumberInvalid');
        }
    }

    return "";
};

const validateRemarks = (remarks: string): string => {
    if (!remarks) return "";

    if (remarks.length > 500) {
        return t('visitor.visitDetails.validation.remarksTooLong');
    }

    const inappropriatePatterns = [
        /\b(fuck|shit|damn|bitch)\b/i,
        /<script|javascript:/i,
        /[<>{}]/g,
    ];

    if (inappropriatePatterns.some((pattern) => pattern.test(remarks))) {
        return t('visitor.visitDetails.validation.remarksInvalid');
    }

    return "";
};

const formatVehicleNumber = (value: string): string => {
    return value.toUpperCase().replace(/\s+/g, "").substring(0, 8);
};

const handleVehicleNumberInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const formatted = formatVehicleNumber(target.value);
    target.value = formatted;

    emit("update", {
        field: "vehicle_number",
        value: formatted,
    });

    const error = validateVehicleNumber(formatted);
    const errorKey = "vehicle_number";

    if (error) {
        validationErrors.value[errorKey] = error;
    } else {
        delete validationErrors.value[errorKey];
    }
};

const handleCompanyInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const value = target.value;

    emit("update", {
        field: "visitor_company",
        value,
    });

    const error = validateCompanyName(value);
    const errorKey = "visitor_company";

    if (error) {
        validationErrors.value[errorKey] = error;
    } else {
        delete validationErrors.value[errorKey];
    }
};

const handlePersonToMeetInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const value = target.value;

    emit("update", {
        field: "person_to_meet",
        value,
    });

    const error = validatePersonToMeet(value, props.values?.purpose);
    const errorKey = "person_to_meet";

    if (error) {
        validationErrors.value[errorKey] = error;
    } else {
        delete validationErrors.value[errorKey];
    }
};

const handleOtherReasonInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const value = target.value;

    emit("update", {
        field: "other_reasons",
        value,
    });

    const error = validateOtherReason(value, props.values?.purpose);
    const errorKey = "other_reasons";

    if (error) {
        validationErrors.value[errorKey] = error;
    } else {
        delete validationErrors.value[errorKey];
    }
};

const handleContainerNumberInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const value = target.value.toUpperCase();

    emit("update", {
        field: "container_number",
        value,
    });

    const error = validateContainerNumber(value, props.values?.visitor_type);
    const errorKey = "container_number";

    if (error) {
        validationErrors.value[errorKey] = error;
    } else {
        delete validationErrors.value[errorKey];
    }
};

const handleRemarksInput = (event: Event) => {
    const target = event.target as HTMLTextAreaElement;
    const value = target.value;

    emit("update", {
        field: "remarks",
        value,
    });

    const error = validateRemarks(value);
    const errorKey = "remarks";

    if (error) {
        validationErrors.value[errorKey] = error;
    } else {
        delete validationErrors.value[errorKey];
    }
};

watch(
    () => props.values?.purpose,
    (newPurpose) => {
        const { person_to_meet, other_reasons } = props.values || {};

        // Validate person_to_meet only if purpose is "Meeting"
        if (person_to_meet && newPurpose === "Meeting") {
            const error = validatePersonToMeet(person_to_meet, newPurpose);
            const errorKey = "person_to_meet";

            if (error) {
                validationErrors.value[errorKey] = error;
            } else {
                delete validationErrors.value[errorKey];
            }
        } else {
            delete validationErrors.value["person_to_meet"];
        }

        // Validate other_reasons only if purpose is "Others"
        if (newPurpose === "Others") {
            const error = validateOtherReason(other_reasons ?? "", newPurpose);
            const errorKey = "other_reasons";

            if (error) {
                validationErrors.value[errorKey] = error;
            } else {
                delete validationErrors.value[errorKey];
            }
        } else {
            delete validationErrors.value["other_reasons"];
        }
    }
);


defineExpose({
    validationErrors,
    isValid: () => {
        const hasErrors = Object.keys(validationErrors.value).length > 0;
        const companyRequired = !props.values?.visitor_company?.trim();
        const purposeRequired = !props.values?.purpose?.trim();
        const personToMeetRequired =
            props.values?.purpose === "Meeting" &&
            !props.values?.person_to_meet?.trim();

        return (
            !hasErrors &&
            !companyRequired &&
            !purposeRequired &&
            !personToMeetRequired
        );
    },
});
</script>
<template>
    <div class="space-y-6">
        <h2 class="text-xl font-semibold">{{ t('visitor.visitDetails.title') }}</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Vehicle Number Field -->
            <FormField v-slot="{ componentField }" name="vehicle_number">
                <FormItem>
                    <FormLabel>{{ t('visitor.visitDetails.vehicleNumber') }}</FormLabel>
                    <FormControl>
                        <Input
                            type="text"
                            v-bind="componentField"
                            :placeholder="t('visitor.visitDetails.vehiclePlaceholder')"
                            @input="handleVehicleNumberInput"
                            :class="
                                validationErrors['vehicle_number']
                                    ? 'border-red-500'
                                    : ''
                            "
                        />
                    </FormControl>
                    <div
                        v-if="validationErrors['vehicle_number']"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ validationErrors["vehicle_number"] }}
                    </div>
                    <FormMessage />
                </FormItem>
            </FormField>

            <!-- Company Name Field -->
            <FormField v-slot="{ componentField }" name="visitor_company">
                <FormItem>
                    <FormLabel>{{ t('visitor.visitDetails.visitorCompany') }}</FormLabel>
                    <FormControl>
                        <Input
                            type="text"
                            v-bind="componentField"
                            :placeholder="t('visitor.visitDetails.companyPlaceholder')"
                            @input="handleCompanyInput"
                            :class="
                                validationErrors['visitor_company']
                                    ? 'border-red-500'
                                    : ''
                            "
                        />
                    </FormControl>
                    <div
                        v-if="validationErrors['visitor_company']"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ validationErrors["visitor_company"] }}
                    </div>
                    <FormMessage />
                </FormItem>
            </FormField>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Purpose Field -->
            <FormField v-slot="{ componentField }" name="purpose">
                <FormItem>
                    <FormLabel>
                        {{ t('visitor.visitDetails.purpose') }} <span class="text-red-500">*</span>
                    </FormLabel>
                    <Select v-bind="componentField">
                        <FormControl>
                            <SelectTrigger>
                                <SelectValue :placeholder="t('visitor.visitDetails.purposePlaceholder')" />
                            </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem
                                    v-for="p in purposes"
                                    :key="p"
                                    :value="p"
                                >
                                    {{ getPurposeTranslation(p) }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FormMessage />
                </FormItem>
            </FormField>

            <!-- Person to Meet Field (conditional) -->
            <FormField
                v-if="values.purpose === 'Meeting'"
                v-slot="{ componentField }"
                name="person_to_meet"
            >
                <FormItem>
                    <FormLabel>
                        {{ t('visitor.visitDetails.personToMeet') }}
                        <span class="text-red-500">*</span>
                    </FormLabel>
                    <FormControl>
                        <Input
                            type="text"
                            v-bind="componentField"
                            :placeholder="t('visitor.visitDetails.personPlaceholder')"
                            @input="handlePersonToMeetInput"
                            :class="
                                validationErrors['person_to_meet']
                                    ? 'border-red-500'
                                    : ''
                            "
                        />
                    </FormControl>
                    <div
                        v-if="validationErrors['person_to_meet']"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ validationErrors["person_to_meet"] }}
                    </div>
                    <FormMessage />
                </FormItem>
            </FormField>

            <!-- If purpose === others state reason (conditional) -->
            <FormField
                v-if="values.purpose === 'Other'"
                v-slot="{ componentField }"
                name="other_reason"
            >
                <FormItem>
                    <FormLabel>
                        {{ t('visitor.visitDetails.statePurpose') }}
                        <span class="text-red-500">*</span>
                    </FormLabel>
                    <FormControl>
                        <Input
                            type="text"
                            v-bind="componentField"
                            :placeholder="t('visitor.visitDetails.statePurpose')"
                            @input="handleOtherReasonInput"
                            :class="
                                validationErrors['other_reasons']
                                    ? 'border-red-500'
                                    : ''
                            "
                        />
                    </FormControl>
                    <div
                        v-if="validationErrors['other_reasons']"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ validationErrors["other_reasons"] }}
                    </div>
                    <FormMessage />
                </FormItem>
            </FormField>

            <!-- Shipment Transport Selection (conditional for shipping visitors) -->
            <FormField
                v-if="values.visitor_type === 'shipping'"
                v-slot="{ componentField }"
                name="shipment_transport_id"
            >
                <FormItem>
                    <FormLabel>
                        {{ t('visitor.visitDetails.selectShipment') }}
                        <span class="text-red-500">*</span>
                    </FormLabel>
                    <Select v-bind="componentField">
                        <FormControl>
                            <SelectTrigger>
                                <SelectValue :placeholder="t('visitor.visitDetails.shipmentPlaceholder')" />
                            </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem
                                    v-for="shipment in shipmentTransports"
                                    :key="shipment.id"
                                    :value="shipment.id.toString()"
                                >
                                    {{ shipment.transport_number }}
                                </SelectItem>
                                <div v-if="loadingShipments" class="p-2 text-center text-sm text-gray-500">
                                    Loading shipments...
                                </div>
                                <div v-else-if="shipmentTransports.length === 0" class="p-2 text-center text-sm text-gray-500">
                                    No shipments available
                                </div>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FormMessage />
                </FormItem>
            </FormField>
        </div>

        <!-- Remarks Field -->
        <FormField v-slot="{ componentField }" name="remarks">
            <FormItem>
                <FormLabel>
                    {{ t('visitor.visitDetails.remarks') }}
                    <span class="text-gray-500 text-sm"
                        >({{ values.remarks?.length || 0 }}/500)</span
                    >
                </FormLabel>
                <FormControl>
                    <Textarea
                        v-bind="componentField"
                        class="h-[100px]"
                        :placeholder="t('visitor.visitDetails.remarksPlaceholder')"
                        @input="handleRemarksInput"
                        :class="
                            validationErrors['remarks'] ? 'border-red-500' : ''
                        "
                        maxlength="500"
                    />
                </FormControl>
                <div
                    v-if="validationErrors['remarks']"
                    class="text-red-500 text-sm mt-1"
                >
                    {{ validationErrors["remarks"] }}
                </div>
                <FormMessage />
            </FormItem>
        </FormField>
    </div>
</template>
