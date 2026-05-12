<script setup lang="ts">
import { ref, onMounted, watch } from "vue";
import axios from "axios";

interface AuditType { id: number; name: string; }
interface AuditSection { id: number; name: string; audit_type_id: number; audit_type: AuditType | null; }
interface AuditQuestion {
    id: number; audit_section_id: number; question_text: string;
    is_mandatory: boolean; is_active: boolean; sort_order: number;
    section: AuditSection | null;
}

const questions = ref<AuditQuestion[]>([]);
const auditTypes = ref<AuditType[]>([]);
const sections = ref<AuditSection[]>([]);
const isLoading = ref(false);
const showModal = ref(false);
const editingQuestion = ref<AuditQuestion | null>(null);
const filterTypeId = ref("all");

const form = ref({ audit_section_id: null as number | null, question_text: "", is_mandatory: true, is_active: true, sort_order: 1 });
const errorMsg = ref("");

// Filtered sections based on selected type
const filteredSections = ref<AuditSection[]>([]);
watch(() => form.value.audit_section_id, () => { /* noop */ });

async function fetchQuestions() {
    isLoading.value = true;
    try {
        const params = new URLSearchParams();
        if (filterTypeId.value !== "all") params.append("audit_type_id", filterTypeId.value);
        const res = await axios.get(`/safety/audit-setup/questions?${params.toString()}`);
        questions.value = res.data.data;
    } catch (e) { console.error(e); } finally { isLoading.value = false; }
}
async function fetchTypes() {
    try { const res = await axios.get("/safety/audit-setup/types"); auditTypes.value = res.data.data; } catch (e) { console.error(e); }
}
async function fetchSections() {
    try { const res = await axios.get("/safety/audit-setup/sections"); sections.value = res.data.data; } catch (e) { console.error(e); }
}

function updateFilteredSections() {
    if (filterTypeId.value !== "all") {
        filteredSections.value = sections.value.filter(s => s.audit_type_id === Number(filterTypeId.value));
    } else {
        filteredSections.value = sections.value;
    }
}

function openCreate() {
    editingQuestion.value = null;
    form.value = { audit_section_id: null, question_text: "", is_mandatory: true, is_active: true, sort_order: 1 };
    errorMsg.value = ""; showModal.value = true;
}
function openEdit(q: AuditQuestion) {
    editingQuestion.value = q;
    form.value = {
        audit_section_id: q.audit_section_id,
        question_text: q.question_text,
        is_mandatory: q.is_mandatory,
        is_active: q.is_active,
        sort_order: q.sort_order,
    };
    errorMsg.value = ""; showModal.value = true;
}

async function submit() {
    if (!form.value.question_text.trim() || !form.value.audit_section_id) {
        errorMsg.value = "Question text and Section are required."; return;
    }
    isLoading.value = true; errorMsg.value = "";
    try {
        if (editingQuestion.value) {
            await axios.put(`/safety/audit-setup/questions/${editingQuestion.value.id}`, form.value);
        } else {
            await axios.post("/safety/audit-setup/questions", form.value);
        }
        showModal.value = false; await fetchQuestions();
    } catch (e: any) { errorMsg.value = e.response?.data?.message || "Failed to save."; } finally { isLoading.value = false; }
}

async function confirmDelete(q: AuditQuestion) {
    if (!confirm(`Delete question?`)) return;
    try { await axios.delete(`/safety/audit-setup/questions/${q.id}`); await fetchQuestions(); } catch (e) { console.error(e); }
}

onMounted(() => { fetchQuestions(); fetchTypes(); fetchSections(); });
</script>

<template>
    <div class="bg-white rounded-xl border shadow-sm">
        <div class="flex justify-between items-center p-4 border-b">
            <h2 class="text-lg font-bold text-gray-900">Questions</h2>
            <div class="flex gap-3 items-center">
                <select v-model="filterTypeId" @change="updateFilteredSections; fetchQuestions()" class="border border-gray-300 rounded-lg text-sm px-3 py-2">
                    <option value="all">All Types</option>
                    <option v-for="t in auditTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
                <button @click="openCreate" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Question
                </button>
            </div>
        </div>
        <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50 sticky top-0"><tr class="text-left text-xs font-bold text-gray-500 uppercase">
                    <th class="p-3">#</th><th class="p-3">Question</th><th class="p-3">Section</th><th class="p-3">Type</th><th class="p-3">Mandatory</th><th class="p-3">Status</th><th class="p-3 text-center">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-if="isLoading && questions.length === 0"><td colspan="7" class="p-6 text-center text-gray-500">Loading...</td></tr>
                    <tr v-else-if="questions.length === 0"><td colspan="7" class="p-6 text-center text-gray-500">No questions found.</td></tr>
                    <tr v-for="q in questions" :key="q.id" class="hover:bg-gray-50">
                        <td class="p-3 text-sm">{{ q.sort_order }}</td>
                        <td class="p-3 font-medium max-w-xs truncate">{{ q.question_text }}</td>
                        <td class="p-3 text-sm text-gray-500">{{ q.section?.name || 'N/A' }}</td>
                        <td class="p-3 text-sm text-gray-500">{{ q.section?.audit_type?.name || 'N/A' }}</td>
                        <td class="p-3"><span class="px-2 py-1 text-xs font-medium rounded-full" :class="q.is_mandatory ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-600'">{{ q.is_mandatory ? 'Yes' : 'No' }}</span></td>
                        <td class="p-3"><span class="px-2 py-1 text-xs font-medium rounded-full" :class="q.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'">{{ q.is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="p-3 text-center space-x-2">
                            <button @click="openEdit(q)" class="px-3 py-1.5 text-xs font-medium bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200">Edit</button>
                            <button @click="confirmDelete(q)" class="px-3 py-1.5 text-xs font-medium bg-red-100 text-red-700 rounded-lg hover:bg-red-200">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Teleport to="body">
            <Transition name="modal-fade">
                <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[9999] p-4" @click.self="showModal = false">
                    <Transition name="modal-scale" appear>
                        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden p-6">
                            <h3 class="text-lg font-bold mb-4">{{ editingQuestion ? 'Edit Question' : 'New Question' }}</h3>
                            <div v-if="errorMsg" class="p-3 bg-red-50 border border-red-200 rounded-md mb-4 text-sm text-red-800">{{ errorMsg }}</div>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Section *</label>
                                    <select v-model="form.audit_section_id" :disabled="!!editingQuestion" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                        <option :value="null" disabled>Select Section</option>
                                        <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.name }} ({{ s.audit_type?.name || 'N/A' }})</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Question Text *</label>
                                    <textarea v-model="form.question_text" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Enter question..."></textarea>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order *</label>
                                        <input type="number" v-model.number="form.sort_order" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                                    </div>
                                    <div class="flex flex-col gap-3 pt-6">
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" v-model="form.is_mandatory" id="q-mandatory" class="w-4 h-4 rounded border-gray-300 text-blue-600" />
                                            <label for="q-mandatory" class="text-sm text-gray-700">Mandatory</label>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" v-model="form.is_active" id="q-active" class="w-4 h-4 rounded border-gray-300 text-blue-600" />
                                            <label for="q-active" class="text-sm text-gray-700">Active</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end gap-3 mt-6">
                                <button @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                                <button @click="submit" :disabled="isLoading" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:bg-gray-400">{{ editingQuestion ? 'Update' : 'Create' }}</button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.modal-scale-enter-active, .modal-scale-leave-active { transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
.modal-scale-enter-from, .modal-scale-leave-to { opacity: 0; transform: scale(0.92) translateY(20px); }
</style>
