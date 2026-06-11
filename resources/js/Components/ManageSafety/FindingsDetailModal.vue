<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import Button from '@/components/ui/button/Button.vue';

const props = defineProps<{
    show: boolean;
    session: any;
}>();

const emit = defineEmits(["close"]);

const loading = ref(false);
const detailData = ref<any>(null);
const failedAnswers = ref<any[]>([]);

// Image preview
const showImagePreview = ref(false);
const selectedImage = ref<string | null>(null);

function openImagePreview(photoPath: string) {
    selectedImage.value = photoPath;
    showImagePreview.value = true;
}

function closeImagePreview() {
    showImagePreview.value = false;
    selectedImage.value = null;
}

async function fetchFindings() {
    if (!props.session?.id) return;

    loading.value = true;
    try {
        const res = await axios.get(`/safety/findings/${props.session.id}`);
        detailData.value = res.data.session;
        // Each failed answer now has its own finding_action
        failedAnswers.value = res.data.failed_answers || [];
    } catch (err) {
        console.error('Error fetching findings:', err);
    } finally {
        loading.value = false;
    }
}

// Group all answers by section (for inspection results)
const groupedAnswers = computed(() => {
    const session = detailData.value;
    if (!session?.answers) return [];

    const groupMap = new Map();

    for (const answer of session.answers) {
        const section = answer.question?.section;
        if (!section) continue;

        const sectionId = section.id;
        if (!groupMap.has(sectionId)) {
            groupMap.set(sectionId, {
                id: section.id,
                name: section.name,
                sort_order: section.sort_order ?? 0,
                answers: []
            });
        }
        groupMap.get(sectionId).answers.push(answer);
    }

    return Array.from(groupMap.values()).sort((a, b) => a.sort_order - b.sort_order);
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        fetchFindings();
    } else {
        detailData.value = null;
        failedAnswers.value = [];
    }
});
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
                    <div
                        class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden"
                    >
                        <!-- HEADER -->
                        <div class="flex justify-between items-center p-6 border-b bg-gray-50">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">
                                    Inspection & Corrective Action Details
                                </h2>
                                <p class="text-sm text-gray-500">
                                    View full inspection results and corrective action history
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
                            <div v-if="loading" class="flex justify-center py-12">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            </div>

                            <template v-else-if="detailData">
                                <!-- Session Summary -->
                                <div class="grid grid-cols-2 gap-4 bg-blue-50 p-4 rounded-lg border border-blue-100">
                                    <div>
                                        <label class="text-xs font-semibold text-blue-700 uppercase">Site</label>
                                        <p class="text-gray-900 font-medium">{{ detailData.site?.name }}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-blue-700 uppercase">Audit Type</label>
                                        <p class="text-gray-900 font-medium">{{ detailData.audit_type?.name }}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-blue-700 uppercase">Department</label>
                                        <p class="text-gray-900 font-medium">{{ detailData.department?.name }}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-blue-700 uppercase">Status</label>
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="detailData.status === 'finding_closed' ? 'bg-green-100 text-green-800' :
                                                   detailData.status === 'failed' ? 'bg-red-100 text-red-800' :
                                                   'bg-yellow-100 text-yellow-800'">
                                            {{ detailData.status === 'finding_closed' ? 'Finding Closed' : detailData.status }}
                                        </span>
                                    </div>
                                    <div class="col-span-2">
                                        <label class="text-xs font-semibold text-blue-700 uppercase">Inspection Date</label>
                                        <p class="text-gray-900 font-medium">{{ detailData.date }}</p>
                                    </div>
                                </div>

                                <!-- Section: Each Finding + Its Own Corrective Action -->
                                <div>
                                    <h3 class="text-lg font-bold mb-3 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        <span>Findings & Corrective Actions ({{ failedAnswers.length }})</span>
                                    </h3>

                                    <div v-if="failedAnswers.length === 0" class="text-sm text-gray-500 italic p-4 bg-gray-50 rounded-lg">
                                        No failed items found in this inspection.
                                    </div>

                                    <!-- Each failed answer has its own row with finding + corrective action -->
                                    <div v-for="(answer, idx) in failedAnswers" :key="answer.id" class="mb-4 border border-gray-200 rounded-xl overflow-hidden">
                                        <!-- Finding (Red section) -->
                                        <div class="bg-red-50 p-3 border-b border-red-200">
                                            <div class="flex items-start gap-3">
                                                <div class="w-7 h-7 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0 text-xs font-bold">
                                                    {{ idx + 1 }}
                                                </div>
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <span class="text-xs font-bold text-red-700 uppercase">Finding</span>
                                                    </div>
                                                    <p class="text-gray-800 font-semibold text-sm">
                                                        {{ answer.question?.question_text || 'Unknown question' }}
                                                    </p>
                                                    <p v-if="answer.remarks" class="text-sm text-gray-600 mt-1">
                                                        <span class="font-semibold">Remark:</span> {{ answer.remarks }}
                                                    </p>
                                                    <div v-if="answer.photo_path" class="mt-2">
                                                        <button @click="openImagePreview(answer.photo_path)" class="block relative overflow-hidden rounded-lg border-2 border-gray-300 hover:border-blue-500 transition-all w-24 h-24">
                                                            <img :src="'/storage/' + answer.photo_path" class="w-full h-full object-cover" alt="Finding photo" />
                                                        </button>
                                                        <p class="text-xs text-gray-400 mt-1">Safety team photo</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Its Corrective Action (Green section) -->
                                        <div class="p-3 bg-white">
                                            <div class="flex items-start gap-3">
                                                <div class="w-7 h-7 rounded-full bg-green-100 text-green-600 flex items-center justify-center shrink-0 text-xs font-bold mt-0.5">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <span class="text-xs font-bold text-green-700 uppercase">Corrective Action</span>
                                                        <span v-if="answer.finding_action?.status === 'approved'" class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full">Approved</span>
                                                        <span v-else-if="answer.finding_action?.status === 'rejected'" class="text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded-full">Rejected</span>
                                                        <span v-else-if="answer.finding_action?.status === 'pending_review'" class="text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full">Pending Review</span>
                                                    </div>

                                                    <div v-if="answer.finding_action">
                                                        <p class="text-sm text-gray-700">{{ answer.finding_action.description || 'No description provided.' }}</p>

                                                        <div v-if="answer.finding_action.corrective_evidence" class="mt-2">
                                                            <button @click="openImagePreview(answer.finding_action.corrective_evidence)" class="block relative overflow-hidden rounded-lg border-2 border-gray-300 hover:border-blue-500 transition-all w-24 h-24">
                                                                <img :src="'/storage/' + answer.finding_action.corrective_evidence" class="w-full h-full object-cover" alt="Evidence" />
                                                            </button>
                                                            <p class="text-xs text-gray-400 mt-1">PIC evidence photo</p>
                                                        </div>

                                                        <!-- Person Info -->
                                                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                                                            <span>By: <strong>{{ answer.finding_action.submitter?.name || 'N/A' }}</strong></span>
                                                            <span v-if="answer.finding_action.submitted_at">Date: {{ new Date(answer.finding_action.submitted_at).toLocaleDateString() }}</span>
                                                            <span v-if="answer.finding_action.reviewer?.name">Reviewed by: <strong>{{ answer.finding_action.reviewer?.name }}</strong></span>
                                                            <span v-if="answer.finding_action.rejection_reason" class="w-full text-red-600 mt-1">
                                                                Rejection reason: {{ answer.finding_action.rejection_reason }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <p v-else class="text-sm text-gray-400 italic">No corrective action submitted for this finding.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section: All Inspection Answers (for reference) -->
                                <div>
                                    <h3 class="text-lg font-bold mb-3 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                        </svg>
                                        <span>Full Inspection Results</span>
                                        <span class="text-xs text-gray-500">({{ detailData.answers?.length || 0 }} answers)</span>
                                    </h3>

                                    <div v-if="groupedAnswers.length === 0" class="text-sm text-gray-500 italic p-4 bg-gray-50 rounded-lg">
                                        No answers found.
                                    </div>

                                    <div v-for="section in groupedAnswers" :key="section.id" class="mb-4">
                                        <div class="flex items-center gap-3 mb-2 pb-2 border-b-2 border-blue-200">
                                            <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs">
                                                {{ section.sort_order }}
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-bold text-gray-900">{{ section.name }}</h4>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <div v-for="a in section.answers" :key="a.id" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50">
                                                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0"
                                                    :class="a.answer === 1 ? 'bg-green-100 text-green-600' : a.answer === 0 ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-500'"
                                                >
                                                    <svg v-if="a.answer === 1" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    <svg v-else-if="a.answer === 0" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                                    </svg>
                                                </div>
                                                <div class="flex-1">
                                                    <p class="text-sm text-gray-700">{{ a.question?.question_text }}</p>
                                                    <p v-if="a.remarks" class="text-xs text-gray-500 mt-0.5">Remark: {{ a.remarks }}</p>
                                                </div>
                                                <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                                                    :class="a.answer === 1 ? 'bg-green-100 text-green-700' : a.answer === 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'">
                                                    {{ a.answer === 1 ? 'Pass' : a.answer === 0 ? 'Fail' : 'N/A' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- FOOTER -->
                        <div class="p-4 bg-gray-50 border-t text-right">
                            <Button
                                @click="emit('close')"
                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold transition shadow-md"
                            >
                                Close
                            </Button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>

    <!-- Image Preview Lightbox -->
    <Transition name="modal-fade">
        <div
            v-if="showImagePreview"
            class="fixed inset-0 bg-black/90 backdrop-blur-sm flex items-center justify-center z-[10000]"
            @click.self="closeImagePreview"
        >
            <Transition name="modal-scale" appear>
                <div class="relative max-w-4xl max-h-[90vh] mx-4">
                    <button @click="closeImagePreview" class="absolute -top-12 right-0 text-white hover:text-gray-300 text-2xl font-bold w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/10 transition-colors z-10">×</button>
                    <div class="relative">
                        <img :src="'/storage/' + selectedImage" alt="Photo" class="max-w-full max-h-[80vh] object-contain rounded-lg shadow-2xl" />
                        <div class="absolute bottom-0 left-0 right-0 bg-black/70 text-white p-4 rounded-b-lg">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-lg">Photo Evidence</h3>
                                <a :href="'/storage/' + selectedImage" target="_blank" rel="noopener noreferrer" class="text-blue-400 hover:text-blue-300 text-sm underline">Open full size</a>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.modal-scale-enter-active, .modal-scale-leave-active { transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
.modal-scale-enter-from, .modal-scale-leave-to { opacity: 0; transform: scale(0.92) translateY(20px); }
</style>