<script setup lang="ts">
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
import { Input } from "@/components/ui/input";

// Props
const props = defineProps<{
    values: Record<string, any>;
    errors: Record<string, any>;
}>();
</script>

<template>
    <div class="flex flex-col gap-4 w-1/2 mx-auto">
        <!-- Visitor Name -->
        <FormField name="visitor_name" v-slot="{ componentField }">
            <FormItem>
                <FormLabel>Visitor Name</FormLabel>
                <FormControl>
                    <Input v-bind="componentField" />
                </FormControl>
                <FormMessage />
            </FormItem>
        </FormField>

        <!-- ID Type Select -->
        <FormField name="id_type" v-slot="{ componentField }">
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
                            <SelectItem value="Passport">Passport</SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
                <FormMessage />
            </FormItem>
        </FormField>

        <!-- Single ID Number Input -->
        <FormField name="id_number" v-slot="{ componentField }">
            <FormItem>
                <FormLabel>
                    {{
                        values.id_type === "Passport"
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

        <!-- Pass Number -->
        <FormField v-slot="{ componentField }" name="pass_number">
            <FormItem>
                <FormLabel>Pass Number</FormLabel>
                <FormControl>
                    <Input type="text" v-bind="componentField" />
                </FormControl>
                <FormMessage />
            </FormItem>
        </FormField>
    </div>
</template>
