<script setup>
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="flex flex-col gap-6">
        <Card class="overflow-hidden shadow-xl shadow-black/30">
            <CardContent class="grid p-0 md:grid-cols-2">
                <form @submit.prevent="submit" class="p-6 md:p-8 bg-red-600">
                    <div class="flex flex-col gap-6">
                        <div class="flex flex-col items-center text-center">
                            <h1 class="text-4xl text-white font-bold">
                                S-Connect
                            </h1>
                            <p class="text-balance text-sm text-white">
                                Login to your account
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="email" class="text-white">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                required
                                class="text-black bg-white"
                                v-model="form.email"
                                autofocus
                            />
                        </div>
                        <div class="grid gap-2">
                            <div class="flex items-center">
                                <Label for="password" class="text-white">Password</Label>
                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="ml-auto text-sm text-white underline-offset-2 hover:underline"
                                >
                                    Forgot your password?
                                </Link>
                            </div>
                            <Input
                                id="password"
                                type="password"
                                required
                                class="bg-white"
                                v-model="form.password"
                            />
                        </div>
                        <Button type="submit" class="w-1/3 mx-auto text-black bg-white">
                            Login
                        </Button>
                    </div>
                </form>
                <div class="flex items-center justify-center">
                    <img
                        src="/assets/7.png"
                        alt="Image"
                        class="mx-auto my-auto h-80 w-80 object-contain dark:brightness-[0.2] dark:grayscale"
                    />
                </div>
            </CardContent>
        </Card>
        <div class="flex flex-col items-center gap-4">
            <img
                src="/assets/5.png"
                alt="Image"
                class="h-24 w-24 object-contain dark:brightness-[0.2] dark:grayscale"
            />
            <div
                class="text-center text-xs text-muted-foreground [&_a]:underline [&_a]:underline-offset-4 hover:[&_a]:text-primary"
            >
                By clicking continue, you agree to our
                <a href="#">Terms of Service</a> and
                <a href="#">Privacy Policy</a>.
            </div>
        </div>
    </div>
</template>
