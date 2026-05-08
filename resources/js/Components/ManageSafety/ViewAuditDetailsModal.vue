<script setup lang="ts">
import { computed, ref } from 'vue';

const props = defineProps<{
    show: boolean;
    id: any;
}>();

const emit = defineEmits(["close"]);

const sessionData = computed(() => {
    if (!props.id) return null;
    return Array.isArray(props.id) ? props.id[0] : props.id;
});

// Image preview
const showImagePreview = ref(false);
const selectedImage = ref<string | null>(null);

function openImagePreview(photoPath: string) {
    selectedImage.value = '';
    // Force Vue to reset then set the value for proper reactivity
    requestAnimationFrame(() => {
        selectedImage.value = photoPath;
        showImagePreview.value = true;
    });
}

function closeImagePreview() {
    showImagePreview.value = false;
    selectedImage.value = null;
}
</script>

<template>
<Teleport to="body">
    <Transition name="modal-fade">
        <div
            v-if="show"
            class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[9999] p-4"
            @click.self="emit('close')"
        >
            <Transition name="modal-scale" appear>

                <!-- MAIN CARD -->
                <div
                    v-if="sessionData"
                    class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden"
                >

                    <!-- HEADER -->
                    <div class="flex justify-between items-center p-6 border-b bg-gray-50">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">
                                Inspection Details
                            </h2>
                            <p class="text-sm text-gray-500">
                                Session ID: #{{ sessionData.id }}
                            </p>
                        </div>

                        <button
                            @click="emit('close')"
                            class="text-gray-400 hover:text-gray-600 text-3xl w-10 h-10 flex items-center justify-center rounded-full hover:bg-gray-200 transition"
                        >
                            ×
                        </button>
                    </div>

                    <!-- BODY -->
                    <div class="p-6 overflow-y-auto space-y-6">

                        <!-- SUMMARY -->
                        <div class="grid grid-cols-2 gap-4 bg-blue-50 p-4 rounded-lg border border-blue-100">
                            <div>
                                <label class="text-xs font-semibold text-blue-700 uppercase">
                                    Date
                                </label>
                                <p class="text-gray-900 font-medium">
                                    {{ sessionData.date }}
                                </p>
                            </div>

                            <div>
                                <label class="text-xs font-semibold text-blue-700 uppercase">
                                    Status
                                </label>
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 capitalize">
                                    {{ sessionData.status }}
                                </span>
                            </div>

                            <div class="col-span-2">
                                <label class="text-xs font-semibold text-blue-700 uppercase">
                                    General Remarks
                                </label>
                                <p class="text-sm text-gray-700">
                                    {{ sessionData.remarks || 'No remarks provided.' }}
                                </p>
                            </div>
                        </div>

                        <!-- QUESTIONS -->
                        <div>
                            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                                <span>Questionnaire Results</span>
                                <span class="text-sm text-gray-500">
                                    ({{ sessionData.answers?.length }} items)
                                </span>
                            </h3>

                            <div class="space-y-3">
                                <div
                                    v-for="a in sessionData.answers"
                                    :key="a.id"
                                    class="group flex flex-col p-4 rounded-xl border border-gray-100 bg-white hover:border-blue-300 hover:shadow-md transition-all duration-200"
                                >
                                    <div class="flex items-start gap-3">

                                        <!-- ICON (LEFT, CLEAR) -->
                                        <div
                                            class="mt-1 w-7 h-7 rounded-full flex items-center justify-center shrink-0"
                                            :class="a.answer === 1
                                                ? 'bg-green-100 text-green-600'
                                                : a.answer === 0
                                                    ? 'bg-red-100 text-red-600'
                                                    : 'bg-gray-100 text-gray-500'"
                                        >
                                            <!-- Pass (YES = 1) -->
                                            <svg v-if="a.answer === 1" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <!-- Fail (NO = 0) -->
                                            <svg v-else-if="a.answer === 0" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            <!-- N/A (2) or null -->
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                            </svg>
                                        </div>

                                        <!-- CONTENT -->
                                        <div class="flex-1">

                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs font-bold text-gray-400">
                                                    Q{{ a.audit_question_id }}
                                                </span>
                                                <!-- Answer label -->
                                                <span
                                                    class="px-2 py-0.5 text-xs font-medium rounded-full"
                                                    :class="a.answer === 1
                                                        ? 'bg-green-100 text-green-700'
                                                        : a.answer === 0
                                                            ? 'bg-red-100 text-red-700'
                                                            : 'bg-gray-100 text-gray-600'"
                                                >
                                                    {{ a.answer === 1 ? 'Pass' : a.answer === 0 ? 'Fail' : a.answer === 2 ? 'N/A' : 'Not Set' }}
                                                </span>
                                            </div>

                                            <p class="text-gray-800 font-semibold leading-snug">
                                                {{ a.question?.question_text }}
                                            </p>

                                            <!-- REMARK -->
                                            <div
                                                v-if="a.remarks"
                                                class="mt-3 text-sm text-gray-600 bg-gray-50 p-3 rounded-md border-l-4"
                                                :class="a.answer === 1
                                                    ? 'border-green-300'
                                                    : 'border-red-300'"
                                            >
                                                <span class="font-semibold text-xs uppercase tracking-wide">
                                                    Remark
                                                </span>
                                                <p class="mt-1">
                                                    {{ a.remarks }}
                                                </p>
                                            </div>

                                            <!-- PHOTO -->
                                            <div
                                                v-if="a.photo_path"
                                                class="mt-3"
                                            >
                                                <button
                                                    @click="openImagePreview(a.photo_path)"
                                                    class="block relative overflow-hidden rounded-lg border-2 border-gray-300 hover:border-blue-500 transition-all duration-200 w-full max-w-xs group"
                                                >
                                                    <img
                                                        :src="'/storage/' + a.photo_path"
                                                        :alt="'Photo evidence'"
                                                        class="w-full h-32 object-cover group-hover:scale-105 transition-transform duration-200"
                                                    />
                                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors duration-200 flex items-center justify-center">
                                                        <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                                        </svg>
                                                    </div>
                                                </button>
                                            </div>

                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- FOOTER -->
                    <div class="p-4 bg-gray-50 border-t text-right">
                        <button
                            @click="emit('close')"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold transition shadow-md shadow-blue-200"
                        >
                            Done
                        </button>
                    </div>

                </div>

                <!-- LOADING -->
                <div v-else class="bg-white p-12 rounded-xl text-center shadow-xl">
                    <p class="text-gray-500">Loading session details...</p>
                </div>

            </Transition>
        </div>
    </Transition>

    <!-- Image Preview Lightbox -->
    <Transition name="modal-fade">
        <div
            v-if="showImagePreview"
            class="fixed inset-0 bg-black/90 backdrop-blur-sm flex items-center justify-center z-[10000]"
            @click.self="closeImagePreview"
        >
            <Transition name="modal-scale" appear>
                <div
                    v-if="showImagePreview"
                    class="relative max-w-4xl max-h-[90vh] mx-4"
                >
                    <!-- Close button -->
                    <button
                        @click="closeImagePreview"
                        class="absolute -top-12 right-0 text-white hover:text-gray-300 text-2xl font-bold w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/10 transition-colors z-10"
                    >
                        ×
                    </button>

                    <!-- Image container -->
                    <div class="relative">
                        <img
                            :src="'/storage/' + selectedImage"
                            alt="Photo evidence"
                            class="max-w-full max-h-[80vh] object-contain rounded-lg shadow-2xl"
                        />

                        <!-- Image info -->
                        <div class="absolute bottom-0 left-0 right-0 bg-black/70 text-white p-4 rounded-b-lg">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-lg">
                                    Photo Evidence
                                </h3>
                                <a
                                    :href="'/storage/' + selectedImage"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-blue-400 hover:text-blue-300 text-sm underline"
                                >
                                    Open full size
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>

</Teleport>
</template>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

.modal-scale-enter-active, .modal-scale-leave-active {
    transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-scale-enter-from, .modal-scale-leave-to {
    opacity: 0;
    transform: scale(0.92) translateY(20px);
}
</style>