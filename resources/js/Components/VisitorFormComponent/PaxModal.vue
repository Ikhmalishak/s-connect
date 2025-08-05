<script setup lang="ts">
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
        alert("Please enter a valid number greater than 0.");
        return;
    }
    emit("confirm", count);
    emit("update:open", false);
}
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    >
        <div class="max-w-md w-full bg-white p-6 rounded-xl shadow-xl">
            <h2 class="text-xl font-semibold mb-2 text-center">Visitor Type</h2>

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
                        Please Select Visitor Type
                    </option>
                    <option value="visitor">Visitor</option>
                    <option value="contractor">Contractor</option>
                    <option value="driver-shipment">Driver - Shipment</option>
                    <option value="driver-receiving">Driver - Receiving</option>
                </select>
            </div>

            <hr class="border-t border-gray-300 my-4" />

            <h2 class="text-xl font-semibold mt-4 mb-2 text-center">
                Number of Visitors
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
                <span class="text-black text-2xl font-medium">Pax</span>
            </div>

            <div class="mt-6 flex justify-center">
                <button
                    @click="confirm"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                    Confirm
                </button>
            </div>
        </div>
    </div>
</template>
