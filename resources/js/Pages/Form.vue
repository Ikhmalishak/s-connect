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
import { Input } from "@/components/ui/input";
import { Card } from "@/components/ui/card";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import axios from "axios";
import { useToast } from "@/components/ui/toast/use-toast";
import { ref } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const { toast } = useToast();

const fileInput = ref<HTMLInputElement | null>(null);

const fileSchema = z.custom<File>(
    (file) => {
        if (!file) return true; // Allow optional
        if (!(file instanceof File)) return false;
        if (file.type !== "application/pdf") return false;
        if (file.size < 100_000 || file.size > 1_000_000) return false;
        return true;
    },
    {
        message: "Please upload a PDF between 100KB and 1MB",
    }
);

const formSchema = toTypedSchema(
    z.object({
        request_date: z.string(),
        type: z.enum(["laptop", "desktop", "others"]),
        justification: z.enum(["new staff", "replacement", "others"]),
        urgency: z.enum(["low", "medium", "high"]),
        description: z.string().min(5),
        attachment: fileSchema.optional(),
    })
);

const { handleSubmit, resetForm } = useForm({
    validationSchema: formSchema,
});

const onSubmit = handleSubmit(async (values) => {
    console.log("Form submitted!", values);

    const formData = new FormData();
    Object.entries(values).forEach(([key, value]) => {
        if (value instanceof File) {
            formData.append(key, value);
        } else {
            formData.append(key, value ?? "");
        }
    });

    try {
        const response = await axios.post("/it-requests", formData);
        toast({
            title: "Success",
            description: "Request submitted successfully.",
        });
        console.log("Toast Triggered");
        resetForm();

        if (fileInput.value) {
            fileInput.value.value = "";
        }
    } catch (error) {
        console.error(error);
        toast({
            title: "Error",
            description: "Something went wrong. Please try again.",
            variant: "destructive",
        });
    }
});
</script>

<template>
    <AuthenticatedLayout>
        <Card class="mt-4 mx-auto max-w-md w-full">
            <form @submit="onSubmit" class="space-y-4 max-w-sm p-6 my-auto">
                <!-- Request Date -->
                <FormField v-slot="{ componentField }" name="request_date">
                    <FormItem>
                        <FormLabel>Request Date</FormLabel>
                        <FormControl>
                            <Input type="date" v-bind="componentField" />
                        </FormControl>
                        <FormDescription
                            >Pick the request date.</FormDescription
                        >
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Type -->
                <FormField v-slot="{ componentField }" name="type">
                    <FormItem>
                        <FormLabel>Request Type</FormLabel>
                        <Select v-bind="componentField">
                            <FormControl>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Type" />
                                </SelectTrigger>
                            </FormControl>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="laptop"
                                        >Laptop</SelectItem
                                    >
                                    <SelectItem value="desktop"
                                        >Desktop</SelectItem
                                    >
                                    <SelectItem value="others"
                                        >Others</SelectItem
                                    >
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <FormDescription
                            >Select type of item requested.</FormDescription
                        >
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Justification -->
                <FormField v-slot="{ componentField }" name="justification">
                    <FormItem>
                        <FormLabel>Justification</FormLabel>
                        <Select v-bind="componentField">
                            <FormControl>
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Select Justification"
                                    />
                                </SelectTrigger>
                            </FormControl>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="new staff"
                                        >New Staff</SelectItem
                                    >
                                    <SelectItem value="replacement"
                                        >Replacement</SelectItem
                                    >
                                    <SelectItem value="others"
                                        >Others</SelectItem
                                    >
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <FormDescription
                            >Reason for this request.</FormDescription
                        >
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Urgency -->
                <FormField v-slot="{ componentField }" name="urgency">
                    <FormItem>
                        <FormLabel>Urgency</FormLabel>
                        <RadioGroup
                            class="flex flex-row space-x-1"
                            v-bind="componentField"
                        >
                            <FormItem class="flex items-center gap-x-3">
                                <FormControl>
                                    <RadioGroupItem value="low" />
                                </FormControl>
                                <FormLabel class="font-normal">Low</FormLabel>
                            </FormItem>
                            <FormItem class="flex items-center gap-x-3">
                                <FormControl>
                                    <RadioGroupItem value="medium" />
                                </FormControl>
                                <FormLabel class="font-normal"
                                    >Medium</FormLabel
                                >
                            </FormItem>
                            <FormItem class="flex items-center gap-x-3">
                                <FormControl>
                                    <RadioGroupItem value="high" />
                                </FormControl>
                                <FormLabel class="font-normal">High</FormLabel>
                            </FormItem>
                        </RadioGroup>
                        <FormDescription
                            >Indicate urgency level.</FormDescription
                        >
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Description -->
                <FormField v-slot="{ componentField }" name="description">
                    <FormItem>
                        <FormLabel>Description</FormLabel>
                        <FormControl>
                            <Input
                                type="text"
                                placeholder="Write description"
                                v-bind="componentField"
                                class="h-[100px]"
                            />
                        </FormControl>
                        <FormDescription>Describe the request.</FormDescription>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Attachment -->
                <FormField v-slot="{ field }" name="attachment">
                    <FormItem>
                        <FormLabel>Attachment</FormLabel>
                        <FormControl>
                            <input
                                ref="fileInput"
                                type="file"
                                accept=".pdf"
                                @change="
                                (event) => {
                                    const input = event.target as HTMLInputElement;
                                    const file = input.files?.[0];
                                    field.onChange(file || undefined);
                                    }"
                            />
                        </FormControl>
                        <FormDescription
                            >Upload a PDF if needed.</FormDescription
                        >
                        <FormMessage />
                    </FormItem>
                </FormField>

                <Button type="submit">Submit</Button>
            </form>
        </Card>
    </AuthenticatedLayout>
</template>
