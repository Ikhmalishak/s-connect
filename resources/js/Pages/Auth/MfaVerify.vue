<script setup lang="ts">
import { ref } from "vue";
import axios from "axios";
import { Head, router } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

const code = ref("");
const error = ref("");
const loading = ref(false);
const resending = ref(false);

const submit = async () => {
    error.value = "";
    loading.value = true;

    try {
        const response = await axios.post("/mfa-verify", { code: code.value });

        if (response.status === 200) {
            router.visit("/");
        }
    } catch (err) {
        if (err.response?.status === 422) {
            error.value = err.response.data.errors.code?.[0] ?? "Invalid code.";
        } else {
            error.value = "Something went wrong. Please try again.";
        }
    } finally {
        loading.value = false;
    }
};

// ⬇️ moved outside of submit() — this makes it accessible to the template
const resend = async () => {
    resending.value = true;
    try {
        const response = await axios.post("/mfa-resend");
        alert(response.data.message);
    } catch (err) {
        alert("Unable to resend code. Please log in again.");
    } finally {
        resending.value = false;
    }
};
</script>

<template>
    <Head title="Verify MFA" />

    <div
        class="flex min-h-svh flex-col items-center justify-center bg-muted p-6 md:p-10"
    >
        <div class="w-full max-w-sm md:max-w-3xl">
            <Card class="overflow-hidden shadow-xl shadow-black/30">
                <CardContent class="grid p-0 md:grid-cols-2">
                    <!-- Form -->
                    <form
                        @submit.prevent="submit"
                        class="p-6 md:p-8 bg-red-600 flex flex-col gap-6 justify-center"
                    >
                        <div class="flex flex-col items-center text-center">
                            <h1 class="text-4xl text-white font-bold">
                                Multi-Factor Authentication
                            </h1>
                            <p class="text-sm text-white">
                                A 6-digit verification code was sent to your
                                company email. Please enter it below.
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="code" class="text-white"
                                >Verification Code</Label
                            >
                            <Input
                                id="code"
                                type="text"
                                maxlength="6"
                                required
                                class="text-black bg-white text-center text-xl tracking-widest"
                                v-model="code"
                                autofocus
                            />
                            <div
                                v-if="error"
                                class="text-sm text-white bg-red-800 p-2 rounded-md"
                            >
                                {{ error }}
                            </div>
                        </div>

                        <Button
                            type="submit"
                            class="w-1/3 mx-auto text-black bg-white"
                            :disabled="loading"
                        >
                            {{ loading ? "Verifying..." : "Verify" }}
                        </Button>
                    </form>

                    <!-- Right Side Image -->
                    <div class="flex items-center justify-center bg-white">
                        <div class="text-center">
                            <img
                                src="/assets/LOGO-3.png"
                                alt="Image"
                                class="mx-auto my-auto h-80 w-80 object-contain"
                            />
                            <div class="text-gray-600 text-sm">V1.1</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="flex flex-col items-center gap-4 mt-4">
                <img
                    src="/assets/5.png"
                    alt="Image"
                    class="h-24 w-24 object-contain dark:brightness-[0.2] dark:grayscale"
                />
                <div
                    class="text-center text-xs text-muted-foreground [&_a]:underline [&_a]:underline-offset-4 hover:[&_a]:text-primary"
                >
                    Having trouble?
                    <button
                        type="button"
                        @click="resend"
                        class="underline text-blue-600 disabled:opacity-50"
                        :disabled="resending"
                    >
                        {{ resending ? "Sending..." : "Resend Code" }}
                    </button>
                    or
                    <a href="/login">Back to Login</a>.
                </div>
            </div>
        </div>
    </div>
</template>
