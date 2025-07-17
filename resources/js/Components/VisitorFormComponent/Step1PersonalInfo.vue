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

const props = defineProps<{
  values: Record<string, any>;
  errors: Record<string, any>;
}>();
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
    <div
      v-for="(visitor, i) in values.visitors"
      :key="i"
      class="border p-4 rounded-md bg-gray-50"
    >
      <h3 class="font-semibold text-gray-700 mb-2">
        Visitor #{{ i + 1 }}
      </h3>

      <FormField :name="`visitors[0].visitor_name`" v-slot="{ componentField }">
        <FormItem>
          <FormLabel>Visitor Name <span class="text-red-500">*</span></FormLabel>
          <FormControl><Input v-bind="componentField" /></FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField :name="`visitors.${i}.id_type`" v-slot="{ componentField }">
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

      <FormField :name="`visitors.${i}.id_number`" v-slot="{ componentField }">
        <FormItem>
          <FormLabel>ID Number <span class="text-red-500">*</span></FormLabel>
          <FormControl><Input v-bind="componentField" /></FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField :name="`visitors.${i}.phone_number`" v-slot="{ componentField }">
        <FormItem>
          <FormLabel>Phone Number <span class="text-red-500">*</span></FormLabel>
          <FormControl><Input v-bind="componentField" /></FormControl>
          <FormMessage />
        </FormItem>
      </FormField>
    </div>
  </div>
</template>
