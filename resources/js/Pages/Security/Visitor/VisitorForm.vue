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
import { onMounted, ref } from "vue";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { router, usePage } from "@inertiajs/vue3";

interface Company {
  id: number;
  name: string;
}

const { props } = usePage();
const visitor = props.visitor as any | undefined;
const { toast } = useToast();

const visitorCompany = ref<Company[]>([]);
const selectedVisitorCompany = ref<Company | null>(null);

// Load companies
onMounted(async () => {
  const res = await axios.get("/listvisitorcompany");
  visitorCompany.value = res.data.visitor_company;

  if (visitor) {
    selectedVisitorCompany.value =
      visitorCompany.value.find((c) => c.id === visitor.visitor_company_id) ||
      null;
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
      pic: z.string(),
    })
    .refine((data) => data.ic_number || data.passport, {
      message: "IC Number or Passport must be provided.",
      path: ["ic_number"],
    })
);

const { handleSubmit, setFieldValue } = useForm({
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
    pic: visitor?.pic ?? "",
  },
});

const onSubmit = handleSubmit(async (values) => {
  try {
    if (visitor?.id) {
      await axios.post(`/visitor/${visitor.id}`, {
        ...values,
        _method: "PUT",
      });
      toast({ title: "Updated", description: "Visitor updated." });
    } else {
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

        <!-- IC Number -->
        <FormField v-slot="{ componentField }" name="ic_number">
          <FormItem>
            <FormLabel>IC Number</FormLabel>
            <FormControl>
              <Input type="text" v-bind="componentField" />
            </FormControl>
            <FormMessage />
          </FormItem>
        </FormField>

        <!-- Passport -->
        <FormField v-slot="{ componentField }" name="passport">
          <FormItem>
            <FormLabel>Passport Number</FormLabel>
            <FormControl>
              <Input type="text" v-bind="componentField" />
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
                  <SelectItem v-for="s in sites" :key="s" :value="s">
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

        <!-- Time Out -->
        <FormField v-slot="{ componentField }" name="time_out">
          <FormItem>
            <FormLabel>Time Out</FormLabel>
            <FormControl>
              <Input type="time" v-bind="componentField" />
            </FormControl>
            <FormMessage />
          </FormItem>
        </FormField>

        <!-- Visitor Company -->
        <FormField v-slot="{ componentField }" name="visitor_company_id">
          <FormItem>
            <FormLabel>Visitor Company</FormLabel>
            <Select
              v-bind="componentField"
              @update:modelValue="
                setFieldValue('visitor_company_id', parseInt($event))
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
                  <SelectItem v-for="p in purposes" :key="p" :value="p">
                    {{ p }}
                  </SelectItem>
                </SelectGroup>
              </SelectContent>
            </Select>
            <FormMessage />
          </FormItem>
        </FormField>

        <!-- PIC -->
        <FormField v-slot="{ componentField }" name="pic">
          <FormItem>
            <FormLabel>Person in Charge</FormLabel>
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
              <Textarea v-bind="componentField" class="h-[100px]" />
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
