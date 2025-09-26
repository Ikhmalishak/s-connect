<script setup lang="ts">
import AdminAuthenticatedLayout from "@/Layouts/AdminAuthenticatedLayout.vue";
import { Card } from "@/components/ui/card";
import {
    Breadcrumb,
    BreadcrumbPage,
    BreadcrumbSeparator,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
} from "@/components/ui/breadcrumb";
import { ref, onMounted, computed, watch } from "vue";
import axios from "axios";

interface PasswordPolicy {
    min_length: number;
    require_mixed_case: boolean;
    require_letters: boolean;
    require_numbers: boolean;
    require_symbols: boolean;
}

const policy = ref<PasswordPolicy>({
    min_length: 8,
    require_mixed_case: false,
    require_letters: false,
    require_numbers: false,
    require_symbols: false,
});

interface EncryptionSetting {
    id: number;
    table_name: string;
    field_name: string;
    label: string;
    is_encrypted: boolean;
}

const originalPolicy = ref<PasswordPolicy | null>(null);
const loading = ref(true);
const saving = ref(false);
const successMessage = ref("");
const errorMessage = ref("");
const testPassword = ref("");
const currentTime = ref(new Date());
const encryptionSettings = ref<EncryptionSetting[]>([]);
const savingEncryption = ref(false);
const encryptionMessage = ref("");
let intervalId;

const formattedDate = computed(() =>
    currentTime.value.toLocaleDateString("en-GB", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
    })
);

const formattedTime = computed(() =>
    currentTime.value.toLocaleTimeString("en-GB")
);

// Load current policy
onMounted(async () => {
    try {
        //password policy
        const res = await axios.get("/password-policy");
        policy.value = { ...res.data.data };
        originalPolicy.value = { ...res.data.data };

        //encryption database
        const result = await axios.get("/encryption-settings");
        console.log(result.data);
        encryptionSettings.value = result.data.data;

        intervalId = setInterval(() => {
            currentTime.value = new Date();
        }, 1000);
    } catch (err) {
        console.error(err);
        errorMessage.value = "Failed to load current policy.";
    } finally {
        loading.value = false;
    }
});

// Check if policy has been modified
const hasChanges = computed(() => {
    if (!originalPolicy.value) return false;
    return (
        JSON.stringify(policy.value) !== JSON.stringify(originalPolicy.value)
    );
});

// Generate password strength description
const strengthDescription = computed(() => {
    const requirements = [];
    if (policy.value.min_length > 0)
        requirements.push(`${policy.value.min_length}+ characters`);
    if (policy.value.require_letters) requirements.push("letters");
    if (policy.value.require_mixed_case)
        requirements.push("uppercase & lowercase");
    if (policy.value.require_numbers) requirements.push("numbers");
    if (policy.value.require_symbols) requirements.push("special characters");

    if (requirements.length === 0) return "No requirements";
    return requirements.join(", ");
});

// Calculate security level
const securityLevel = computed(() => {
    let score = 0;
    if (policy.value.min_length >= 8) score += 1;
    if (policy.value.min_length >= 12) score += 1;
    if (policy.value.require_letters) score += 1;
    if (policy.value.require_mixed_case) score += 1;
    if (policy.value.require_numbers) score += 1;
    if (policy.value.require_symbols) score += 1;

    if (score >= 5)
        return { level: "Strong", color: "text-green-600", bg: "bg-green-100" };
    if (score >= 3)
        return {
            level: "Medium",
            color: "text-yellow-600",
            bg: "bg-yellow-100",
        };
    return { level: "Weak", color: "text-red-600", bg: "bg-red-100" };
});

