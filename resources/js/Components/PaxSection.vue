<script setup lang="ts">
import { ref, computed, watch, toRefs } from "vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Trash2 } from "lucide-vue-next";
import {
    FormField,
    FormItem,
    FormLabel,
    FormControl,
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
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";

// Props
const props = defineProps<{
    paxFields: any[];
    paxCount: number;
    maxPax?: number;
}>();

// Emits
const emit = defineEmits<{
    (e: "update:paxCount", value: number): void;
    (e: "removePax", index: number): void;
}>();

const { paxFields, paxCount, maxPax } = toRefs(props);
const cappedMax = computed(() => maxPax?.value ?? 10);

// Local state to solve double-click bug
const localPaxCount = ref(paxCount.value);

// Sync localPaxCount when parent updates
watch(
    paxCount,
    (newVal) => {
        localPaxCount.value = newVal;
    },
    { immediate: true }
);

// Emit updated count to parent when localPaxCount changes
watch(localPaxCount, (newVal) => {
    if (newVal !== paxCount.value) {
        emit("update:paxCount", newVal);
    }
});
</script>

<template>
    <div class="mb-4">
        <label
            for="pax-count"
            class="block text-sm font-medium leading-6 text-gray-900"
        >
            How many pax?
        </label>
        <Input
            id="pax-count"
            type="number"
            v-model="localPaxCount"
            min="0"
            :max="cappedMax"
            class="w-24"
        />
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div
            v-for="(field, index) in paxFields"
            :key="field.key"
            class="border rounded p-4 space-y-4 relative"
        >
            <h3 class="text-md font-semibold">Pax #{{ index + 1 }}</h3>

            <Button
                v-if="paxFields.length > 1"
                type="button"
                variant="destructive"
                size="sm"
                class="absolute top-4 right-4"
                @click="emit('removePax', index)"
            >
                <Trash2 class="h-4 w-4" />
            </Button>

            <!-- Name -->
            <FormField
                :name="`pax[${index}].visitor_name`"
                v-slot="{ componentField }"
            >
                <FormItem>
                    <FormLabel>Name</FormLabel>
                    <FormControl>
                        <Input
                            type="text"
                            v-bind="componentField"
                            placeholder="Enter name"
                        />
                    </FormControl>
                    <FormMessage />
                </FormItem>
            </FormField>

            <!-- ID Type -->
            <!-- <FormField
                :name="`pax[${index}].id_type`"
                v-slot="{ componentField }"
            >
                <FormItem>
                    <FormLabel>ID Type</FormLabel>
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
            </FormField> -->

            <!-- ID Type Radio -->
            <FormField
                v-slot="{ componentField }"
                :name="`pax[${index}].id_type`"
            >
                <FormItem class="space-y-3">
                    <FormLabel>ID Type</FormLabel>
                    <FormControl>
                        <RadioGroup
                            class="flex flex-col space-y-1"
                            v-bind="componentField"
                        >
                            <FormItem
                                class="flex items-center space-y-0 gap-x-3"
                            >
                                <FormControl>
                                    <RadioGroupItem value="IC" />
                                </FormControl>
                                <FormLabel class="font-normal">
                                    Identification Card
                                </FormLabel>
                            </FormItem>
                            <FormItem
                                class="flex items-center space-y-0 gap-x-3"
                            >
                                <FormControl>
                                    <RadioGroupItem value="Passport" />
                                </FormControl>
                                <FormLabel class="font-normal">
                                    Passport
                                </FormLabel>
                            </FormItem>
                        </RadioGroup>
                    </FormControl>
                    <FormMessage />
                </FormItem>
            </FormField>

            <!-- ID Number -->
            <FormField
                :name="`pax[${index}].id_number`"
                v-slot="{ componentField }"
            >
                <FormItem>
                    <FormLabel>
                        {{
                            (field.value as any).id_type === "Passport"
                                ? "Passport Number"
                                : "IC Number"
                        }}
                    </FormLabel>
                    <FormControl>
                        <Input
                            v-bind="componentField"
                            placeholder="Enter ID Number"
                        />
                    </FormControl>
                    <FormMessage />
                </FormItem>
            </FormField>

            <!-- Phone Number -->
            <FormField
                :name="`pax[${index}].phone_number`"
                v-slot="{ componentField }"
            >
                <FormItem>
                    <FormLabel>Phone Number</FormLabel>
                    <FormControl>
                        <Input
                            type="text"
                            v-bind="componentField"
                            placeholder="Enter Phone Number"
                        />
                    </FormControl>
                    <FormMessage />
                </FormItem>
            </FormField>
        </div>
    </div>
</template>
