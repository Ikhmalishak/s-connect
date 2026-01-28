<script setup lang="ts">
import { ref } from "vue";
import axios from "axios";
import { toTypedSchema } from "@vee-validate/zod";
import * as z from "zod";
import { useForm } from "vee-validate";
import { Button } from "@/components/ui/button";
import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
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

const formSchema = toTypedSchema(
    z
        .object({
            name: z.string().min(2).max(50),
            email: z.string().toLowerCase().email(),
            site_id: z.string(),
            role: z.enum(["admin", "guard", "receptionist","staff"]),
            password: z
                .string()
                .min(1, "Password must be at least 6 characters"),
            confirm_password: z.string(),
        })
        .refine((data) => data.password === data.confirm_password, {
            path: ["confirm_password"],
            message: "Passwords do not match",
        })
);
const form = useForm({ validationSchema: formSchema });
const props = defineProps<{ show: boolean }>();
const emit = defineEmits(["close"]);
const errorMessage = ref("");
const successMessage = ref("");

const onSubmit = form.handleSubmit(async (values) => {
    try {
        const res = await axios.post("/register", values);
        console.log("Form submitted!", res.data);
        setTimeout(() => {
            successMessage.value = "User created successfully";
            emit('close');
        }, 1000);
    } catch (e: any) {
        errorMessage.value =
            e.response?.data?.message ||
            "Failed to register user. Please try again.";

        setTimeout(() => (errorMessage.value = ""), 1000);
        console.error("Submit failed:", e);
    }
});
</script>
<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div
                v-if="show"
                class="fixed inset-0 bg-black/50 backdrop-blur-md flex items-center justify-center z-[9999]"
                @click.self="emit('close')"
            >
                <Transition name="modal-scale" appear>
                    <div
                        v-if="show"
                        class="bg-white p-6 rounded-lg shadow-lg w-[80%] max-w-2xl max-h-[80vh] overflow-y-auto"
                    >
                        <!-- Header -->
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold text-blue-700">
                                Create User
                            </h2>
                            <button
                                @click="emit('close')"
                                class="text-gray-500 hover:text-gray-700 text-xl font-bold"
                            >
                                ×
                            </button>
                        </div>
                        <!-- Form -->
                        <form class="w-2/3 space-y-6" @submit="onSubmit">
                            <FormField v-slot="{ componentField }" name="name">
                                <FormItem>
                                    <FormLabel>Name</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            placeholder="Enter name"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>
                            <FormField v-slot="{ componentField }" name="email">
                                <FormItem>
                                    <FormLabel>Email</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="email"
                                            placeholder="Enter email"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>
                            <FormField
                                v-slot="{ componentField }"
                                name="site_id"
                            >
                                <FormItem>
                                    <FormLabel>Site</FormLabel>
                                    <Select v-bind="componentField">
                                        <FormControl>
                                            <SelectTrigger>
                                                <SelectValue
                                                    placeholder="Select site"
                                                />
                                            </SelectTrigger>
                                        </FormControl>
                                        <SelectContent class="z-[99999]">
                                            <SelectGroup>
                                                <SelectItem value="1">
                                                    Site 1
                                                </SelectItem>
                                                <SelectItem value="2">
                                                    Site 2
                                                </SelectItem>
                                                <SelectItem value="3">
                                                    Site 3
                                                </SelectItem>
                                                <SelectItem value="4">
                                                    Site 4
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <FormMessage />
                                </FormItem>
                            </FormField>
                            <FormField v-slot="{ componentField }" name="role">
                                <FormItem>
                                    <FormLabel>Role</FormLabel>
                                    <Select v-bind="componentField">
                                        <FormControl>
                                            <SelectTrigger>
                                                <SelectValue
                                                    placeholder="Select role"
                                                />
                                            </SelectTrigger>
                                        </FormControl>
                                        <SelectContent class="z-[99999]">
                                            <SelectGroup>
                                                <SelectItem value="admin">
                                                    Admin
                                                </SelectItem>
                                                <SelectItem value="staff">
                                                    Staff
                                                </SelectItem>
                                                <SelectItem value="guard">
                                                    Guard
                                                </SelectItem>
                                                <SelectItem
                                                    value="receptionist"
                                                >
                                                    Receptionist
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <FormMessage />
                                </FormItem>
                            </FormField>
                            <FormField
                                v-slot="{ componentField }"
                                name="password"
                            >
                                <FormItem>
                                    <FormLabel>Password</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="password"
                                            placeholder="Enter password"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>
                            <FormField
                                v-slot="{ componentField }"
                                name="confirm_password"
                            >
                                <FormItem>
                                    <FormLabel>Confirm Password</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="password"
                                            placeholder="Confirm password"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>
                            <!-- Error message -->
                            <p v-if="errorMessage" class="text-red-600 mt-2">
                                {{ errorMessage }}
                            </p>
                            <!-- Success message -->
                            <p v-if="successMessage" class="text-red-600 mt-2">
                                {{ successMessage }}
                            </p>
                            <!-- Submit -->
                            <div class="flex justify-end">
                                <Button class="mt-4" type="submit"
                                    >Submit</Button
                                >
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}
.modal-scale-enter-active,
.modal-scale-leave-active {
    transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.modal-scale-enter-from,
.modal-scale-leave-to {
    opacity: 0;
    transform: perspective(1000px) rotateX(-90deg) scale(0.3) translateY(-200px);
}
</style>
