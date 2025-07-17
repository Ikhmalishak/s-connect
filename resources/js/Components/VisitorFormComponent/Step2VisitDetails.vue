<script setup lang="ts">
import { ref, computed } from "vue";
import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

// Props
const props = defineProps<{
  values: any;
  errors: any;
}>();

// Mock data - replace with actual data sources
const sites = ref([
    "Site A",
    "Site B",
    "Site C",
    "Main Office",
    "Warehouse"
]);

const visitorCompany = ref([
    { id: 1, name: "Company A" },
    { id: 2, name: "Company B" },
    { id: 3, name: "Company C" },
    { id: 4, name: "Contractor XYZ" },
    { id: 5, name: "Vendor ABC" }
]);

const purposes = ref([
    "Meeting",
    "Delivery",
    "Maintenance",
    "Inspection",
    "Training",
    "Other"
]);
</script>

<template>
    <div class="space-y-6">
        <h2 class="text-xl font-semibold">Step 2: Visit Details</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <FormField v-slot="{ componentField }" name="vehicle_number">
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
                    <FormLabel>Site <span class="text-red-500">*</span></FormLabel>
                    <Select v-bind="componentField">
                        <FormControl>
                            <SelectTrigger>
                                <SelectValue placeholder="Select Site" />
                            </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem v-for="s in sites" :key="s" :value="s">
                                    {{ s }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <FormMessage />
                </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="visitor_company_id">
                <FormItem>
                    <FormLabel>Visitor Company <span class="text-red-500">*</span></FormLabel>
                    <Select v-bind="componentField">
                        <FormControl>
                            <SelectTrigger>
                                <SelectValue placeholder="Select Company" />
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

            <FormField v-slot="{ componentField }" name="visit_date">
                <FormItem>
                    <FormLabel>Visit Date <span class="text-red-500">*</span></FormLabel>
                    <FormControl>
                        <Input
                            type="date"
                            v-bind="componentField"
                        />
                    </FormControl>
                    <FormMessage />
                </FormItem>
            </FormField>

            <FormField v-slot="{ componentField }" name="visit_time">
                <FormItem>
                    <FormLabel>Visit Time <span class="text-red-500">*</span></FormLabel>
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
</template>