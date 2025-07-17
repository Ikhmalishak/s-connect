<script setup lang="ts">
import {
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

const props = defineProps<{
  visitors: any[];
  errors: any;
}>();
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
    <div
      v-for="(visitor, i) in visitors"
      :key="i"
      class="border p-4 rounded-md bg-gray-50"
    >
      <h3 class="font-semibold text-gray-700 mb-2">
        Visitor #{{ i + 1 }}
      </h3>

      <FormField :name="`visitors[${i}].visitor_name`" v-slot="{ componentField }">
        <FormItem>
          <FormLabel>Visitor Name <span class="text-red-500">*</span></FormLabel>
          <FormControl><Input v-bind="componentField" /></FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField :name="`visitors[${i}].id_type`" v-slot="{ componentField }">
        <FormItem>
          <FormLabel>ID Type <span class="text-red-500">*</span></FormLabel>
          <Select v-bind="componentField">
            <FormControl>
              <SelectTrigger><SelectValue placeholder="Select ID Type" /></SelectTrigger>
            </FormControl>
            <SelectContent>
              <SelectGroup>
                <SelectItem value="IC">Identification Card</SelectItem>
                <SelectItem value="Passport">Passport</SelectItem>
              </SelectGroup>
            </SelectContent>
          </Select>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField :name="`visitors[${i}].id_number`" v-slot="{ componentField }">
        <FormItem>
          <FormLabel>ID Number <span class="text-red-500">*</span></FormLabel>
          <FormControl><Input v-bind="componentField" /></FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField :name="`visitors[${i}].phone_number`" v-slot="{ componentField }">
        <FormItem>
          <FormLabel>Phone Number <span class="text-red-500">*</span></FormLabel>
          <FormControl><Input v-bind="componentField" /></FormControl>
          <FormMessage />
        </FormItem>
      </FormField>
    </div>
  </div>
</template>