// Test password against current policy
const passwordTestResult = computed(() => {
    if (!testPassword.value) return null;

    const issues = [];
    if (testPassword.value.length < policy.value.min_length) {
        issues.push(`Must be at least ${policy.value.min_length} characters`);
    }
    if (policy.value.require_letters && !/[a-zA-Z]/.test(testPassword.value)) {
        issues.push("Must contain letters");
    }
    if (
        policy.value.require_mixed_case &&
        (!/[a-z]/.test(testPassword.value) || !/[A-Z]/.test(testPassword.value))
    ) {
        issues.push("Must contain both uppercase and lowercase letters");
    }
    if (policy.value.require_numbers && !/[0-9]/.test(testPassword.value)) {
        issues.push("Must contain numbers");
    }
    if (
        policy.value.require_symbols &&
        !/[!@#$%^&*(),.?":{}|<>]/.test(testPassword.value)
    ) {
        issues.push("Must contain special characters");
    }

    return {
        valid: issues.length === 0,
        issues: issues,
    };
});

// Clear messages after timeout
watch([successMessage, errorMessage], () => {
    setTimeout(() => {
        successMessage.value = "";
        errorMessage.value = "";
    }, 5000);
});

async function savePolicy() {
    saving.value = true;
    successMessage.value = "";
    errorMessage.value = "";

    try {
        const res = await axios.post("/password-policy", policy.value);
        successMessage.value =
            res.data.message || "Password policy updated successfully!";
        originalPolicy.value = { ...policy.value };
    } catch (error: any) {
        errorMessage.value =
            error.response?.data?.message ||
            "Failed to update policy. Please try again.";
    } finally {
        saving.value = false;
    }
}

async function saveEncryptionSettings() {
    savingEncryption.value = true;
    encryptionMessage.value = "";
    try {
        await axios.post("/encryption-settings", {
            settings: encryptionSettings.value,
        });
        encryptionMessage.value = "Encryption settings updated successfully!";
        setTimeout(() => {
            encryptionMessage.value = "";
        }, 1500);
    } catch (error: any) {
        encryptionMessage.value =
            error.response?.data?.message ||
            "Failed to save encryption settings.";
    } finally {
        savingEncryption.value = false;
    }
}

function resetPolicy() {
    if (originalPolicy.value) {
        policy.value = { ...originalPolicy.value };
    }
}

function usePreset(preset: string) {
    switch (preset) {
        case "basic":
            policy.value = {
                min_length: 6,
                require_letters: true,
                require_mixed_case: false,
                require_numbers: false,
                require_symbols: false,
            };
            break;
        case "standard":
            policy.value = {
                min_length: 8,
                require_letters: true,
                require_mixed_case: true,
                require_numbers: true,
                require_symbols: false,
            };
            break;
        case "strong":
            policy.value = {
                min_length: 12,
                require_letters: true,
                require_mixed_case: true,
                require_numbers: true,
                require_symbols: true,
            };
            break;
    }
}
</script>

<template>
    <AdminAuthenticatedLayout>
        <!-- Breadcrumb -->
        <template #breadcrumb>
            <Breadcrumb>
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink href="/"
                            >Visitor Management System</BreadcrumbLink
                        >
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbPage>Password Policy</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>
        </template>

        <Card
            class="shadow-lg shadow-opacity-30 p-2 mb-4 text-2xl font-bold flex items-center justify-between bg-gray-100"
        >
            <div class="flex flex-row items-center">
                <img src="/assets/ss1.png" class="h-12 w-12" alt="" />
                <div>Visitor Management System</div>
            </div>
            <div class="flex flex-row items-center gap-10">
                <div
                    class="flex flex-row space-x-4 text-base font-normal text-gray-600 text-right"
                >
                    <div>{{ formattedDate }}</div>
                    <div>{{ formattedTime }}</div>
                </div>
            </div>
        </Card>

        <!-- Header -->
        <Card class="shadow-lg p-2 mb-6 bg-gray-100">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-500 rounded-lg mr-4">
                        <svg
                            class="w-8 h-8 text-white"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                            ></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">
                            Password Policy Settings
                        </h1>
                        <p class="text-gray-600 mt-1">
                            Configure security requirements for user passwords
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">Security Level</div>
                    <div class="flex items-center mt-1">
                        <span
                            class="px-3 py-1 rounded-full text-sm font-semibold"
                            :class="[securityLevel.color, securityLevel.bg]"
                        >
                            {{ securityLevel.level }}
                        </span>
                    </div>
                </div>
            </div>
        </Card>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Configuration -->
            <div class="lg:col-span-2">
                <Card class="shadow-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">
                            Policy Configuration
                        </h2>
                        <div
                            v-if="loading"
                            class="flex items-center text-blue-600"
                        >
                            <svg
                                class="animate-spin -ml-1 mr-3 h-5 w-5"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            Loading...
                        </div>
                    </div>

                    <div v-if="!loading" class="space-y-6">
                        <!-- Quick Presets -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">
                                Quick Presets
                            </h3>
                            <div class="flex gap-3">
                                <button
                                    @click="usePreset('strong')"
                                    class="px-4 py-2 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition-colors text-sm"
                                >
                                    Strong (12+ chars, all requirements)
                                </button>
                            </div>
                        </div>

                        <!-- Minimum Length -->
                        <div
                            class="bg-white border border-gray-200 p-4 rounded-lg"
                        >
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                Minimum Password Length
                            </label>
                            <div class="flex items-center gap-4">
                                <input
                                    type="range"
                                    min="12"
                                    max="32"
                                    v-model="policy.min_length"
                                    class="flex-1"
                                />
                                <div
                                    class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold min-w-[60px] text-center"
                                >
                                    {{ policy.min_length }}
                                </div>
                            </div>
                            <div class="text-xs text-gray-500 mt-2">
                                Range: 12-32 characters
                            </div>
                        </div>

                        <!-- Requirements -->
                        <div
                            class="bg-white border border-gray-200 p-4 rounded-lg"
                        >
                            <h3 class="text-sm font-medium text-gray-700 mb-4">
                                Character Requirements
                            </h3>
                            <div class="space-y-3">
                                <label
                                    class="flex items-center group cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="policy.require_letters"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 h-5 w-5 text-blue-600 transition duration-150 ease-in-out"
                                    />
                                    <div class="ml-3">
                                        <span
                                            class="text-sm font-medium text-gray-700 group-hover:text-gray-900"
                                            >Require Letters (a-z, A-Z)</span
                                        >
                                        <p class="text-xs text-gray-500">
                                            Password must contain alphabetic
                                            characters
                                        </p>
                                    </div>
                                </label>

                                <label
                                    class="flex items-center group cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="policy.require_mixed_case"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 h-5 w-5 text-blue-600 transition duration-150 ease-in-out"
                                    />
                                    <div class="ml-3">
                                        <span
                                            class="text-sm font-medium text-gray-700 group-hover:text-gray-900"
                                            >Require Mixed Case</span
                                        >
                                        <p class="text-xs text-gray-500">
                                            Password must contain both uppercase
                                            and lowercase letters
                                        </p>
                                    </div>
                                </label>

                                <label
                                    class="flex items-center group cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="policy.require_numbers"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 h-5 w-5 text-blue-600 transition duration-150 ease-in-out"
                                    />
                                    <div class="ml-3">
                                        <span
                                            class="text-sm font-medium text-gray-700 group-hover:text-gray-900"
                                            >Require Numbers (0-9)</span
                                        >
                                        <p class="text-xs text-gray-500">
                                            Password must contain numeric
                                            characters
                                        </p>
                                    </div>
                                </label>

                                <label
                                    class="flex items-center group cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="policy.require_symbols"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 h-5 w-5 text-blue-600 transition duration-150 ease-in-out"
                                    />
                                    <div class="ml-3">
                                        <span
                                            class="text-sm font-medium text-gray-700 group-hover:text-gray-900"
                                            >Require Special Characters</span
                                        >
                                        <p class="text-xs text-gray-500">
                                            Password must contain symbols
                                            (!@#$%^&* etc.)
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Messages -->
                        <div
                            v-if="successMessage || errorMessage"
                            class="space-y-2"
                        >
                            <div
                                v-if="successMessage"
                                class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center"
                            >
                                <svg
                                    class="w-5 h-5 mr-2"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                {{ successMessage }}
                            </div>
                            <div
                                v-if="errorMessage"
                                class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center"
                            >
                                <svg
                                    class="w-5 h-5 mr-2"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                {{ errorMessage }}
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3 pt-4 border-t border-gray-200">
                            <button
                                @click="savePolicy"
                                :disabled="saving || !hasChanges"
                                class="px-6 py-3 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors flex items-center"
                            >
                                <svg
                                    v-if="saving"
                                    class="animate-spin -ml-1 mr-2 h-4 w-4"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                {{ saving ? "Saving..." : "Save Policy" }}
                            </button>
                            <button
                                @click="resetPolicy"
                                :disabled="!hasChanges"
                                class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg shadow hover:bg-gray-200 disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed transition-colors"
                            >
                                Reset Changes
                            </button>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Policy Preview -->
                <Card class="shadow-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">
                        Current Policy
                    </h2>
                    <div class="space-y-3">
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">Requirements:</span>
                            <p class="mt-1">{{ strengthDescription }}</p>
                        </div>
                        <div class="flex items-center">
                            <span
                                class="px-2 py-1 rounded-full text-xs font-medium"
                                :class="[securityLevel.color, securityLevel.bg]"
                            >
                                {{ securityLevel.level }} Security
                            </span>
                        </div>
                    </div>
                </Card>

                <!-- Password Tester -->
                <Card class="shadow-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">
                        Test Password
                    </h2>
                    <div class="space-y-3">
                        <input
                            v-model="testPassword"
                            type="password"
                            placeholder="Enter password to test..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <div v-if="passwordTestResult" class="space-y-2">
                            <div class="flex items-center">
                                <svg
                                    v-if="passwordTestResult.valid"
                                    class="w-5 h-5 text-green-500 mr-2"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                <svg
                                    v-else
                                    class="w-5 h-5 text-red-500 mr-2"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                <span
                                    class="text-sm font-medium"
                                    :class="
                                        passwordTestResult.valid
                                            ? 'text-green-700'
                                            : 'text-red-700'
                                    "
                                >
                                    {{
                                        passwordTestResult.valid
                                            ? "Valid Password"
                                            : "Invalid Password"
                                    }}
                                </span>
                            </div>
                            <ul
                                v-if="!passwordTestResult.valid"
                                class="text-xs text-red-600 space-y-1"
                            >
                                <li
                                    v-for="issue in passwordTestResult.issues"
                                    :key="issue"
                                    class="flex items-center"
                                >
                                    <span
                                        class="w-1 h-1 bg-red-400 rounded-full mr-2"
                                    ></span>
                                    {{ issue }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </Card>

                <!-- Tips -->
                <Card
                    class="shadow-lg p-6 bg-gradient-to-br from-yellow-50 to-orange-50"
                >
                    <h2
                        class="text-lg font-semibold text-gray-800 mb-4 flex items-center"
                    >
                        <svg
                            class="w-5 h-5 text-yellow-600 mr-2"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd"
                            ></path>
                        </svg>
                        Security Tips
                    </h2>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li>• Longer passwords are generally more secure</li>
                        <li>• Mixed case adds complexity</li>
                        <li>• Numbers and symbols increase entropy</li>
                        <li>• Consider user experience vs securityy</li>
                    </ul>
                </Card>
            </div>
        </div>
        <!-- Encryption Settings Section -->
        <Card class="shadow-lg p-6 mt-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">
                Database Encryption Settings
            </h2>
            <p class="text-sm text-gray-600 mb-4">
                Control which fields are encrypted in the database. This helps
                demonstrate compliance with PDPA.
            </p>

            <div class="space-y-4">
                <div
                    v-for="setting in encryptionSettings"
                    :key="setting.id"
                    class="flex items-center justify-between bg-gray-50 p-3 rounded-lg border border-gray-200"
                >
                    <div>
                        <div class="font-medium text-gray-800">
                            {{ setting.label }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ setting.table_name }}.{{ setting.field_name }}
                        </div>
                    </div>
                    <label class="flex items-center cursor-pointer">
                        <input
                            type="checkbox"
                            v-model="setting.is_encrypted"
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-200 h-5 w-5"
                        />
                        <span class="ml-2 text-sm text-gray-700">
                            Encrypted
                        </span>
                    </label>
                </div>
            </div>

            <div class="flex justify-between gap-3 mt-6">
                <div>
                    <div
                        v-if="encryptionMessage"
                        class="mt-4 text-sm text-green-600"
                    >
                        {{ encryptionMessage }}
                    </div>
                </div>

                <button
                    @click="saveEncryptionSettings"
                    :disabled="savingEncryption"
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
                >
                    {{
                        savingEncryption
                            ? "Saving..."
                            : "Save Encryption Settings"
                    }}
                </button>
            </div>
        </Card>
    </AdminAuthenticatedLayout>
</template>
