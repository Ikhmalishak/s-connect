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

const form = useForm({
    validationSchema: formSchema,
});

const onSubmit = form.handleSubmit(async (values) => {
    console.log("Form submitted!", values);

    const formData = new FormData();
    Object.entries(values).forEach(([key, value]) => {
        if (value instanceof File) {
            formData.append(key, value);
        } else {
            formData.append(key, value ?? "");
        }
    });

    const response = await axios.post("/it-requests", formData);
    console.log(response.data);
});
</script>

<template>
  <Card class="mt-4 mx-auto max-w-md w-full">
    <form @submit="onSubmit" class="space-y-4 max-w-sm p-6 my-auto">
      <!-- Request Date -->
      <FormField v-slot="{ componentField }" name="request_date">
        <FormItem>
          <FormLabel>Request Date</FormLabel>
          <FormControl>
            <Input type="date" v-bind="componentField" />
          </FormControl>
          <FormDescription>Pick the request date.</FormDescription>
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
                <SelectItem value="laptop">Laptop</SelectItem>
                <SelectItem value="desktop">Desktop</SelectItem>
                <SelectItem value="others">Others</SelectItem>
              </SelectGroup>
            </SelectContent>
          </Select>
          <FormDescription>Select type of item requested.</FormDescription>
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
                <SelectValue placeholder="Select Justification" />
              </SelectTrigger>
            </FormControl>
            <SelectContent>
              <SelectGroup>
                <SelectItem value="new staff">New Staff</SelectItem>
                <SelectItem value="replacement">Replacement</SelectItem>
                <SelectItem value="others">Others</SelectItem>
              </SelectGroup>
            </SelectContent>
          </Select>
          <FormDescription>Reason for this request.</FormDescription>
          <FormMessage />
        </FormItem>
      </FormField>

      <!-- Urgency -->
      <FormField v-slot="{ componentField }" name="urgency">
        <FormItem>
          <FormLabel>Urgency</FormLabel>
          <RadioGroup class="flex flex-row space-x-1" v-bind="componentField">
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
              <FormLabel class="font-normal">Medium</FormLabel>
            </FormItem>
            <FormItem class="flex items-center gap-x-3">
              <FormControl>
                <RadioGroupItem value="high" />
              </FormControl>
              <FormLabel class="font-normal">High</FormLabel>
            </FormItem>
          </RadioGroup>
          <FormDescription>Indicate urgency level.</FormDescription>
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
            <Input
              type="file"
              accept=".pdf"
              @change="
                (event) => {
                  const file = event.target.files?.[0];
                  field.onChange(file || undefined);
                }
              "
            />
          </FormControl>
          <FormDescription>Upload a PDF if needed.</FormDescription>
          <FormMessage />
        </FormItem>
      </FormField>

      <Button type="submit">Submit</Button>
    </form>
  </Card>
</template>
