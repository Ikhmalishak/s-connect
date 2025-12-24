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
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

interface Visitor {
    visitor_name: string;
    id_type: string;
    id_number: string;
    phone_number: string;
    pass_number: string;
    visitor_type: string;
}

const props = defineProps<{
    visitors: Visitor[];
    errors: any;
}>();

const emit = defineEmits(["update"]);

// Custom validation errors
const validationErrors = ref<{ [key: string]: string }>({});

// Validation functions
const validatePhoneNumber = (phone: string): string => {
    if (!phone) return t('visitor.personalInfo.validation.phoneRequired');

    if (phone === "0000000000") return "";

    if (!/^[\d\s\-\+\(\)]+$/.test(phone)) {
        return t('visitor.personalInfo.validation.phoneInvalid');
    }

    const digitsOnly = phone.replace(/\D/g, "");

    if (digitsOnly.length < 10 || digitsOnly.length > 15) {
        return t('visitor.personalInfo.validation.phoneLength');
    }

    if (
        !digitsOnly.startsWith("0") &&
        !digitsOnly.startsWith("6") &&
        digitsOnly.length < 12
    ) {
        return t('visitor.personalInfo.validation.phoneMalaysian');
    }

    return "";
};

const validateIdNumber = (idNumber: string, idType: string): string => {
    if (!idNumber) return t('visitor.personalInfo.validation.idRequired');

    if (idType === "IC") {
        const icPattern = /^\d{6}-?\d{2}-?\d{4}$/;
        const digitsOnly = idNumber.replace(/\D/g, "");

        if (!icPattern.test(idNumber) && digitsOnly.length !== 12) {
            return t('visitor.personalInfo.validation.icInvalid');
        }

        if (digitsOnly.length === 12) {
            const year = parseInt(digitsOnly.substring(0, 2));
            const month = parseInt(digitsOnly.substring(2, 4));
            const day = parseInt(digitsOnly.substring(4, 6));

            if (month < 1 || month > 12) {
                return t('visitor.personalInfo.validation.icMonth');
            }
            if (day < 1 || day > 31) {
                return t('visitor.personalInfo.validation.icDay');
            }
        }
    } else if (idType === "Passport") {
        if (!/^[A-Z0-9]{6,15}$/i.test(idNumber)) {
            return t('visitor.personalInfo.validation.passportInvalid');
        }
    }

    return "";
};

const validateName = (name: string): string => {
    if (!name?.trim()) return t('visitor.personalInfo.validation.nameRequired');

    if (!/^[a-zA-Z\s'-]+$/.test(name.trim())) {
        return t('visitor.personalInfo.validation.nameInvalid');
    }

    if (name.trim().length < 2) {
        return t('visitor.personalInfo.validation.nameTooShort');
    }

    if (name.trim().length > 100) {
        return t('visitor.personalInfo.validation.nameTooLong');
    }

    return "";
};

const formatPhoneNumber = (value: string): string => {
    let cleaned = value.replace(/[^\d\s\-\+\(\)]/g, "");

    if (cleaned === "0000000000") return value;

    // Example formatting: 012-345 6789
    // const digitsOnly = cleaned.replace(/\D/g, "");

    // if (digitsOnly.startsWith("0") && digitsOnly.length === 10) {
    //   return digitsOnly.replace(/(\d{3})(\d{3})(\d{4})/, "$1-$2 $3");
    // }

    return cleaned.substring(0, 20);
};

const handlePhoneInput = (visitorIndex: number, event: Event) => {
    const target = event.target as HTMLInputElement;
    const formatted = formatPhoneNumber(target.value);
    target.value = formatted;

    emit("update", {
        index: visitorIndex,
        field: "phone_number",
        value: formatted,
    });

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

    emit("update", {
        index: visitorIndex,
        field: "visitor_name",
        value,
    });

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
    const idType = props.visitors[visitorIndex]?.id_type;

    if (idType === "IC") {
        value = value.replace(/[^0-9-]/g, "");

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

        value = value.substring(0, 14);
    } else if (idType === "Passport") {
        value = value.replace(/[^A-Z0-9]/gi, "").toUpperCase();
        value = value.substring(0, 15);
    }

    target.value = value;

    emit("update", {
        index: visitorIndex,
        field: "id_number",
        value,
    });

    const error = validateIdNumber(value, idType);
    const errorKey = `visitors[${visitorIndex}].id_number`;

    if (error) {
        validationErrors.value[errorKey] = error;
    } else {
        delete validationErrors.value[errorKey];
    }
};

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

defineExpose({
    validationErrors,
    isValid: () => Object.keys(validationErrors.value).length === 0,
});
</script>

<template>
    <div class="space-y-6">
        <h2 class="text-xl font-semibold">{{ t('visitor.personalInfo.title') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
            <div
                v-for="(visitor, i) in visitors"
                :key="i"
                class="border p-4 rounded-md bg-gray-50"
            >
                <h3 class="font-semibold text-gray-700 mb-2">
                    {{ t(`visitorTypes.${visitor.visitor_type}`) }}
                    #{{ i + 1 }}
                </h3>

                <FormField
                    :name="`visitors[${i}].visitor_name`"
                    v-slot="{ componentField }"
                >
                    <FormItem>
                        <FormLabel>
                            {{ t(`visitorTypes.${visitor.visitor_type}`) }}
                            {{ t('visitor.personalInfo.visitorName') }} <span class="text-red-500">*</span>
                        </FormLabel>
                        <FormControl>
                            <Input
                                v-bind="componentField"
                                @input="handleNameInput(i, $event)"
                                :placeholder="t('visitor.personalInfo.fullNamePlaceholder')"
                                class="w-full p-2 placeholder:text-xs resize-none"
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

                <FormField
                    :name="`visitors[${i}].id_type`"
                    v-slot="{ componentField }"
                >
                    <FormItem>
                        <FormLabel>
                            {{ t('visitor.personalInfo.idType') }} <span class="text-red-500">*</span>
                        </FormLabel>
                        <Select v-bind="componentField">
                            <FormControl>
                                <SelectTrigger>
                                    <SelectValue :placeholder="t('visitor.personalInfo.idType')" />
                                </SelectTrigger>
                            </FormControl>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="IC">
                                        {{ t('visitor.personalInfo.identificationCard') }}
                                    </SelectItem>
                                    <SelectItem value="Passport">
                                        {{ t('visitor.personalInfo.passport') }}
                                    </SelectItem>
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
                        <FormLabel>
                            {{ t('visitor.personalInfo.idNumber') }} <span class="text-red-500">*</span>
                        </FormLabel>
                        <FormControl>
                            <Input
                                v-bind="componentField"
                                @input="handleIdNumberInput(i, $event)"
                                :placeholder="
                                    visitor.id_type === 'IC'
                                        ? t('visitor.personalInfo.icPlaceholder')
                                        : t('visitor.personalInfo.passportPlaceholder')
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

                <FormField
                    :name="`visitors[${i}].phone_number`"
                    v-slot="{ componentField }"
                >
                    <FormItem>
                        <FormLabel>
                            {{ t('visitor.personalInfo.phoneNumber') }} <span class="text-red-500">*</span>
                        </FormLabel>
                        <FormControl>
                            <Input
                                v-bind="componentField"
                                @input="handlePhoneInput(i, $event)"
                                :placeholder="t('visitor.personalInfo.phonePlaceholder')"
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
