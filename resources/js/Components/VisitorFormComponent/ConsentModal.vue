<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Site {
    id: number;
    name: string;
    site_code: string;
}

const props = defineProps<{
    open: boolean;
    site: Site;
}>();

const emit = defineEmits<{
    (e: "update:open", value: boolean): void;
    (e: "agree"): void;
}>();

const scrolledToBottom = ref(false);
const textAreaRef = ref<HTMLElement | null>(null);

const companyName = computed(() => {
    return props.site.site_code === 'S5' ? 'SKP BM Electronic' : t('visitor.consent.company');
});

const introText = computed(() => {
    const baseText = t('visitor.consent.intro');
    const defaultCompany = t('visitor.consent.company');
    const currentCompany = companyName.value;
    return baseText.replace(defaultCompany, currentCompany);
});

const purposes = computed(() => [
    t('visitor.consent.purposes.0'),
    t('visitor.consent.purposes.1'),
    t('visitor.consent.purposes.2'),
    t('visitor.consent.purposes.3')
]);

const dataTypes = computed(() => [
    t('visitor.consent.dataTypes.0'),
    t('visitor.consent.dataTypes.1'),
    t('visitor.consent.dataTypes.2'),
    t('visitor.consent.dataTypes.3'),
    t('visitor.consent.dataTypes.4'),
    t('visitor.consent.dataTypes.5')
]);

const declarations = computed(() => [
    t('visitor.consent.declarations.0'),
    t('visitor.consent.declarations.1'),
    t('visitor.consent.declarations.2')
]);

function handleScroll() {
    if (!textAreaRef.value) return;
    const { scrollTop, scrollHeight, clientHeight } = textAreaRef.value;
    scrolledToBottom.value = scrollTop + clientHeight >= scrollHeight - 10; // 10px tolerance
}

function agree() {
    if (scrolledToBottom.value) {
        emit("agree");
        emit("update:open", false);
    }
}
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-md bg-opacity-50"
    >
        <div class="max-w-4xl w-full bg-white p-6 rounded-xl shadow-xl max-h-[90vh] flex flex-col">
            <h2 class="text-2xl font-semibold mb-4 text-center">{{ t('visitor.consent.title') }}</h2>
            <h3 class="text-xl font-medium mb-2 text-center">{{ t('visitor.consent.subtitle') }}</h3>
            <p class="text-center mb-4 font-medium">{{ companyName }}</p>

            <div
                ref="textAreaRef"
                @scroll="handleScroll"
                class="flex-1 overflow-y-auto border border-gray-300 p-4 rounded-md mb-4 max-h-96 text-sm leading-relaxed"
            >
                <p class="mb-4">
                    {{ introText }}
                </p>

                <h4 class="font-semibold mb-2">{{ t('visitor.consent.purposeTitle') }}</h4>
                <p class="mb-4">
                    {{ t('visitor.consent.purposeText') }}
                </p>
                <ul class="list-disc list-inside mb-4 ml-4">
                    <li v-for="purpose in purposes" :key="purpose">{{ purpose }}</li>
                </ul>

                <h4 class="font-semibold mb-2">{{ t('visitor.consent.typesTitle') }}</h4>
                <p class="mb-4">
                    {{ t('visitor.consent.typesText') }}
                </p>
                <ul class="list-disc list-inside mb-4 ml-4">
                    <li v-for="dataType in dataTypes" :key="dataType">{{ dataType }}</li>
                </ul>

                <h4 class="font-semibold mb-2">{{ t('visitor.consent.protectionTitle') }}</h4>
                <p class="mb-4">
                    {{ t('visitor.consent.protectionText') }}
                </p>

                <h4 class="font-semibold mb-2">{{ t('visitor.consent.retentionTitle') }}</h4>
                <p class="mb-4">
                    {{ t('visitor.consent.retentionText') }}
                </p>

                <h4 class="font-semibold mb-2">{{ t('visitor.consent.declarationTitle') }}</h4>
                <p class="mb-4">
                    {{ t('visitor.consent.declarationText') }}
                </p>
                <ul class="list-disc list-inside mb-4 ml-4">
                    <li v-for="declaration in declarations" :key="declaration">{{ declaration }}</li>
                </ul>

                <p class="mb-4">
                    {{ t('visitor.consent.disclaimer') }}
                </p>

                <p class="font-semibold text-center">{{ t('visitor.consent.acknowledged') }}</p>
            </div>

            <div class="flex justify-center">
                <button
                    @click="agree"
                    :disabled="!scrolledToBottom"
                    class="px-6 py-2 rounded text-white text-lg font-medium"
                    :class="
                        scrolledToBottom
                            ? 'bg-blue-600 hover:bg-blue-700'
                            : 'bg-gray-400 cursor-not-allowed'
                    "
                >
                    {{ t('visitor.consent.agreeButton') }}
                </button>
            </div>
        </div>
    </div>
</template>