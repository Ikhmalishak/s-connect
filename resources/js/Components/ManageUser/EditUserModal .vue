<script setup lang="ts">
import { ref, watch } from "vue";
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

const props = defineProps<{ show: boolean; user: any }>();

interface Department {
    id: number;
    name: string;
}

const formSchema = toTypedSchema(
    z.object({
        name: z.string().min(2).max(50),
        email: z.string().toLowerCase().email(),
        site_id: z.string(),
        department_id: z.string(),
        role: z.enum(["admin", "guard", "receptionist", "staff"]),
    }),
);

const form = useForm({
    validationSchema: formSchema,
    initialValues: {
        name: props.user?.name || "",
        email: props.user?.email || "",
        site_id: String(props.user?.site_id || ""),
        department_id: String(props.user?.department_id || ""),
        role: props.user?.roles[0].name || "",
    },
});

const departments = ref<Department[]>([]);

watch(
    () => props.user,
    (newUser) => {
        if (newUser) {
            form.resetForm({
                values: {
                    name: newUser.name,
                    email: newUser.email,
                    site_id: String(newUser.site_id),
                    department_id: String(newUser.department_id || ""),
                    role: newUser.roles[0].name,
                },
            });
        }
    },
    { immediate: true },
);

const emit = defineEmits(["close", "saved"]);
const errorMessage = ref("");
const successMessage = ref("");
const resetPasswordMessage = ref("");
const isSubmitting = ref(false);
const isResetting = ref(false);

const onSubmit = form.handleSubmit(async (values) => {
    try {
        isSubmitting.value = true;
        await axios.put(`/admin/update-user/${props.user.id}`, values);

        // show success message
        successMessage.value = "User updated successfully!";

        // keep message visible for 1.5–2s, then close modal
        setTimeout(() => (successMessage.value = ""), 1500);
    } catch (e: any) {
        errorMessage.value =
            e.response?.data?.message ||
            "Failed to update user. Please try again.";
        setTimeout(() => (errorMessage.value = ""), 2000);
    } finally {
        isSubmitting.value = false;
    }
});

async function fetchDepartments() {
    try {
        const response = await axios.get("/api/departments");
        departments.value = response.data.data;
    } catch (error) {
        console.error("Failed to fetch departments:", error);
    }
}

fetchDepartments();

const resetPassword = async () => {
    try {
        isResetting.value = true;
        const res = await axios.post(`/admin/reset-password/${props.user.id}`);
        resetPasswordMessage.value = res.data.message;
        setTimeout(() => (resetPasswordMessage.value = ""), 2000);
    } catch (e: any) {
        errorMessage.value =
            e.response?.data?.message ||
            "Failed to reset password. Please try again.";
        setTimeout(() => (errorMessage.value = ""), 2000);
    } finally {
        isResetting.value = false;
    }
};
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
                                Edit User
                            </h2>
                            <button
                                @click="emit('close')"
                                class="text-gray-500 hover:text-gray-700 text-xl font-bold"
                            >
                                ×
                            </button>
                        </div>
                        <!-- Form -->

                        <form class="w-4/5 space-y-6" @submit="onSubmit">
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
                                                <SelectItem value="guard">
                                                    Guard
                                                </SelectItem>
                                                <SelectItem
                                                    value="receptionist"
                                                >
                                                    Receptionist
                                                </SelectItem>
                                                <SelectItem value="staff">
                                                    Staff
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <FormMessage />
                                </FormItem>
                            </FormField>
                            <FormField v-slot="{ componentField }" name="department_id">
                                <FormItem>
                                    <FormLabel>Department</FormLabel>
                                    <Select v-bind="componentField">
                                        <FormControl>
                                            <SelectTrigger>
                                                <SelectValue
                                                    placeholder="Select department"
                                                />
                                            </SelectTrigger>
                                        </FormControl>
                                        <SelectContent class="z-[99999]">
                                            <SelectGroup>
                                                <SelectItem
                                                v-for="dept in departments"
                                                :key="dept.id"
                                                :value = "String(dept.id)"
                                                >
                                                {{ dept.name }}
                                            </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <FormMessage />
                                </FormItem>
                            </FormField>
                            <!-- Error message -->
                            <p v-if="errorMessage" class="text-red-600 mt-2">
                                {{ errorMessage }}
                            </p>
                            <p
                                v-if="successMessage"
                                class="text-green-600 mt-2"
                            >
                                {{ successMessage }}
                            </p>
                            <p
                                v-if="resetPasswordMessage"
                                class="text-green-600 mt-2"
                            >
                                {{ resetPasswordMessage }}
                            </p>
                            <div class="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="primary"
                                    :disabled="isResetting"
                                    @click="resetPassword"
                                    class="border"
                                >
                                    {{
                                        isResetting
                                            ? "Resetting..."
                                            : "Reset Password"
                                    }}
                                </Button>
                                <Button type="submit" :disabled="isSubmitting">
                                    {{ isSubmitting ? "Saving..." : "Edit" }}
                                </Button>
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
