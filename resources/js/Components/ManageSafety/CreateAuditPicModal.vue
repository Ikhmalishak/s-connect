<script setup lang="ts">
import { ref, watch } from "vue";
import axios from "axios";

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits(["close", "saved"]);

const isLoading = ref(false);
const errorMessage = ref("");
const successMessage = ref("");

// Form data
const users = ref<{ id: number; name: string; site_id: number; department_id: number; site: { id: number; name: string }; department: { id: number; name: string } }[]>([]);
const sites = ref<{ id: number; name: string }[]>([]);
const departments = ref<{ id: number; name: string }[]>([]);

const form = ref({
    user_id: null as number | null,
    site_id: null as number | null,
    department_id: null as number | null,
});

async function fetchFormData() {
    try {
        const res = await axios.get("/safety/audit-pics/form-data");
        users.value = res.data.users;
        sites.value = res.data.sites;
        departments.value = res.data.departments;
    } catch (error) {
        console.error("Error fetching form data:", error);
    }
}

// Filtered users based on selected site & department
const filteredUsers = ref<typeof users.value>([]);

watch([() => form.value.site_id, () => form.value.department_id], () => {
    if (form.value.site_id && form.value.department_id) {
        filteredUsers.value = users.value.filter(
            (u) => u.site_id === form.value.site_id && u.department_id === form.value.department_id
        );
    } else if (form.value.site_id) {
        filteredUsers.value = users.value.filter((u) => u.site_id === form.value.site_id);
    } else if (form.value.department_id) {
        filteredUsers.value = users.value.filter((u) => u.department_id === form.value.department_id);
    } else {
        filteredUsers.value = users.value;
    }
});

// Filter sites/departments based on user selection
const filteredSites = ref<typeof sites.value>([]);
const filteredDepartments = ref<typeof departments.value>([]);

watch(form.value.user_id ? [() => form.value.user_id] : [], () => {
    // No auto-filter on user change - that's the opposite direction
}, { immediate: false });

async function submit() {
    if (!form.value.user_id || !form.value.site_id || !form.value.department_id) {
        errorMessage.value = "Please select a user, site, and department.";
        return;
    }

    isLoading.value = true;
    errorMessage.value = "";
    successMessage.value = "";

    try {
        await axios.post("/safety/audit-pics", {
            user_id: form.value.user_id,
            site_id: form.value.site_id,
            department_id: form.value.department_id,
        });
        successMessage.value = "PIC assigned successfully!";
        setTimeout(() => {
            emit("saved");
            emit("close");
            resetForm();
        }, 1500);
    } catch (error: any) {
        errorMessage.value = error.response?.data?.message || "Failed to assign PIC.";
    } finally {
        isLoading.value = false;
    }
}

function resetForm() {
    form.value = { user_id: null, site_id: null, department_id: null };
    errorMessage.value = "";
    successMessage.value = "";
}

watch(
    () => props.show,
    (value) => {
        if (value) {
            fetchFormData();
            resetForm();
        }
    }
);
</script>

<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div
                v-if="show"
                class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[9999] p-4"
                @click.self="emit('close')"
            >
                <Transition name="modal-scale" appear>
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-hidden">
                        <!-- HEADER -->
                        <div class="flex justify-between items-center p-6 border-b bg-gray-50">
                            <h2 class="text-xl font-bold text-gray-900">Assign PIC (Person In Charge)</h2>
                            <button
                                @click="emit('close')"
                                class="text-gray-400 hover:text-gray-600 text-3xl w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-200 transition"
                            >×</button>
                        </div>

                        <!-- BODY -->
                        <div class="p-6 space-y-4">
                            <!-- Error -->
                            <div v-if="errorMessage" class="p-3 bg-red-50 border border-red-200 rounded-md">
                                <p class="text-red-800 text-sm">{{ errorMessage }}</p>
                            </div>

                            <!-- Success -->
                            <div v-if="successMessage" class="p-3 bg-green-50 border border-green-200 rounded-md">
                                <p class="text-green-800 text-sm">{{ successMessage }}</p>
                            </div>

                            <!-- Site -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Site *</label>
                                <select
                                    v-model="form.site_id"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                    <option :value="null" disabled>Select Site</option>
                                    <option v-for="s in sites" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>

                            <!-- Department -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Department *</label>
                                <select
                                    v-model="form.department_id"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                    <option :value="null" disabled>Select Department</option>
                                    <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                            </div>

                            <!-- User filtered by site/department -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">User *</label>
                                <select
                                    v-model="form.user_id"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                    <option :value="null" disabled>Select User</option>
                                    <option v-for="u in filteredUsers" :key="u.id" :value="u.id">
                                        {{ u.name }} ({{ u.site?.name || 'No Site' }} - {{ u.department?.name || 'No Dept' }})
                                    </option>
                                </select>
                                <p v-if="filteredUsers.length === 0 && form.site_id && form.department_id" class="text-xs text-amber-600 mt-1">
                                    No users found for this site and department combination.
                                </p>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div class="p-4 bg-gray-50 border-t flex justify-end gap-3">
                            <button
                                @click="emit('close')"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                            >Cancel</button>
                            <button
                                @click="submit"
                                :disabled="isLoading"
                                class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center gap-2"
                            >
                                <div v-if="isLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                Assign PIC
                            </button>
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
