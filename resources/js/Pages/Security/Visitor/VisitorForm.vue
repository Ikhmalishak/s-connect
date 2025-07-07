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
import { Check, ChevronsUpDown, Search } from "lucide-vue-next";
import axios from "axios";
import { useToast } from "@/components/ui/toast/use-toast";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Textarea } from "@/components/ui/textarea";
import { onMounted, ref } from "vue";
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxTrigger,
} from "@/components/ui/combobox";
import { cn } from "@/lib/utils";

interface Company {
    id: number;
    name: string;
}

const visitorCompany = ref<Company[]>([]);
const selectedVisitorCompany = ref<Company | null>(null);
const { toast } = useToast();

//fetch list of visitor company id when finish loading
onMounted(async () => {
    console.log("mounting....");
    const response = await axios.get("/listvisitorcompany");
    visitorCompany.value = response.data.visitor_company;
});

//form validation
const formSchema = toTypedSchema(
    z.object({
        visitor_name: z.string(),
        vehicle_number: z.string(),
        time_register: z.string(),
        time_in: z.string(),
        time_out: z.string(),
        reasons: z.string(),
        ic_number: z.string(),
        pass_number: z.string(),
        phone_number: z.string(),
        visitor_company_id: z.number({
            required_error: "Visitor company is required",
        }),
    })
);

const { handleSubmit, setFieldValue, resetForm } = useForm({
    validationSchema: formSchema,
});

const onSubmit = handleSubmit(async (values) => {
    try {
        console.log("Form submitted!", values);

        const result = await axios.post("/visitor", values);

        console.log(result);

        if (result.status === 200) {
            resetForm();       
            
            //Clear the Combobox selection manually
            selectedVisitorCompany.value = null;
            
            //Display toast
            toast({
                title: "Success",
                description: "Request submitted successfully.",
            });
        }
    } catch (error) {
        console.log(error);
    }
});
</script>

<template>
    <AuthenticatedLayout>
        <Card class="mt-4 mx-auto max-w-md w-full">
            <form @submit="onSubmit" class="space-y-4 max-w-sm p-6 my-auto">
                <!-- Request Date -->
                <FormField v-slot="{ componentField }" name="visitor_name">
                    <FormItem>
                        <FormLabel>Visitor Name</FormLabel>
                        <FormControl>
                            <Input
                                type="text"
                                v-bind="componentField"
                                placeholder="Ali bin Abu"
                            />
                        </FormControl>
                        <FormDescription
                            >Please insert the visitor name.</FormDescription
                        >
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Type -->
                <FormField v-slot="{ componentField }" name="vehicle_number">
                    <FormItem>
                        <FormLabel>Vehicle Number</FormLabel>
                        <Input
                            type="text"
                            v-bind="componentField"
                            placeholder="ABC1234"
                        />
                        <FormDescription
                            >Please insert the vehicle number</FormDescription
                        >
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Time Register -->
                <FormField v-slot="{ componentField }" name="time_register">
                    <FormItem>
                        <FormLabel>Time Register</FormLabel>
                        <FormControl>
                            <Input type="time" v-bind="componentField" />
                        </FormControl>
                        <FormDescription
                            >Fill in the time register</FormDescription
                        >
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Time In -->
                <FormField v-slot="{ componentField }" name="time_in">
                    <FormItem>
                        <FormLabel>Time In</FormLabel>
                        <FormControl>
                            <Input type="time" v-bind="componentField" />
                        </FormControl>
                        <FormDescription>Fill in the time in</FormDescription>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Time Register -->
                <FormField v-slot="{ componentField }" name="time_out">
                    <FormItem>
                        <FormLabel>Time Out</FormLabel>
                        <FormControl>
                            <Input type="time" v-bind="componentField" />
                        </FormControl>
                        <FormDescription>Fill in the time out</FormDescription>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!--Visitor Company-->
                <Combobox
                    v-model="selectedVisitorCompany"
                    @update:modelValue="
                        setFieldValue('visitor_company_id', $event?.id ?? null)
                    "
                    by="name"
                >
                    <ComboboxAnchor as-child>
                        <ComboboxTrigger as-child>
                            <Button variant="outline" class="justify-between">
                                {{
                                    selectedVisitorCompany?.name ??
                                    "Select Visitor Company"
                                }}

                                <ChevronsUpDown
                                    class="ml-2 h-4 w-4 shrink-0 opacity-50"
                                />
                            </Button>
                        </ComboboxTrigger>
                    </ComboboxAnchor>

                    <ComboboxList>
                        <div class="relative w-full max-w-sm items-center">
                            <ComboboxInput
                                class="pl-9 focus-visible:ring-0 border-0 border-b rounded-none h-10"
                                placeholder="Select framework..."
                            />
                            <span
                                class="absolute start-0 inset-y-0 flex items-center justify-center px-3"
                            >
                                <Search class="size-4 text-muted-foreground" />
                            </span>
                        </div>

                        <ComboboxEmpty> No framework found. </ComboboxEmpty>

                        <ComboboxGroup>
                            <ComboboxItem
                                v-for="company in visitorCompany"
                                :key="company.id"
                                :value="company"
                            >
                                {{ company.name }}

                                <ComboboxItemIndicator>
                                    <Check :class="cn('ml-auto h-4 w-4')" />
                                </ComboboxItemIndicator>
                            </ComboboxItem>
                        </ComboboxGroup>
                    </ComboboxList>
                </Combobox>

                <!--Reasons-->
                <FormField v-slot="{ componentField }" name="reasons">
                    <FormItem>
                        <FormLabel>Reason</FormLabel>
                        <FormControl>
                            <Textarea
                                placeholder="Write reason.."
                                v-bind="componentField"
                                class="h-[100px]"
                            />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Ic Number -->
                <FormField v-slot="{ componentField }" name="ic_number">
                    <FormItem>
                        <FormLabel>IC Number</FormLabel>
                        <FormControl>
                            <Input
                                type="text"
                                placeholder="Enter the IC Number"
                                v-bind="componentField"
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
                            <Input
                                type="text"
                                placeholder="Enter the Pass Number"
                                v-bind="componentField"
                            />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Phone Number -->
                <FormField v-slot="{ componentField }" name="phone_number">
                    <FormItem>
                        <FormLabel>Phone Number</FormLabel>
                        <FormControl>
                            <Input
                                type="text"
                                placeholder="Enter the Phone Number"
                                v-bind="componentField"
                            />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <Button type="submit">Submit</Button>
            </form>
        </Card>
    </AuthenticatedLayout>
</template>
