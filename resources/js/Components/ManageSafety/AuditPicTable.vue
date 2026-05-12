<script setup lang="ts">
import { ref, watch, onMounted } from "vue";
import axios from "axios";

const emit = defineEmits(["openCreatePicModal", "openDeletePicModal"]);

interface AuditPic {
    id: number;
    user_id: number;
    site_id: number;
    department_id: number;
    user: { id: number; name: string };
    site: { id: number; name: string };
    department: { id: number; name: string };
    created_at: string;
}

const picList = ref<AuditPic[]>([]);
const isLoading = ref(false);
const searchQuery = ref("");
const siteFilter = ref("all");
const departmentFilter = ref("all");
const sites = ref<{ id: number; name: string }[]>([]);
const departments = ref<{ id: number; name: string }[]>([]);

async function fetchPics() {
    isLoading.value = true;
    try {
        const params = new URLSearchParams();
        if (searchQuery.value) params.append("search", searchQuery.value);
        if (siteFilter.value !== "all") params.append("site_id", siteFilter.value);
        if (departmentFilter.value !== "all") params.append("department_id", departmentFilter.value);

        const res = await axios.get(`/safety/audit-pics?${params.toString()}`);
        picList.value = res.data.data;
    } catch (error) {
        console.error("Error fetching PICs:", error);
        picList.value = [];
    } finally {
        isLoading.value = false;
    }
}

async function fetchFilters() {
    try {
        const [sitesRes, deptsRes] = await Promise.all([
            axios.get("/api/sites"),
            axios.get("/api/departments"),
        ]);
        sites.value = sitesRes.data;
        departments.value = deptsRes.data.data;
    } catch (error) {
        console.error("Error fetching filter data:", error);
    }
}

function openDeletePicModal(pic: AuditPic) {
    emit("openDeletePicModal", pic);
}

watch([searchQuery, siteFilter, departmentFilter], () => {
    fetchPics();
});

onMounted(() => {
    fetchPics();
    fetchFilters();
});
</script>

<template>
    <div class="relative">
        <div class="absolute -top-3 left-2 z-10">
            <span class="bg-gray-100 text-black px-2 py-1 rounded-full text-xs font-semibold border shadow-md">
                PIC Assignments
            </span>
        </div>

        <div class="p-3 shadow-2xl shadow-opacity-60 bg-white rounded-xl border">
            <div class="flex space-x-4 justify-between mb-3">
                <div class="flex items-center gap-4">
                    <input
                        v-model="searchQuery"
                        class="w-72 bg-gray-100 text-black placeholder:text-gray-500 border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Search by user name..."
                    />
                    <select
                        v-model="siteFilter"
                        class="w-40 border border-gray-300 rounded-lg text-sm px-3 py-2 bg-white focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="all">All Sites</option>
                        <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
                    </select>
                    <select
                        v-model="departmentFilter"
                        class="w-44 border border-gray-300 rounded-lg text-sm px-3 py-2 bg-white focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="all">All Departments</option>
                        <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                    </select>
                </div>
                <div>
                    <button
                        @click="$emit('openCreatePicModal')"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition shadow-md flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Assign PIC
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto max-h-[500px] border border-gray-200 rounded-lg">
                <table class="min-w-full">
                    <thead class="sticky top-0 bg-gray-100 z-40">
                        <tr class="border-b border-gray-300 text-black">
                            <th class="text-left p-3 text-xs font-bold uppercase">No</th>
                            <th class="text-left p-3 text-xs font-bold uppercase">User</th>
                            <th class="text-left p-3 text-xs font-bold uppercase">Site</th>
                            <th class="text-left p-3 text-xs font-bold uppercase">Department</th>
                            <th class="text-left p-3 text-xs font-bold uppercase">Assigned At</th>
                            <th class="text-center p-3 text-xs font-bold uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="isLoading">
                            <td colspan="6" class="text-center p-6 text-gray-500">Loading...</td>
                        </tr>
                        <tr v-else-if="picList.length === 0">
                            <td colspan="6" class="text-center p-6 text-gray-500">No PIC assignments found.</td>
                        </tr>
                        <tr v-for="(pic, index) in picList" :key="pic.id" class="hover:bg-gray-50 transition">
                            <td class="p-3 text-sm">{{ index + 1 }}</td>
                            <td class="p-3 text-sm font-medium">{{ pic.user?.name || 'N/A' }}</td>
                            <td class="p-3 text-sm">{{ pic.site?.name || 'N/A' }}</td>
                            <td class="p-3 text-sm">{{ pic.department?.name || 'N/A' }}</td>
                            <td class="p-3 text-sm text-gray-500">{{ pic.created_at ? new Date(pic.created_at).toLocaleDateString('en-GB') : 'N/A' }}</td>
                            <td class="p-3 text-center">
                                <button
                                    @click="openDeletePicModal(pic)"
                                    class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-medium rounded-lg transition"
                                >
                                    Remove
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
