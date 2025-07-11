<script setup lang="ts">
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/zod";
import * as z from "zod";
import { Button } from "@/components/ui/button";
import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Card } from "@/components/ui/card";
import axios from "axios";
import { useToast } from "@/components/ui/toast/use-toast";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Textarea } from "@/components/ui/textarea";
import { onMounted, ref, computed } from "vue";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { router, usePage } from "@inertiajs/vue3";
import { watch } from "vue";

// 1. Declare these first
const paxCount = ref(1);

const pax = ref([
  {
    visitor_name: "",
    ic_number: "",
    passport: "",
    phone_number: "",
    isMalaysian: null,
  }
]);

// 2. THEN watch them
watch(paxCount, (newCount) => {
  if (newCount > pax.value.length) {
    // Add more pax entries
    for (let i = pax.value.length; i < newCount; i++) {
      pax.value.push({
        visitor_name: "",
        ic_number: "",
        passport: "",
        phone_number: "",
        isMalaysian: null,
      });
    }
  } else {
    pax.value.splice(newCount);
  }
});


interface Company {
    id: number;
    name: string;
}

const { props } = usePage();
const visitor = props.visitor as any | undefined;
const { toast } = useToast();
const isMalaysian = ref<boolean | null>(null);
const isPassReturned = ref(false);
const visitorCompany = ref<Company[]>([]);
const selectedVisitorCompany = ref<Company | null>(null);

// Load companies
onMounted(async () => {
    const res = await axios.get("/listvisitorcompany");
    visitorCompany.value = res.data.visitor_company;

    if (visitor) {
        selectedVisitorCompany.value =
            visitorCompany.value.find(
                (c) => c.id === visitor.visitor_company_id
            ) || null;

        isMalaysian.value = visitor?.ic_number ? true : false;
    }
});

const purposes = ["Visit", "Meeting", "Delivery", "Interview"];
const sites = ["Site 1", "Site 2", "Site 3", "Site 4"];

const formSchema = toTypedSchema(
    z
        .object({
            visitor_name: z.string(),
            vehicle_number: z.string().optional(),
            site: z.string(),
            time_register: z.string().optional(),
            time_in: z.string().optional(),
            time_out: z.string().optional(),
            remarks: z.string(),
            ic_number: z.string().optional(),
            passport: z.string().optional(),
            pass_number: z.string(),
            phone_number: z.string(),
            visitor_company_id: z.number({
                required_error: "Visitor company is required",
            }),
            purpose: z.string(),
            person_to_meet: z.string().optional(),
        })
        .refine((data) => data.ic_number || data.passport, {
            message: "IC Number or Passport must be provided.",
            path: ["ic_number"],
        })
);

const { handleSubmit, setFieldValue, values } = useForm({
    validationSchema: formSchema,
    initialValues: {
        visitor_name: visitor?.visitor_name ?? "",
        vehicle_number: visitor?.vehicle_number ?? "",
        site: visitor?.site ?? "",
        time_register: visitor?.time_register ?? "",
        time_in: visitor?.time_in ?? "",
        time_out: visitor?.time_out ?? "",
        remarks: visitor?.remarks ?? "",
        ic_number: visitor?.ic_number ?? "",
        passport: visitor?.passport ?? "",
        pass_number: visitor?.pass_number ?? "",
        phone_number: visitor?.phone_number ?? "",
        visitor_company_id: visitor?.visitor_company_id ?? undefined,
        purpose: visitor?.purpose ?? "",
        person_to_meet: visitor?.person_to_meet ?? "",
    },
});

const onSubmit = handleSubmit(async (values) => {
    try {
        if (visitor?.id) {
            const response = await axios.post(`/visitor/${visitor.id}`, {
                ...values,
                _method: "PUT",
            });
            console.log(response);
            if(response.status === 200){
            toast({ title: "Updated", description: "Visitor updated." });
            router.visit('/visitor');
            }
        } else {
            console.log(values);
            const response = await axios.post("/visitor/submit", values);
            if(response.status === 200){
            toast({ title: "Created", description: "Visitor created." });
            router.visit('/visitor');
            }
        }

    } catch (error) {
        console.error(error);
    }
});

</script>

