
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
import { ref, watch } from "vue";

interface FormValues {
  vehicle_number?: string;
  visitor_company?: string;
  purpose?: string;
  person_to_meet?: string;
  remarks?: string;
}

const props = defineProps<{
  values: FormValues;
  errors: any;
  purposes: string[];
}>();

const emit = defineEmits(['update']);

// Custom validation errors
const validationErrors = ref<{[key: string]: string}>({});

// Validation functions
const validateVehicleNumber = (vehicleNumber: string): string => {
  if (!vehicleNumber) return "";
  
  const cleanNumber = vehicleNumber.replace(/\s+/g, '').toUpperCase();
  
  const patterns = [
    /^[A-Z]{1,3}\d{1,4}[A-Z]?$/,
    /^[A-Z]{2}\d{1,4}[A-Z]{1,2}$/,
    /^W[A-Z]{1,2}\d{1,4}[A-Z]?$/,
    /^[A-Z]\d{1,4}[A-Z]{2,3}$/
  ];
  
  const isValidFormat = patterns.some(pattern => pattern.test(cleanNumber));
  
  if (!isValidFormat) {
    return "Please enter a valid Malaysian vehicle number (e.g., ABC1234, AB1234C)";
  }
  
  if (cleanNumber.length < 4 || cleanNumber.length > 8) {
    return "Vehicle number must be between 4-8 characters";
  }
  
  return "";
};

const validateCompanyName = (company: string): string => {
  if (!company?.trim()) return " ";
  
  if (!/^[a-zA-Z0-9\s&.,()'-]+$/.test(company.trim())) {
    return "Company name contains invalid characters";
  }
  
  if (company.trim().length < 2) {
    return "Company name must be at least 2 characters long";
  }
  
  if (company.trim().length > 100) {
    return "Company name cannot exceed 100 characters";
  }
  
  return "";
};

const validatePersonToMeet = (person: string, purpose: string): string => {
  if (purpose === 'Meeting') {
    if (!person?.trim()) return "Person to meet is required for meetings";
    
    if (!/^[a-zA-Z\s'-]+$/.test(person.trim())) {
      return "Person name can only contain letters, spaces, apostrophes, and hyphens";
    }
    
    if (person.trim().length < 2) {
      return "Person name must be at least 2 characters long";
    }
    
    if (person.trim().length > 100) {
      return "Person name cannot exceed 100 characters";
    }
  }
  
  return "";
};

const validateRemarks = (remarks: string): string => {
  if (!remarks) return "";
  
  if (remarks.length > 500) {
    return "Remarks cannot exceed 500 characters";
  }
  
  const inappropriatePatterns = [
    /\b(fuck|shit|damn|bitch)\b/i,
    /<script|javascript:/i,
    /[<>{}]/g
  ];
  
  if (inappropriatePatterns.some(pattern => pattern.test(remarks))) {
    return "Remarks contain inappropriate or invalid content";
  }
  
  return "";
};

const formatVehicleNumber = (value: string): string => {
  return value.toUpperCase().replace(/\s+/g, '').substring(0, 8);
};

const handleVehicleNumberInput = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const formatted = formatVehicleNumber(target.value);
  target.value = formatted;
  
  emit('update', {
    field: 'vehicle_number',
    value: formatted
  });
  
  const error = validateVehicleNumber(formatted);
  const errorKey = 'vehicle_number';
  
  if (error) {
    validationErrors.value[errorKey] = error;
  } else {
    delete validationErrors.value[errorKey];
  }
};

const handleCompanyInput = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const value = target.value;
  
  emit('update', {
    field: 'visitor_company',
    value
  });
  
  const error = validateCompanyName(value);
  const errorKey = 'visitor_company';
  
  if (error) {
    validationErrors.value[errorKey] = error;
  } else {
    delete validationErrors.value[errorKey];
  }
};

const handlePersonToMeetInput = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const value = target.value;
  
  emit('update', {
    field: 'person_to_meet',
    value
  });
  
  const error = validatePersonToMeet(value, props.values?.purpose);
  const errorKey = 'person_to_meet';
  
  if (error) {
    validationErrors.value[errorKey] = error;
  } else {
    delete validationErrors.value[errorKey];
  }
};

const handleRemarksInput = (event: Event) => {
  const target = event.target as HTMLTextAreaElement;
  const value = target.value;
  
  emit('update', {
    field: 'remarks',
    value
  });
  
  const error = validateRemarks(value);
  const errorKey = 'remarks';
  
  if (error) {
    validationErrors.value[errorKey] = error;
  } else {
    delete validationErrors.value[errorKey];
  }
};

