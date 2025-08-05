<script setup lang="ts">
const props = defineProps<{
    open: boolean;
    message: string;
    acknowledged: boolean;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "proceed"): void;
    (e: "watch-video"): void;
}>();
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-md"
    >
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h2 class="text-xl font-semibold mb-4 text-center">
                Acknowledgement Status
            </h2>

            <p
                class="text-center mb-6"
                :class="{
                    'text-green-600': acknowledged,
                    'text-red-600': !acknowledged,
                }"
            >
                {{ message }}
            </p>

            <div class="flex justify-center gap-4">
                <button
                    v-if="acknowledged"
                    @click="$emit('proceed')"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
                >
                    Proceed to Submit
                </button>
                <button
                    v-else
                    @click="$emit('watch-video')"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                >
                    Go to Video & Guidelines
                </button>
            </div>

            <!-- 
            <div class="flex justify-center mt-4">
                <button
                    @click="$emit('close')"
                    class="text-gray-600 hover:text-black underline text-sm"
                >
                    Close
                </button>
            </div> -->
        </div>
    </div>
</template>