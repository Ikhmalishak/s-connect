<script setup lang="ts">
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Plus } from "lucide-vue-next";
import { Card } from "@/components/ui/card";
import CustomTooltip from "../CustomTooltip.vue";
import { ref, watch, onMounted } from "vue";
import axios from "axios";
import Button from "@/components/ui/button/Button.vue";

const emit = defineEmits(["openAuditDetailModal", "openCreateAuditModal", "openFailedItemModal"]);

interface AuditAnswer {
    id: number;
    audit_session_id: number;
    audit_question_id: number;
    answer: string;
    remark?: string;
    checked_at: string;
    created_at?: string;
    updated_at?: string;
}

interface Audit {
    id: number;
    audit_type_id: number;
    audit_type: any;
    department_id: number;
    department: any;
    site_id: number;
    site: any;
    date: string;
    user_id: number;
    status: string;
    remarks?: string;
    created_at?: string;
    updated_at?: string;
    answers: AuditAnswer[];
}

// Local data ref (table manages its own data)
const filteredAudits = ref<Audit[]>([]);
const isLoading = ref(false);

// Filter reactive variables
const searchQuery = ref("");
const limit = ref("50");
const statusFilter = ref("all");

function handleOpenAuditDetailModal(audit: Audit) {
    emit("openAuditDetailModal", audit);
}

function handleOpenFailedItemsModal(audit: Audit) {
    emit("openFailedItemModal", audit);
}

// Fetch sessions with filters from safety endpoint
async function fetchFilteredSessions() {
    isLoading.value = true;
    try {
        const params = new URLSearchParams();
        if (searchQuery.value) params.append("search", searchQuery.value);
        if (limit.value !== "all") params.append("limit", limit.value);
        if (statusFilter.value !== "all")
            params.append("status", statusFilter.value);

        const res = await axios.get(
            `/safety/audit-sessions?${params.toString()}`,
        );
        console.log(res.data.session);
        filteredAudits.value = res.data.session;
    } catch (error) {
        console.error("Error fetching filtered sessions:", error);
        filteredAudits.value = [];
    } finally {
        isLoading.value = false;
    }
}

// Watch for filter changes
watch([searchQuery, statusFilter, limit], () => {
    fetchFilteredSessions();
});

// Initial load
onMounted(() => {
    fetchFilteredSessions();
});
</script>

<template>
    <div class="relative">
        <!-- Badge -->
        <div class="absolute -top-3 left-2 z-10">
            <span
                class="bg-gray-100 text-black px-2 py-1 rounded-full text-xs font-semibold border shadow-md"
            >
                Audit List
            </span>
        </div>

        <Card class="p-3 shadow-2xl max-h-[700px] shadow-opacity-60">
            <div class="flex space-x-4 justify-between mb-2">
                <div class="flex items-center gap-4">
                    <div class="flex flex-row space-x-2">
                        <input
                            v-model="searchQuery"
                            class="w-400 bg-gray-300 text-black placeholder:text-black border-none rounded-lg text-sm"
                            placeholder="Search audit type, date..."
                        />
                    </div>
                    <div>
                        <Select v-model="statusFilter">
                            <SelectTrigger class="w-32">
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="all"
                                        >All Status</SelectItem
                                    >
                                    <SelectItem value="draft">Draft</SelectItem>
                                    <SelectItem value="submitted"
                                        >Submitted</SelectItem
                                    >
                                    <SelectItem value="failed"
                                        >Failed</SelectItem
                                    >
                                    <SelectItem value="approved"
                                        >Approved</SelectItem
                                    >
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div>
                        <Select v-model="limit">
                            <SelectTrigger>
                                <SelectValue placeholder="Select limit" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="50">50</SelectItem>
                                    <SelectItem value="100">100</SelectItem>
                                    <SelectItem value="200">200</SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <CustomTooltip :text="'New Audit'" position="top">
                            <Plus
                                class="w-9 h-9 cursor-pointer"
                                @click="$emit('openCreateAuditModal')"
                            />
                        </CustomTooltip>
                    </div>
                </div>
            </div>

            <div
                class="flex-1 overflow-y-auto max-h-[420px] border border-gray-300"
            >
                <table class="min-w-full">
                    <thead
                        class="sticky top-0 bg-gray-100 z-40 border border-b-gray-300"
                    >
                        <tr
                            class="border border-gray-300 font-black divide-x divide-gray-300 text-black"
                        >
                            <th
                                class="font-black text-black text-center bg-gray-100 p-3 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                No
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Name
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Department
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Site
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Audit Type
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Status
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="border border-gray-300 divide-x divide-gray-300"
                    >
                        <tr v-if="isLoading">
                            <td
                                colspan="4"
                                class="text-center p-4 text-gray-500"
                            >
                                Loading...
                            </td>
                        </tr>
                        <tr v-else-if="filteredAudits.length === 0">
                            <td
                                colspan="4"
                                class="text-center p-4 text-gray-500"
                            >
                                No audit sessions found.
                            </td>
                        </tr>
                        <tr
                            v-for="(audit, index) in filteredAudits"
                            class="text-center text-sm border border-gray-300 divide-x divide-gray-300 p-2"
                            :key="audit.id"
                        >
                            <td class="p-2 text-center">{{ index + 1 }}</td>
                            <td class="p-2">
                                <button
                                    @click="handleOpenAuditDetailModal(audit)"
                                    class="text-blue-600 hover:text-blue-800 underline"
                                >
                                    {{ audit.audit_type?.name }} -
                                    {{ audit.date }}
                                </button>
                            </td>
                            <td class="p-2">{{ audit.department?.name }}</td>
                            <td class="p-2">{{ audit.site?.name }}</td>
                            <td class="p-2">{{ audit.audit_type?.name }}</td>

                            <td class="p-2">
                                <span
                                    class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                    :class="
                                        audit.status === 'approved'
                                            ? 'bg-green-100 text-green-800'
                                            : audit.status === 'submitted'
                                              ? 'bg-blue-100 text-blue-800'
                                              : audit.status === 'failed'
                                                ? 'bg-red-100 text-red-800'
                                                : 'bg-gray-100 text-gray-800'
                                    "
                                >
                                    {{ audit.status }}
                                </span>
                            </td>
                            <td class="p-2 flex flex-row justify-center gap-2">
                                <CustomTooltip
                                    :text="'The container is still not completed'"
                                    position="top"
                                >
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        class="bg-purple-600 hover:bg-purple-700 text-white text-xs disabled:opacity-50 disabled:cursor-not-allowed"
                                        @click = "handleOpenFailedItemsModal(audit)"
                                    >
                                        Corrective Action
                                    </Button>
                                </CustomTooltip>
                                <CustomTooltip
                                    text="Download container report"
                                    position="top"
                                >
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        class="bg-purple-600 hover:bg-purple-700 text-white text-xs"
                                    >
                                        Edit
                                    </Button>
                                </CustomTooltip>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
