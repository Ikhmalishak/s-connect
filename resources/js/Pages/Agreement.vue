<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, useForm } from "@inertiajs/vue3";

const form = useForm({});
const hasScrolledToBottom = ref(false);
const scrollContainer = ref(null);

const submit = () => {
    if (hasScrolledToBottom.value) {
        form.post(route("agreement.accept"));
    }
};

const checkScroll = () => {
    if (scrollContainer.value) {
        const { scrollTop, scrollHeight, clientHeight } = scrollContainer.value;
        // Consider scrolled to bottom if within 10px of the bottom
        hasScrolledToBottom.value = scrollTop + clientHeight >= scrollHeight - 10;
    }
};

onMounted(() => {
    if (scrollContainer.value) {
        scrollContainer.value.addEventListener('scroll', checkScroll);
        // Check initial state
        checkScroll();
    }
});

onBeforeUnmount(() => {
    if (scrollContainer.value) {
        scrollContainer.value.removeEventListener('scroll', checkScroll);
    }
});
</script>

<template>
    <div class="flex items-center justify-center min-h-screen bg-gray-50 py-8 px-4">
        <div class="max-w-2xl w-full">
            <Card class="shadow-lg border-gray-200 bg-white">
                <CardHeader class="text-center pb-6 space-y-4">
                    <div class="flex justify-center">
                        <img 
                            src="/assets/skpLogo.png" 
                            alt="SKP Logo" 
                            class="h-20 w-auto object-contain"
                        />
                    </div>
                    <div class="space-y-2">
                        <CardTitle class="text-2xl font-bold text-gray-900">
                            Container Inspection Management System
                        </CardTitle>
                        <p class="text-sm text-gray-500">
                            Confidentiality & Acceptable Use Agreement
                        </p>
                    </div>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div
                        ref="scrollContainer"
                        class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg p-6 bg-gradient-to-b from-gray-50 to-white"
                    >
                        <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed space-y-4">
                            <p class="text-gray-600">
                                By accessing or using the Container Inspection Management System ("the System"), you acknowledge and agree to the following terms and conditions:
                            </p>

                            <div class="space-y-4">
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 mb-1.5">
                                        1. Confidential Information
                                    </h4>
                                    <p class="text-sm text-gray-600">
                                        All data, records, reports, images, documents, and information contained within the System are strictly confidential and the exclusive property of Syarikat Sin Kwang Plastic Industries Sdn Bhd.
                                    </p>
                                </div>

                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 mb-1.5">
                                        2. Authorized Use Only
                                    </h4>
                                    <p class="text-sm text-gray-600">
                                        Access to the System is granted solely for authorized business purposes related to container inspection and management. Unauthorized access, use, modification, copying, or distribution of any information is strictly prohibited.
                                    </p>
                                </div>

                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 mb-1.5">
                                        3. Non-Disclosure
                                    </h4>
                                    <p class="text-sm text-gray-600">
                                        You shall not disclose, share, transmit, or reproduce any information from the System to any third party without prior written approval from Syarikat Sin Kwang Plastic Industries Sdn Bhd management.
                                    </p>
                                </div>

                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 mb-1.5">
                                        4. Data Integrity & Accuracy
                                    </h4>
                                    <p class="text-sm text-gray-600">
                                        Users are responsible for ensuring that all information entered into the System is accurate, complete, and truthful. Any unauthorized alteration or falsification of records is prohibited.
                                    </p>
                                </div>

                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 mb-1.5">
                                        5. Security Responsibility
                                    </h4>
                                    <p class="text-sm text-gray-600">
                                        You are responsible for maintaining the confidentiality of your login credentials. Any activity performed using your account will be deemed your responsibility.
                                    </p>
                                </div>

                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 mb-1.5">
                                        6. Monitoring & Audit
                                    </h4>
                                    <p class="text-sm text-gray-600">
                                        Syarikat Sin Kwang Plastic Industries Sdn Bhd reserves the right to monitor, audit, and review system access and usage at any time for security, compliance, and operational purposes.
                                    </p>
                                </div>

                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 mb-1.5">
                                        7. Violation & Disciplinary Action
                                    </h4>
                                    <p class="text-sm text-gray-600">
                                        Any breach of this agreement may result in disciplinary action, including access revocation, internal investigation, and legal action where applicable.
                                    </p>
                                </div>
                            </div>

                            <div class="pt-2 border-t border-gray-200 mt-6">
                                <p class="font-medium text-gray-900 text-sm">
                                    By selecting "I Accept", you confirm that you have read, understood, and agreed to comply with this agreement.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-center pt-2">
                        <Button
                            @click="submit"
                            :disabled="!hasScrolledToBottom || form.processing"
                            class="px-8 py-2.5 text-base font-medium transition-all duration-200"
                            :class="hasScrolledToBottom 
                                ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-sm hover:shadow' 
                                : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
                        >
                            {{ form.processing ? 'Processing...' : 'I Accept' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>