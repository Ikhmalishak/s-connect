<script setup lang="ts">
import AdminAuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Card } from "@/components/ui/card";
import {
    Breadcrumb,
    BreadcrumbPage,
    BreadcrumbSeparator,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
} from "@/components/ui/breadcrumb";
import { ref, onMounted, computed, watch } from "vue";
import axios from "axios";

interface Log {
    id: number;
    causer_id: number | null;
    description: string;
    created_at: string;
    causer_name: string;
    causer_email: string;
}

const logs = ref<Log[]>([]);
const loading = ref(true);
const errorMessage = ref("");
const currentTime = ref(new Date());
const search = ref("");
const sortOrder = ref("desc");

const formattedDate = computed(() =>
    currentTime.value.toLocaleDateString("en-GB", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
    })
);
const formattedTime = computed(() =>
    currentTime.value.toLocaleTimeString("en-GB")
);

const fetchLogs = async () => {
    loading.value = true;
    try {
        const res = await axios.get("/admin/get-system-log-list", {
            params: {
                search: search.value,
                sort: sortOrder.value,
                limit: 20,
            },
        });
        logs.value = res.data.data || [];
    } catch (err) {
        console.error(err);
        errorMessage.value = "Failed to load logs.";
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchLogs();
    setInterval(() => (currentTime.value = new Date()), 1000);
});

watch([search, sortOrder], fetchLogs);
</script>

<template>
    <AdminAuthenticatedLayout>
        <!-- Breadcrumb -->
        <template #breadcrumb>
            <Breadcrumb>
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink href="/" class="text-gray-500 hover:text-gray-900">
                            Visitor Management System
                        </BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbPage class="text-gray-900">System Logs</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>
        </template>

        <!-- Header -->
        <Card
            class="shadow-lg shadow-opacity-30 p-2 mb-4 text-2xl font-bold flex items-center justify-between bg-gray-100"
        >
            <div class="flex flex-row items-center">
                <img src="/assets/ss1.png" class="h-12 w-12" alt="" />
                <div>Visitor Management System</div>
            </div>
            <div class="flex flex-row space-x-4 text-base font-normal text-gray-600 text-right">
                <div>{{ formattedDate }}</div>
                <div>{{ formattedTime }}</div>
            </div>
        </Card>

        <!-- Filters -->
        <Card class="mb-6 bg-white border border-gray-200 shadow-sm rounded-xl p-4">
            <div class="flex flex-wrap gap-4 items-end">
                <div class="flex flex-col flex-1 min-w-[200px]">
                    <label class="text-xs text-gray-500 mb-1">Search Logs</label>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search logs..."
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>

                <div class="flex flex-col min-w-[150px]">
                    <label class="text-xs text-gray-500 mb-1">Sort Order</label>
                    <select
                        v-model="sortOrder"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white"
                    >
                        <option value="desc">Newest First</option>
                        <option value="asc">Oldest First</option>
                    </select>
                </div>
            </div>
        </Card>

        <!-- Logs Table -->
        <Card class="bg-white border border-gray-200 shadow-sm rounded-xl overflow-hidden">
            <div class="flex items-center justify-between p-5 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Activity Logs</h2>
                <div v-if="loading" class="text-blue-600 flex items-center text-sm">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Loading...
                </div>
            </div>

            <div v-if="errorMessage" class="px-5 py-3 text-red-600 text-sm bg-red-50 border-b border-red-200">
                {{ errorMessage }}
            </div>

            <div class="max-h-[500px] overflow-y-auto overflow-x-auto">
                <table class="w-full min-w-[1000px] border-collapse table-fixed">
                    <thead class="bg-gray-50 sticky top-0 z-10 shadow-sm">
                        <tr class="text-left text-gray-700 text-xs font-medium uppercase tracking-wider">
                            <th class="py-3 px-5 w-16">#</th>
                            <th class="py-3 px-5">User Name</th>
                            <th class="py-3 px-5">User Email</th>
                            <th class="py-3 px-5">Description</th>
                            <th class="py-3 px-5">Created At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr
                            v-for="(log, index) in logs"
                            :key="log.id"
                            class="hover:bg-gray-50 text-sm transition-colors"
                        >
                            <td class="py-3 px-5 text-gray-500">{{ index + 1 }}</td>
                            <td class="py-3 px-5 font-medium text-gray-900">{{ log.causer_name ?? "N/A" }}</td>
                            <td class="py-3 px-5 text-gray-600">{{ log.causer_email ?? "N/A" }}</td>
                            <td class="py-3 px-5 text-gray-700">{{ log.description }}</td>
                            <td class="py-3 px-5 text-gray-500">
                                {{ new Date(log.created_at).toLocaleString("en-GB") }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!loading && logs.length === 0" class="text-center text-gray-500 py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                </svg>
                <p class="text-sm">No logs found</p>
            </div>
        </Card>
    </AdminAuthenticatedLayout>
</template>
