<script setup lang="ts">
import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { ref, watch } from "vue";

const props = defineProps<{
    visitors: any[];
    errors: any;
}>();

// Custom validation errors
const validationErrors = ref<{ [key: string]: string }>({});

// Validation functions
const validatePhoneNumber = (phone: string): string => {
    if (!phone) return "Phone number is required";

    // Remove all non-digit characters for validation
    const digitsOnly = phone.replace(/\D/g, "");

    // Check if it contains only digits
    if (phone !== digitsOnly && !/^[\d\s\-\+\(\)]+$/.test(phone)) {
        return "Phone number can only contain numbers, spaces, dashes, plus signs, and parentheses";
    }

    // Check length (assuming Malaysian phone numbers)
    if (digitsOnly.length < 10 || digitsOnly.length > 15) {
        return "Phone number must be between 10-15 digits";
    }

    // Check Malaysian format (starts with 0 for local, or country code)
    if (
        !digitsOnly.startsWith("0") &&
        !digitsOnly.startsWith("6") &&
        digitsOnly.length < 12
    ) {
        return "Please enter a valid Malaysian phone number";
    }

    return "";
};

const validateIdNumber = (idNumber: string, idType: string): string => {
    if (!idNumber) return "ID number is required";

    if (idType === "IC") {
        // Malaysian IC validation (12 digits: YYMMDD-PB-###G)
        const icPattern = /^\d{6}-?\d{2}-?\d{4}$/;
        const digitsOnly = idNumber.replace(/\D/g, "");

        if (!icPattern.test(idNumber) && digitsOnly.length !== 12) {
            return "IC number must be in format YYMMDD-PB-###G or 12 digits";
        }

        if (digitsOnly.length === 12) {
            // Basic date validation for first 6 digits
            const year = parseInt(digitsOnly.substring(0, 2));
            const month = parseInt(digitsOnly.substring(2, 4));
            const day = parseInt(digitsOnly.substring(4, 6));

            if (month < 1 || month > 12) {
                return "Invalid month in IC number";
            }
            if (day < 1 || day > 31) {
                return "Invalid day in IC number";
            }
        }
    } else if (idType === "Passport") {
        // Basic passport validation (alphanumeric, 6-15 characters)
        if (!/^[A-Z0-9]{6,15}$/i.test(idNumber)) {
            return "Passport number must be 6-15 alphanumeric characters";
        }
    }

    return "";
};

