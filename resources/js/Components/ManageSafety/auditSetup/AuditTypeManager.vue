<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios";

interface AuditType {
    id: number;
    name: string;
    is_active: boolean;
    sections_count: number;
    created_at: string;
}

const types = ref<AuditType[]>([]);
const isLoading = ref(false);
const showModal = ref(false);
const editingType = ref<AuditType | null>(null);
const formName = ref("");
const formActive = ref(true);
const errorMsg = ref("");

async function fetchTypes() {
    isLoading.value = true;
    try {
        const res = await axios.get("/safety/audit-setup/types");
        types.value = res.data.data;
    } catch (e) {
        console.error(e);
    } finally {
        isLoading.value = false;
    }
}

function openCreate() {
    editingType.value = null;
    formName.value = "";
    formActive.value = true;
    errorMsg.value = "";
    showModal.value = true;
}

function openEdit(type: AuditType) {
    editingType.value = type;
    formName.value = type.name;
    formActive.value = type.is_active;
    errorMsg.value = "";
    showModal.value = true;
}

async function submit() {
    if (!formName.value.trim()) {
        errorMsg.value = "Name is required.";
        return;
    }
    isLoading.value = true;
    errorMsg.value = "";
    try {
        if (editingType.value) {
            await axios.put(`/safety/audit-setup/types/${editingType.value.id}`, {
                name: formName.value,
                is_active: formActive.value,
            });
        } else {
            await axios.post("/safety/audit-setup/types", {
                name: formName.value,
                is_active: formActive.value,
            });
        }
        showModal.value = false;
        await fetchTypes();
    } catch (e: any) {
        errorMsg.value = e.response?.data?.message || "Failed to save.";
    } finally {
        isLoading.value = false;
    }
}

async function confirmDelete(type: AuditType) {
    if (!confirm(`Delete "${type.name}"? This will also delete all its sections and questions.`)) return;
    try {
        await axios.delete(`/safety/audit-setup/types/${type.id}`);
        await fetchTypes();
    } catch (e) {
        console.error(e);
    }
}

onMounted(fetchTypes);
</script>

<template>
    <div class="bg-white rounded-xl border shadow-sm">
        <div class="flex justify-between items-center p-4 border-b">
            <h2 class="text-lg font-bold text-gray-900">Audit Types</h2>
            <button @click="openCreate" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Audit Type
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-bold text-gray-500 uppercase">
                        <th class="p-3">Name</th>
                        <th class="p-3">Sections</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-if="isLoading && types.length === 0"><td colspan="4" class="p-6 text-center text-gray-500">Loading...</td></tr>
                    <tr v-else-if="types.length === 0"><td colspan="4" class="p-6 text-center text-gray-500">No audit types yet.</td></tr>
                    <tr v-for="type in types" :key="type.id" class="hover:bg-gray-50">
                        <td class="p-3 font-medium">{{ type.name }}</td>
                        <td class="p-3 text-sm text-gray-500">{{ type.sections_count }}</td>
                        <td class="p-3">
                            <span class="px-2 py-1 text-xs font-medium rounded-full" :class="type.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'">
                                {{ type.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="p-3 text-center space-x-2">
                            <button @click="openEdit(type)" class="px-3 py-1.5 text-xs font-medium bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition">Edit</button>
                            <button @click="confirmDelete(type)" class="px-3 py-1.5 text-xs font-medium bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <Transition name="modal-fade">
                <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[9999] p-4" @click.self="showModal = false">
                    <Transition name="modal-scale" appear>
                        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
                            <div class="p-6">
                                <h3 class="text-lg font-bold mb-4">{{ editingType ? 'Edit Audit Type' : 'New Audit Type' }}</h3>
                                <div v-if="errorMsg" class="p-3 bg-red-50 border border-red-200 rounded-md mb-4 text-sm text-red-800">{{ errorMsg }}</div>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                        <input v-model="formName" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="e.g. Safety Inspection" />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" v-model="formActive" id="type-active" class="w-4 h-4 rounded border-gray-300 text-blue-600" />
                                        <label for="type-active" class="text-sm text-gray-700">Active</label>
                                    </div>
                                </div>
                                <div class="flex justify-end gap-3 mt-6">
                                    <button @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                                    <button @click="submit" :disabled="isLoading" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:bg-gray-400">
                                        {{ editingType ? 'Update' : 'Create' }}
                                    </button>
                                </div>
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
