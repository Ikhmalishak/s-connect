<script setup lang="ts">
import LanguageSelector from "@/Components/LanguageSelector.vue";
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps<{
    open: boolean;
    paxInput: string;
    visitorType: string;
}>();

const emit = defineEmits<{
    (e: "update:open", value: boolean): void;
    (e: "update:pax-input", value: string): void;
    (e: "update:visitor-type", value: string): void;
    (e: "confirm", count: number): void;
}>();

function confirm() {
    const count = parseInt(props.paxInput, 10);
    if (isNaN(count) || count <= 0) {
        alert(t('visitor.paxModal.numberOfVisitors') + " must be greater than 0.");
        return;
    }
    emit("confirm", count);
    emit("update:open", false);
}
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-md bg-opacity-50"
    >
        <div class="max-w-md w-full bg-white p-6 rounded-xl shadow-xl">

            <img src="/assets/ss2.png" alt="" class="h-20 mx-auto mb-4" />

            <div class="mb-4 flex justify-center">
                <div class="scale-90">
                    <LanguageSelector />
                </div>
            </div>

            <h2 class="text-xl font-semibold mb-2 text-center">{{ t('visitor.paxModal.title') }}</h2>

            <div class="flex justify-center items-center gap-3">
                <select
                    :value="visitorType"
                    class="border border-gray-300 p-2 rounded-md max-w-xs w-full text-center text-lg"
                    @change="
                        $emit(
                            'update:visitor-type',
                            ($event.target as HTMLSelectElement).value
                        )
                    "
                >
                    <option value="" disabled selected hidden>
                        {{ t('visitor.paxModal.selectVisitorType') }}
                    </option>
                    <option value="visitor">{{ t('visitor.paxModal.visitor') }}</option>
                    <option value="contractor">{{ t('visitor.paxModal.contractor') }}</option>
                    <option value="inbound-shipment/transfer">
                        {{ t('visitor.paxModal.inboundShipment') }}
                    </option>
                    <option value="outbound-shipment/transfer">
                        {{ t('visitor.paxModal.outboundShipment') }}
                    </option>
                </select>
            </div>

            <hr class="border-t border-gray-300 my-4 mt-8" />

            <h2 class="text-xl font-semibold mt-10 mb-2 text-center">
                {{ t('visitor.paxModal.numberOfVisitors') }}
            </h2>

            <div class="flex justify-center items-center gap-3">
                <input
                    :value="paxInput"
                    @input="
                        $emit(
                            'update:pax-input',
                            ($event.target as HTMLInputElement).value
                        )
                    "
                    type="number"
                    min="1"
                    max="5"
                    class="border border-gray-300 p-3 rounded-md w-24 text-center text-2xl"
                    placeholder="1-5"
                />
                <span class="text-black text-2xl font-medium">{{ t('visitor.paxModal.pax') }}</span>
            </div>

            <div class="mt-6 flex justify-center">
                <button
                    @click="confirm"
                    :disabled="!visitorType"
                    class="px-4 py-2 rounded text-white"
                    :class="
                        !visitorType
                            ? 'bg-gray-400 cursor-not-allowed'
                            : 'bg-blue-600 hover:bg-blue-700'
                    "
                >
                    {{ t('visitor.paxModal.confirm') }}
                </button>
            </div>

            <hr class="border-t border-gray-300 my-4 mt-8" />

            <div class="text-xs text-center">
                {{ t('visitor.paxModal.compliance') }}
            </div>
        </div>
    </div>
</template>