const validateName = (name: string): string => {
    if (!name?.trim()) return "Name is required";

    // Only allow letters, spaces, apostrophes, and hyphens
    if (!/^[a-zA-Z\s'-]+$/.test(name.trim())) {
        return "Name can only contain letters, spaces, apostrophes, and hyphens";
    }

    if (name.trim().length < 2) {
        return "Name must be at least 2 characters long";
    }

    if (name.trim().length > 100) {
        return "Name cannot exceed 100 characters";
    }

    return "";
};

// Format phone number as user types
const formatPhoneNumber = (value: string): string => {
    // Remove all non-digit characters except +, -, (, ), and spaces
    let cleaned = value.replace(/[^\d\s\-\+\(\)]/g, "");

    // Basic formatting for Malaysian numbers
    if (cleaned.startsWith("0") && cleaned.length > 3) {
        // Format local numbers: 01X-XXX XXXX
        if (cleaned.length <= 11) {
            cleaned = cleaned.replace(/(\d{3})(\d{3})(\d{4})/, "$1-$2 $3");
        }
    }

    return cleaned.substring(0, 20); // Limit length
};

// Input handlers
const handlePhoneInput = (visitorIndex: number, event: Event) => {
    const target = event.target as HTMLInputElement;
    const formatted = formatPhoneNumber(target.value);
    target.value = formatted;

    // Update the visitor data
    if (props.visitors[visitorIndex]) {
        props.visitors[visitorIndex].phone_number = formatted;
    }

    // Validate
    const error = validatePhoneNumber(formatted);
    const errorKey = `visitors[${visitorIndex}].phone_number`;

    if (error) {
        validationErrors.value[errorKey] = error;
    } else {
        delete validationErrors.value[errorKey];
    }
};

const handleNameInput = (visitorIndex: number, event: Event) => {
    const target = event.target as HTMLInputElement;
    const value = target.value;

    // Update the visitor data
    if (props.visitors[visitorIndex]) {
        props.visitors[visitorIndex].visitor_name = value;
    }

    // Validate
    const error = validateName(value);
    const errorKey = `visitors[${visitorIndex}].visitor_name`;

    if (error) {
        validationErrors.value[errorKey] = error;
    } else {
        delete validationErrors.value[errorKey];
    }
};

const handleIdNumberInput = (visitorIndex: number, event: Event) => {
    const target = event.target as HTMLInputElement;
    let value = target.value;

    // Format based on ID type
    const idType = props.visitors[visitorIndex]?.id_type;

    if (idType === "IC") {
        // Remove non-alphanumeric characters except hyphens
        value = value.replace(/[^0-9-]/g, "");

        // Auto-format IC number
        if (value.length > 6 && !value.includes("-")) {
            value = value.substring(0, 6) + "-" + value.substring(6);
        }
        if (value.length > 9 && value.split("-").length === 2) {
            const parts = value.split("-");
            value =
                parts[0] +
                "-" +
                parts[1].substring(0, 2) +
                "-" +
                parts[1].substring(2);
        }

        // Limit to IC format length
        value = value.substring(0, 14);
    } else if (idType === "Passport") {
        // Only alphanumeric for passport
        value = value.replace(/[^A-Z0-9]/gi, "").toUpperCase();
        value = value.substring(0, 15);
    }

    target.value = value;

    // Update the visitor data
    if (props.visitors[visitorIndex]) {
        props.visitors[visitorIndex].id_number = value;
    }

    // Validate
    const error = validateIdNumber(value, idType);
    const errorKey = `visitors[${visitorIndex}].id_number`;

    if (error) {
        validationErrors.value[errorKey] = error;
    } else {
        delete validationErrors.value[errorKey];
    }
};

// Watch for ID type changes to re-validate ID number
watch(
    () => props.visitors,
    (newVisitors) => {
        newVisitors.forEach((visitor, index) => {
            if (visitor.id_number) {
                const error = validateIdNumber(
                    visitor.id_number,
                    visitor.id_type
                );
                const errorKey = `visitors[${index}].id_number`;

                if (error) {
                    validationErrors.value[errorKey] = error;
                } else {
                    delete validationErrors.value[errorKey];
                }
            }
        });
    },
    { deep: true }
);

// Expose validation state to parent component
defineExpose({
    validationErrors,
    isValid: () => Object.keys(validationErrors.value).length === 0,
});
</script>

<template>
    <div class="space-y-6">
        <h2 class="text-xl font-semibold">Step 1: Visitor Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
            <div
                v-for="(visitor, i) in visitors"
                :key="i"
                class="border p-4 rounded-md bg-gray-50"
            >
                <h3 class="font-semibold text-gray-700 mb-2">
                    {{
                        visitor.visitor_type
                            .replace("-", " ")
                            .replace(/\b\w/g, (char) => char.toUpperCase())
                    }}
                    #{{ i + 1 }}
                </h3>

                <!-- Name Field -->
                <FormField
                    :name="`visitors[${i}].visitor_name`"
                    v-slot="{ componentField }"
                >
                    <FormItem>
                        <FormLabel>
                            {{
                                visitor.visitor_type
                                    .replace("-", " ")
                                    .replace(/\b\w/g, (char) =>
                                        char.toUpperCase()
                                    )
                            }}
                            Name <span class="text-red-500">*</span>
                        </FormLabel>
                        <FormControl>
                            <Input
                                v-bind="componentField"
                                @input="handleNameInput(i, $event)"
                                placeholder="Graham Bell"
                                :class="
                                    validationErrors[
                                        `visitors[${i}].visitor_name`
                                    ]
                                        ? 'border-red-500'
                                        : ''
                                "
                            />
                        </FormControl>
                        <div
                            v-if="
                                validationErrors[`visitors[${i}].visitor_name`]
                            "
                            class="text-red-500 text-sm mt-1"
                        >
                            {{
                                validationErrors[`visitors[${i}].visitor_name`]
                            }}
                        </div>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- ID Type Field -->
                <FormField
                    :name="`visitors[${i}].id_type`"
                    v-slot="{ componentField }"
                >
                    <FormItem>
                        <FormLabel>
                            ID Type <span class="text-red-500">*</span>
                        </FormLabel>
                        <Select v-bind="componentField">
                            <FormControl>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select ID Type" />
                                </SelectTrigger>
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

                <!-- ID Number Field -->
                <FormField
                    :name="`visitors[${i}].id_number`"
                    v-slot="{ componentField }"
                >
                    <FormItem>
                        <FormLabel>
                            ID Number <span class="text-red-500">*</span>
                        </FormLabel>
                        <FormControl>
                            <Input
                                v-bind="componentField"
                                @input="handleIdNumberInput(i, $event)"
                                :placeholder="
                                    visitor.id_type === 'IC'
                                        ? 'XXXXXX-XX-XXXX'
                                        : 'Passport Number'
                                "
                                :class="
                                    validationErrors[`visitors[${i}].id_number`]
                                        ? 'border-red-500'
                                        : ''
                                "
                            />
                        </FormControl>
                        <div
                            v-if="validationErrors[`visitors[${i}].id_number`]"
                            class="text-red-500 text-sm mt-1"
                        >
                            {{ validationErrors[`visitors[${i}].id_number`] }}
                        </div>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Phone Number Field -->
                <FormField
                    :name="`visitors[${i}].phone_number`"
                    v-slot="{ componentField }"
                >
                    <FormItem>
                        <FormLabel>
                            Phone Number <span class="text-red-500">*</span>
                        </FormLabel>
                        <FormControl>
                            <Input
                                v-bind="componentField"
                                @input="handlePhoneInput(i, $event)"
                                placeholder="01X-XXX XXXX"
                                :class="
                                    validationErrors[
                                        `visitors[${i}].phone_number`
                                    ]
                                        ? 'border-red-500'
                                        : ''
                                "
                            />
                        </FormControl>
                        <div
                            v-if="
                                validationErrors[`visitors[${i}].phone_number`]
                            "
                            class="text-red-500 text-sm mt-1"
                        >
                            {{
                                validationErrors[`visitors[${i}].phone_number`]
                            }}
                        </div>
                        <FormMessage />
                    </FormItem>
                </FormField>
            </div>
        </div>
    </div>
</template>