watch(() => props.values?.purpose, (newPurpose) => {
  if (props.values?.person_to_meet) {
    const error = validatePersonToMeet(props.values.person_to_meet, newPurpose);
    const errorKey = 'person_to_meet';
    
    if (error) {
      validationErrors.value[errorKey] = error;
    } else {
      delete validationErrors.value[errorKey];
    }
  }
  
  if (newPurpose !== 'Meeting') {
    delete validationErrors.value['person_to_meet'];
  }
});

defineExpose({
  validationErrors,
  isValid: () => {
    const hasErrors = Object.keys(validationErrors.value).length > 0;
    const companyRequired = !props.values?.visitor_company?.trim();
    const purposeRequired = !props.values?.purpose?.trim();
    const personToMeetRequired = props.values?.purpose === 'Meeting' && !props.values?.person_to_meet?.trim();
    
    return !hasErrors && !companyRequired && !purposeRequired && !personToMeetRequired;
  }
});
</script>
<template>
  <div class="space-y-6 ">
    <h2 class="text-xl font-semibold">Step 2: Visit Details</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Vehicle Number Field -->
      <FormField v-slot="{ componentField }" name="vehicle_number">
        <FormItem>
          <FormLabel>Vehicle Number</FormLabel>
          <FormControl>
            <Input
              type="text"
              v-bind="componentField"
              placeholder="ABC1234"
              @input="handleVehicleNumberInput"
              :class="validationErrors['vehicle_number'] ? 'border-red-500' : ''"
            />
          </FormControl>
          <div v-if="validationErrors['vehicle_number']" class="text-red-500 text-sm mt-1">
            {{ validationErrors['vehicle_number'] }}
          </div>
          <FormMessage />
        </FormItem>
      </FormField>

      <!-- Company Name Field -->
      <FormField v-slot="{ componentField }" name="visitor_company">
        <FormItem>
          <FormLabel>Visitor Company</FormLabel>
          <FormControl>
            <Input
              type="text"
              v-bind="componentField"
              placeholder="Enter company name"
              @input="handleCompanyInput"
              :class="validationErrors['visitor_company'] ? 'border-red-500' : ''"
            />
          </FormControl>
          <div v-if="validationErrors['visitor_company']" class="text-red-500 text-sm mt-1">
            {{ validationErrors['visitor_company'] }}
          </div>
          <FormMessage />
        </FormItem>
      </FormField>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Purpose Field -->
      <FormField v-slot="{ componentField }" name="purpose">
        <FormItem>
          <FormLabel>Purpose <span class="text-red-500">*</span></FormLabel>
          <Select v-bind="componentField">
            <FormControl>
              <SelectTrigger>
                <SelectValue placeholder="Select Purpose" />
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

      <!-- Person to Meet Field (conditional) -->
      <FormField
        v-if="values.purpose === 'Meeting'"
        v-slot="{ componentField }"
        name="person_to_meet"
      >
        <FormItem>
          <FormLabel>Person to Meet <span class="text-red-500">*</span></FormLabel>
          <FormControl>
            <Input
              type="text"
              v-bind="componentField"
              placeholder="Enter person's name"
              @input="handlePersonToMeetInput"
              :class="validationErrors['person_to_meet'] ? 'border-red-500' : ''"
            />
          </FormControl>
          <div v-if="validationErrors['person_to_meet']" class="text-red-500 text-sm mt-1">
            {{ validationErrors['person_to_meet'] }}
          </div>
          <FormMessage />
        </FormItem>
      </FormField>
    </div>

    <!-- Remarks Field -->
    <FormField v-slot="{ componentField }" name="remarks">
      <FormItem>
        <FormLabel>
          Remarks 
          <span class="text-gray-500 text-sm">({{ (values.remarks?.length || 0) }}/500)</span>
        </FormLabel>
        <FormControl>
          <Textarea
            v-bind="componentField"
            class="h-[100px]"
            placeholder="Additional notes or special requirements..."
            @input="handleRemarksInput"
            :class="validationErrors['remarks'] ? 'border-red-500' : ''"
            maxlength="500"
          />
        </FormControl>
        <div v-if="validationErrors['remarks']" class="text-red-500 text-sm mt-1">
          {{ validationErrors['remarks'] }}
        </div>
        <FormMessage />
      </FormItem>
    </FormField>
  </div>
</template>