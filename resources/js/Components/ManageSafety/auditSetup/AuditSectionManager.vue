<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios";

interface AuditType { id: number; name: string; }
interface AuditSection {
    id: number; name: string; audit_type_id: number;
    sort_order: number; is_active: boolean; questions_count: number;
    audit_type: AuditType | null;
}

const sections = ref<AuditSection[]>([]);
const auditTypes = ref<AuditType[]>([]);
const isLoading = ref(false);
const showModal = ref(false);
const editingSection = ref<AuditSection | null>(null);
const filterTypeId = ref("all");

const form = ref({ audit_type_id: null as number | null, name: "", sort_order: 1, is_active: true });
const errorMsg = ref("");

async function fetchSections() {
    isLoading.value = true;
    try {
        const params = new URLSearchParams();
        if (filterTypeId.value !== "all") params.append("audit_type_id", filterTypeId.value);
        const res = await axios.get(`/safety/audit-setup/sections?${params.toString()}`);
        sections.value = res.data.data;
    } catch (e) { console.error(e); } finally { isLoading.value = false; }
}
async function fetchTypes() {
    try { const res = await axios.get("/safety/audit-setup/types"); auditTypes.value = res.data.data; } catch (e) { console.error(e); }
}

function openCreate() {
    editingSection.value = null;
    form.value = { audit_type_id: null, name: "", sort_order: 1, is_active: true };
    errorMsg.value = ""; showModal.value = true;
}
function openEdit(s: AuditSection) {
    editingSection.value = s;
    form.value = { audit_type_id: s.audit_type_id, name: s.name, sort_order: s.sort_order, is_active: s.is_active };
    errorMsg.value = ""; showModal.value = true;
}

async function submit() {
    if (!form.value.name.trim() || !form.value.audit_type_id) { errorMsg.value = "Name and Audit Type are required."; return; }
    isLoading.value = true; errorMsg.value = "";
    try {
        if (editingSection.value) {
            await axios.put(`/safety/audit-setup/sections/${editingSection.value.id}`, form.value);
        } else {
            await axios.post("/safety/audit-setup/sections", form.value);
        }
        showModal.value = false; await fetchSections();
    } catch (e: any) { errorMsg.value = e.response?.data?.message || "Failed to save."; } finally { isLoading.value = false; }
}

async function confirmDelete(s: AuditSection) {
    if (!confirm(`Delete section "${s.name}"?`)) return;
    try { await axios.delete(`/safety/audit-setup/sections/${s.id}`); await fetchSections(); } catch (e) { console.error(e); }
}

onMounted(() => { fetchSections(); fetchTypes(); });
</script>

<template>
    <div class="bg-white rounded-xl border shadow-sm">
        <div class="flex justify-between items-center p-4 border-b">
            <h2 class="text-lg font-bold text-gray-900">Sections</h2>
            <div class="flex gap-3 items-center">
                <select v-model="filterTypeId" @change="fetchSections" class="border border-gray-300 rounded-lg text-sm px-3 py-2">
                    <option value="all">All Types</option>
                    <option v-for="t in auditTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
                <button @click="openCreate" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Section
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50"><tr class="text-left text-xs font-bold text-gray-500 uppercase">
                    <th class="p-3">Order</th><th class="p-3">Name</th><th class="p-3">Audit Type</th><th class="p-3">Questions</th><th class="p-3">Status</th><th class="p-3 text-center">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-if="isLoading && sections.length === 0"><td colspan="6" class="p-6 text-center text-gray-500">Loading...</td></tr>
                    <tr v-else-if="sections.length === 0"><td colspan="6" class="p-6 text-center text-gray-500">No sections found.</td></tr>
                    <tr v-for="s in sections" :key="s.id" class="hover:bg-gray-50">
                        <td class="p-3 text-sm">{{ s.sort_order }}</td>
                        <td class="p-3 font-medium">{{ s.name }}</td>
                        <td class="p-3 text-sm text-gray-500">{{ s.audit_type?.name || 'N/A' }}</td>
                        <td class="p-3 text-sm text-gray-500">{{ s.questions_count }}</td>
                        <td class="p-3"><span class="px-2 py-1 text-xs font-medium rounded-full" :class="s.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'">{{ s.is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="p-3 text-center space-x-2">
                            <button @click="openEdit(s)" class="px-3 py-1.5 text-xs font-medium bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200">Edit</button>
                            <button @click="confirmDelete(s)" class="px-3 py-1.5 text-xs font-medium bg-red-100 text-red-700 rounded-lg hover:bg-red-200">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Teleport to="body">
            <Transition name="modal-fade">
                <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[9999] p-4" @click.self="showModal = false">
                    <Transition name="modal-scale" appear>
                        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden p-6">
                            <h3 class="text-lg font-bold mb-4">{{ editingSection ? 'Edit Section' : 'New Section' }}</h3>
                            <div v-if="errorMsg" class="p-3 bg-red-50 border border-red-200 rounded-md mb-4 text-sm text-red-800">{{ errorMsg }}</div>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Audit Type *</label>
                                    <select v-model="form.audit_type_id" :disabled="!!editingSection" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                        <option :value="null" disabled>Select</option>
                                        <option v-for="t in auditTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                    <input v-model="form.name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="e.g. General Safety" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order *</label>
                                    <input type="number" v-model.number="form.sort_order" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" v-model="form.is_active" id="sec-active" class="w-4 h-4 rounded border-gray-300 text-blue-600" />
                                    <label for="sec-active" class="text-sm text-gray-700">Active</label>
                                </div>
                            </div>
                            <div class="flex justify-end gap-3 mt-6">
                                <button @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                                <button @click="submit" :disabled="isLoading" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:bg-gray-400">{{ editingSection ? 'Update' : 'Create' }}</button>
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
