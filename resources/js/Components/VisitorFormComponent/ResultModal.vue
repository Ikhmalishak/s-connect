<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
    open: Boolean,
    result: {
        type: [Object, null], // allow Object or null
        default: null, // default value
    },
});

const emit = defineEmits(["update:open"]);

function closeModal() {
    emit("update:open", false);
}
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    >
        <div class="bg-white p-6 rounded shadow text-center w-96">
            <h2 class="text-lg font-bold mb-4">{{ t('visitor.result.thankYou') }}</h2>

            <!-- Successfully Registered -->
            <div v-if="result?.created?.length" class="mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <div
                        class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center"
                    >
                        <svg
                            class="w-4 h-4 text-white"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-green-700">
                        {{ t('visitor.result.successfullyRegistered') }}
                    </h3>
                </div>
                <div class="space-y-3">
                    <div
                        v-for="visitor in result.created"
                        :key="visitor.id"
                        class="flex items-center justify-between p-4 bg-green-50 border-l-4 border-green-500 rounded-r-lg"
                    >
                        <span class="font-medium text-gray-800">{{
                            visitor.visitor_name
                        }}</span>
                        <span
                            class="bg-green-500 text-white px-3 py-1 rounded-full text-sm font-medium"
                        >
                            {{ t('visitor.result.pass') }}: {{ visitor.pass_number }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Failed to Register -->
            <div v-if="result?.failed?.length" class="mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <div
                        class="w-6 h-6 bg-red-500 rounded-full flex items-center justify-center"
                    >
                        <svg
                            class="w-4 h-4 text-white"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-red-700">
                        {{ t('visitor.result.failedToRegister') }}
                    </h3>
                </div>
                <div class="space-y-3">
                    <div
                        v-for="(fail, index) in result.failed"
                        :key="index"
                        class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg"
                    >
                        <div class="font-medium text-gray-800 mb-2">
                            {{ fail.visitor.visitor_name }}
                        </div>
                        <p class="text-sm text-red-700">{{ fail.reason }}</p>
                    </div>
                </div>
            </div>

            <button
                class="mt-6 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
                @click="closeModal"
            >
                {{ t('visitor.result.close') }}
            </button>
        </div>
    </div>
</template>