<template>
<AuthenticatedLayout>
<Card class="mt-4 mx-auto max-w-md w-full">
<form @submit="onSubmit" class="space-y-4 p-6">
    <!-- Visitor Name -->
    <FormField v-slot="{ componentField }" name="visitor_name">
        <FormItem>
            <FormLabel>Visitor Name</FormLabel>
            <FormControl>
                <Input type="text" v-bind="componentField" />
            </FormControl>
            <FormMessage />
        </FormItem>
    </FormField>

    <!-- ID Type Selector -->
    <FormField name="id_type">
        <FormItem>
            <FormLabel>ID Type</FormLabel>
            <Select
                :modelValue="isMalaysian === true ? 'true' : isMalaysian === false ? 'false' : ''"
                @update:modelValue="
                    (value) => {
                        isMalaysian = value === 'true';
                        setFieldValue('passport', '');
                        setFieldValue('ic_number', '');
                    }
                "
            >
                <FormControl>
                    <SelectTrigger>
                        <SelectValue placeholder="Select your ID type" />
                    </SelectTrigger>
                </FormControl>
                <SelectContent>
                    <SelectGroup>
                        <SelectItem value="true">Identification Card</SelectItem>
                        <SelectItem value="false">Passport</SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>
            <FormMessage />
        </FormItem>
    </FormField>

    <!-- IC / Passport -->
    <template v-if="isMalaysian !== null">
        <FormField
            v-if="isMalaysian"
            v-slot="{ componentField }"
            name="ic_number"
        >
            <FormItem>
                <FormLabel>IC Number</FormLabel>
                <FormControl>
                    <Input type="text" v-bind="componentField" />
                </FormControl>
                <FormMessage />
            </FormItem>
        </FormField>

        <FormField
            v-else
            v-slot="{ componentField }"
            name="passport"
        >
            <FormItem>
                <FormLabel>Passport Number</FormLabel>
                <FormControl>
                    <Input type="text" v-bind="componentField" />
                </FormControl>
                <FormMessage />
            </FormItem>
        </FormField>
    </template>

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

    <!-- Phone Number -->
    <FormField v-slot="{ componentField }" name="phone_number">
        <FormItem>
            <FormLabel>Phone Number</FormLabel>
            <FormControl>
                <Input type="text" v-bind="componentField" />
            </FormControl>
            <FormMessage />
        </FormItem>
    </FormField>

    <!-- Vehicle Number -->
    <FormField v-slot="{ componentField }" name="vehicle_number">
        <FormItem>
            <FormLabel>Vehicle Number</FormLabel>
            <FormControl>
                <Input type="text" v-bind="componentField" />
            </FormControl>
            <FormMessage />
        </FormItem>
    </FormField>

    <!-- Site -->
    <FormField v-slot="{ componentField }" name="site">
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
                        <SelectItem
                            v-for="s in sites"
                            :key="s"
                            :value="s"
                        >
                            {{ s }}
                        </SelectItem>
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

    <!-- Pass Returned Checkbox -->
    <div class="flex items-center space-x-2">
        <input
            id="pass-returned"
            type="checkbox"
            v-model="isPassReturned"
            class="border-gray-300 rounded"
        />
        <label for="pass-returned" class="text-sm font-medium">Is Pass Returned?</label>
    </div>

    <!-- Time Out -->
    <FormField v-slot="{ componentField }" name="time_out">
        <FormItem>
            <FormLabel>Time Out</FormLabel>
            <FormControl>
                <Input
                    type="time"
                    v-bind="componentField"
                    :disabled="!isPassReturned || !values.time_in"
                />
            </FormControl>
            <FormMessage />
        </FormItem>
    </FormField>

    <!-- Visitor Company -->
    <FormField
        v-slot="{ componentField }"
        name="visitor_company_id"
    >
        <FormItem>
            <FormLabel>Visitor Company</FormLabel>
            <Select
                v-bind="componentField"
                @update:modelValue="
                    setFieldValue(
                        'visitor_company_id',
                        parseInt($event)
                    )
                "
            >
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

    
    <!-- Purpose -->
    <FormField v-slot="{ componentField }" name="purpose">
        <FormItem>
            <FormLabel>Purpose</FormLabel>
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

    <!-- Person to Meet -->
    <FormField
        v-if="values.purpose === 'Meeting'"
        v-slot="{ componentField }"
        name="person_to_meet"
    >
        <FormItem>
            <FormLabel>Person to Meet</FormLabel>
            <FormControl>
                <Input type="text" v-bind="componentField" />
            </FormControl>
            <FormMessage />
        </FormItem>
    </FormField>

    <!-- Remarks -->
    <FormField v-slot="{ componentField }" name="remarks">
        <FormItem>
            <FormLabel>Remarks</FormLabel>
            <FormControl>
                <Textarea
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
