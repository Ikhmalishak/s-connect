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
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { cn } from "@/lib/utils";
import { router, usePage } from "@inertiajs/vue3";
import { data } from "autoprefixer";

interface Company {
    id: number;
    name: string;
}

interface Purpose {
    id: number;
    name: string;
}

interface VisitorFormData {
    id?: number;
    visitor_name: string;
    vehicle_number: string;
    reasons: string;
    ic_number: string;
    pass_number: string;
    phone_number: string;
    visitor_company_id: number;
    purpose_id: number;
}
const { props } = usePage();
const visitor = props.visitor as VisitorFormData | undefined;
const { toast } = useToast();

const visitorCompany = ref<Company[]>([]);
const selectedVisitorCompany = ref<Company | null>(null);
const purpose = ref<Purpose>();
const selectedPurpose = ref<Purpose | null>(null);

// Load companies
onMounted(async () => {
    const res = await axios.get("/listvisitorcompany");
    visitorCompany.value = res.data.visitor_company;

    // Pre-select company if editing
    if (visitor) {
        selectedVisitorCompany.value =
            visitorCompany.value.find(
                (c) => c.id === visitor.visitor_company_id
            ) || null;
    }
});

//dummy data
const purposes = [
    { id: 1, name: "Visit" },
    { id: 2, name: "Meeting" },
    { id: 3, name: "Delivery" },
    { id: 4, name: "Interview" },
];

//form validation
const formSchema = toTypedSchema(
    z.object({
        visitor_name: z.string(),
        vehicle_number: z.string(),
        time_register: z.string().optional(),
        time_in: z.string().optional(),
        time_out: z.string().optional(),
        reasons: z.string(),
        ic_number: z
            .string()
            .length(12, { message: "IC Number must be exactly 12 digits." })
            .regex(/^\d+$/, {
                message: "IC Number must contain only numbers.",
            }),
        pass_number: z.string(),
        phone_number: z.string(),
        visitor_company_id: z.number({
            required_error: "Visitor company is required",
        }),
        purpose_id: z.number({
            required_error: "Purpose is required",
        }),
    })
);

const { handleSubmit, setFieldValue, resetForm } = useForm({
    validationSchema: formSchema,
    initialValues: {
        visitor_name: visitor?.visitor_name ?? "",
        vehicle_number: visitor?.vehicle_number ?? "",
        reasons: visitor?.reasons ?? "",
        ic_number: visitor?.ic_number ?? "",
        pass_number: visitor?.pass_number ?? "",
        phone_number: visitor?.phone_number ?? "",
        visitor_company_id: visitor?.visitor_company_id ?? undefined,
        purpose_id: visitor?.purpose_id ?? undefined,
    },
});

const onSubmit = handleSubmit(async (values) => {
    try {
        if (visitor?.id) {
            // Update
            console.log(`/visitor/${visitor.id}`)
            console.log(visitor.id);
            await axios.post(`/visitor/${visitor.id}`, { 
                ...values,
                _method: 'PUT'
            });
            toast({ title: "Updated", description: "Visitor updated." });
        } else {
            // Create
            await axios.post("/visitor/submit", values);
            toast({ title: "Created", description: "Visitor created." });
        }
    } catch (error) {
        console.error(error);
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
                                placeholder="Please write your name..."
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

                <!-- Vehicle Number -->
                <FormField v-slot="{ componentField }" name="vehicle_number">
                    <FormItem>
                        <FormLabel>Vehicle Number</FormLabel>
                        <Input
                            type="text"
                            v-bind="componentField"
                            placeholder="Please write the vehicle number"
                        />
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Site -->
                <FormField v-slot="{ componentField }" name="type">
                    <FormItem>
                        <FormLabel>Site</FormLabel>
                        <Select v-bind="componentField">
                            <FormControl>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Site" />
                                </SelectTrigger>
                            </FormControl>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="Site 1"
                                        >Site 1</SelectItem
                                    >
                                    <SelectItem value="Site 2"
                                        >Site 2</SelectItem
                                    >
                                    <SelectItem value="Site 3"
                                        >Site 3</SelectItem
                                    >
                                    <SelectItem value="Site 4"
                                        >Site 4</SelectItem
                                    >
                                </SelectGroup>
                            </SelectContent>
                        </Select>
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
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!--Visitor Company-->
                <label class="block text-sm font-medium text-gray-700">
                    Visitor Company
                </label>

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
                                placeholder="Select company..."
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

                <!--Purpose-->
                <label class="block text-sm font-medium text-gray-700">
                    Purpose
                </label>
                <Combobox
                    v-model="selectedPurpose"
                    @update:modelValue="
                        setFieldValue('purpose_id', $event?.id ?? null)
                    "
                    by="name"
                >
                    <ComboboxAnchor as-child>
                        <ComboboxTrigger as-child>
                            <Button variant="outline" class="justify-between">
                                {{ selectedPurpose?.name ?? "Select Purpose" }}

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
                                placeholder="Select purpose..."
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
                                v-for="purpose in purposes"
                                :key="purpose.id"
                                :value="purpose"
                            >
                                {{ purpose.name }}

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
                        <FormLabel>Remarks</FormLabel>
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

                <Button type="submit">
                    {{ visitor?.id ? "Update" : "Create" }}
                </Button>
            </form>
        </Card>
    </AuthenticatedLayout>
</template>
