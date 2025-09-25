<script setup>
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useForm } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    reason: {
        type: String,
        required: false,
    },
    message: {
        type: String,
        required: false,
    },
});

const form = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const submit = () => {
    form.post(route("password.update.expired"), {
        onFinish: () =>
            form.reset("current_password", "password", "password_confirmation"),
    });
};

//function to return reasonMessage
const reasonMessage = computed(() => {
    if (props.reason === "first_time") {
        return "You need to change the password since this is the first time you login.";
    }
    if (props.reason === "expired") {
        return "You need to change the password because it has already been 180 days.";
    }
    return "";
});
</script>

<template>
    <div class="flex flex-col gap-6">
        <Card class="overflow-hidden shadow-xl shadow-black/30">
            <CardContent class="grid p-0 md:grid-cols-2">
                <!-- left side: form -->
                <form @submit.prevent="submit" class="p-6 md:p-8 bg-red-600">
                    <div class="flex flex-col gap-6">
                        <div class="flex flex-col items-center text-center">
                            <h1 class="text-3xl text-white font-bold">
                                Password Expired
                            </h1>
                            <p class="text-sm text-white">
                                Please update your password to continue
                            </p>
                            <p>{{ reasonMessage }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="current_password" class="text-white">
                                Current Password
                            </Label>
                            <Input
                                id="current_password"
                                type="password"
                                required
                                class="bg-white text-black"
                                v-model="form.current_password"
                                autocomplete="current-password"
                            />
                            <div
                                v-if="form.errors.current_password"
                                class="text-xs text-white bg-red-800/50 px-2 py-1 rounded"
                            >
                                {{ form.errors.current_password }}
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="password" class="text-white">
                                New Password
                            </Label>
                            <Input
                                id="password"
                                type="password"
                                required
                                class="bg-white text-black"
                                v-model="form.password"
                                autocomplete="new-password"
                            />
                            <div
                                v-if="form.errors.password"
                                class="text-xs text-white bg-red-800/50 px-2 py-1 rounded"
                            >
                                {{ form.errors.password }}
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label
                                for="password_confirmation"
                                class="text-white"
                            >
                                Confirm Password
                            </Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                required
                                class="bg-white text-black"
                                v-model="form.password_confirmation"
                                autocomplete="new-password"
                            />
                            <div
                                v-if="form.errors.password_confirmation"
                                class="text-xs text-white bg-red-800/50 px-2 py-1 rounded"
                            >
                                {{ form.errors.password_confirmation }}
                            </div>

                            <!-- 👇 Password policy instructions -->
                            <div
                                class="text-xs text-white mt-1"
                            >
                                {{ message }}
                            </div>
                        </div>

                        <div class="flex justify-center">
                            <Button
                                type="submit"
                                class="w-1/2 text-black bg-white"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                Update Password
                            </Button>
                        </div>
                    </div>
                </form>

                <!-- right side: logo -->
                <div class="flex items-center justify-center">
                    <div>
                        <img
                            src="/assets/LOGO-3.png"
                            alt="Image"
                            class="mx-auto my-auto h-80 w-80 object-contain dark:brightness-[0.2] dark:grayscale"
                        />
                        <div class="text-center">V1.1</div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
