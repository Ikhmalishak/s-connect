<script setup>
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Head, Link, useForm } from "@inertiajs/vue3";

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
});

const submit = () => {
    form.post(route("register"), {
        onFinish: () => form.reset("password", "password_confirmation"),
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
                                S-CONNECT
                            </h1>
                            <p class="text-balance text-sm text-white">
                                Create your account
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="name" class="text-white">Name</Label>
                            <Input
                                id="name"
                                type="text"
                                required
                                class="text-black bg-white"
                                v-model="form.name"
                                autofocus
                                autocomplete="name"
                            />
                            <div v-if="form.errors.name" class="text-xs text-white bg-red-800/50 px-2 py-1 rounded">
                                {{ form.errors.name }}
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="email" class="text-white">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                required
                                class="text-black bg-white"
                                v-model="form.email"
                                autocomplete="username"
                            />
                            <div v-if="form.errors.email" class="text-xs text-white bg-red-800/50 px-2 py-1 rounded">
                                {{ form.errors.email }}
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="password" class="text-white">Password</Label>
                            <Input
                                id="password"
                                type="password"
                                required
                                class="bg-white"
                                v-model="form.password"
                                autocomplete="new-password"
                            />
                            <div v-if="form.errors.password" class="text-xs text-white bg-red-800/50 px-2 py-1 rounded">
                                {{ form.errors.password }}
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="password_confirmation" class="text-white">Confirm Password</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                required
                                class="bg-white"
                                v-model="form.password_confirmation"
                                autocomplete="new-password"
                            />
                            <div v-if="form.errors.password_confirmation" class="text-xs text-white bg-red-800/50 px-2 py-1 rounded">
                                {{ form.errors.password_confirmation }}
                            </div>
                        </div>
                        <div class="flex flex-row items-center gap-3">
                            <Button
                                type="submit"
                                class="w-1/3 text-black bg-white"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                Register
                            </Button>
                            <Link
                                :href="route('login')"
                                class="text-sm text-white underline-offset-2 hover:underline"
                            >
                                Already registered?
                            </Link>
                        </div>
                    </div>
                </form>
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