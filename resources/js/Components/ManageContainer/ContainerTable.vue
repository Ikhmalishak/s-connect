<script setup lang="ts">
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Plus } from "lucide-vue-next";
import { Card } from "@/components/ui/card";
import CustomTooltip from "../CustomTooltip.vue";
import ViewContainerDetailsModal from "./ViewContainerDetailsModal.vue";
import axios from "axios";
import { Button } from "@/components/ui/button";
import { ref, watch, computed } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();

const emit = defineEmits([
    "openCreateContainerModal",
    "openContainerInspectionModal",
    "openViewInspectionModal",
    "openCreateContainerRecordModal",
    "openViewContainerRecordModal",
    "openContainerSecurityCheckingModal",
    "openViewContainerSecurityCheckingModal",
    "updateContainers",
]);

interface Container {
    id: number;
    transport_type: string;
    transport_number: string;
    inspection: {
        id: number;
        status: string;
    } | null;
    photo: any;
    status: string;
    stage: string;
    created_at?: string;
    shipment_date?: string;
}

const props = defineProps<{
    containers: Container[];
}>();

// Get user permissions
const userPermissions = computed(() => {
    return (page.props as any).auth?.user?.permissions || [];
});

// Permission check functions
const canDoInspection = computed(() => userPermissions.value.includes('container.quality_approve'));
const canCreateRecord = computed(() => userPermissions.value.includes('container.warehouse_approve'));
const canDoSecurityCheck = computed(() => userPermissions.value.includes('container.security_approve'));
const canCreateContainer = computed(() => userPermissions.value.includes('container.shipping_approve'));

// Filter reactive variables
const searchQuery = ref("");
const limit = ref("50");
const statusFilter = ref("all");
let searchTimeout: any = null;

// Modal state
const showContainerDetailsModal = ref(false);
const selectedContainer = ref(null);

async function createInspection(containerId) {
    console.log("Create Inspection clicked", containerId);

    const res = await axios.post("/containers/create-inspection", {
        shipment_transport_id: containerId,
    });

    emit("openContainerInspectionModal", containerId);
}

// Debounced search function
function debouncedSearch() {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        fetchFilteredContainers();
    }, 300);
}

// Fetch containers with filters
async function fetchFilteredContainers() {
    try {
        const params = new URLSearchParams();
        if (searchQuery.value) params.append("search", searchQuery.value);
        if (limit.value !== "all") params.append("limit", limit.value);
        if (statusFilter.value !== "all")
            params.append("status", statusFilter.value);

        const res = await axios.get(`/containers?${params.toString()}`);
        // Update parent component's containers
        // This assumes the parent component has a reactive containers array
        // We'll need to emit an event to update it
        emit("updateContainers", res.data.data);
    } catch (error) {
        console.error("Error fetching filtered containers:", error);
    }
}

// Watch for filter changes
watch([limit, statusFilter], () => {
    fetchFilteredContainers();
});

function capitalizeFirstLetter(string) {
  if (string.length === 0) { // Handle empty strings
    return "";
  }
  // Get the first character and uppercase it
  const firstLetter = string.charAt(0).toUpperCase();
  // Get the rest of the string
  const restOfString = string.slice(1);
  // Combine them
  return firstLetter + restOfString;
}

function formatDate(dateString) {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString();
}

function openContainerDetails(container) {
    selectedContainer.value = container;
    showContainerDetailsModal.value = true;
}

</script>

<template>
    <div class="relative">
        <!-- Badge -->
        <div class="absolute -top-3 left-2 z-10">
            <span
                class="bg-gray-100 text-black px-2 py-1 rounded-full text-xs font-semibold border shadow-md"
            >
                Container List
            </span>
        </div>

        <Card class="p-3 shadow-2xl max-h-[700px] shadow-opacity-60">
            <div class="flex space-x-4 justify-between mb-2">
                <div class="flex items-center gap-4">
                    <div class="flex flex-row space-x-2">
                        <input
                            v-model="searchQuery"
                            @input="debouncedSearch"
                            class="w-400 bg-gray-300 text-black placeholder:text-black border-none rounded-lg text-sm"
                            placeholder="Search transport number, type, SKU..."
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
                                    <SelectItem value="in_progress"
                                        >In Progress</SelectItem
                                    >
                                    <SelectItem value="completed"
                                        >Completed</SelectItem
                                    >
                                    <SelectItem value="failed"
                                        >Failed</SelectItem
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
                        <CustomTooltip :text="canCreateContainer ? 'New Container' : 'Create Container (Shipping Only)'" position="top">
                            <Plus
                                class="w-9 h-9"
                                :class="[
                                    canCreateContainer
                                        ? 'cursor-pointer text-black hover:text-blue-600'
                                        : 'cursor-not-allowed text-gray-400'
                                ]"
                                @click="canCreateContainer ? $emit('openCreateContainerModal') : null"
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
                                Transport Type
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Transport Number
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Date
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Container/Truck Checking
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Container/Truck Report
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Security Outboarding Checking
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Container Status
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Stage
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="border border-gray-300 divide-x divide-gray-300"
                    >
                        <tr
                            v-for="(container, index) in containers"
                            :key="container.id"
                            class="text-center text-sm border border-gray-300 divide-x divide-gray-300 p-2"
                            :class="{
                                'bg-blue-50': container.status === 'in_progress',
                                'bg-red-50': container.status === 'failed',
                                'bg-green-200': container.status === 'completed'
                            }"
                        >
                            <td class="p-2">{{ index + 1 }}</td>
                            <td class="p-2">{{ container.transport_type }}</td>
                            <td class="p-2">
                                <button
                                    @click="openContainerDetails(container)"
                                    class="text-blue-600 hover:text-blue-800 underline"
                                >
                                    {{ container.transport_number }}
                                </button>
                            </td>
                            <td class="p-2">
                                {{ formatDate(container.created_at || container.shipment_date) }}
                            </td>
                            <td class="p-2">
                                <CustomTooltip
                                    v-if="container.inspection === null || container.inspection.status === 'pending'"
                                    :text="canDoInspection ? 'Start container inspection' : 'Requires Quality department permission'"
                                    position="top"
                                >
                                    <Button
                                        v-if="
                                            container.inspection === null ||
                                            container.inspection.status ===
                                                'pending'
                                        "
                                        variant="outline"
                                        :class="[
                                            'text-white',
                                            canDoInspection
                                                ? 'bg-blue-600 hover:bg-blue-700'
                                                : 'bg-gray-400 cursor-not-allowed'
                                        ]"
                                        :disabled="!canDoInspection"
                                        @click="canDoInspection ? createInspection(container.id) : null"
                                    >
                                        {{ canDoInspection ? 'Start Inspection' : 'Inspection (Quality Only)' }}
                                    </Button>
                                </CustomTooltip>

                                <Button
                                    v-else-if="
                                        container.inspection &&
                                        ['passed', 'failed'].includes(
                                            container.inspection.status
                                        )
                                    "
                                    variant="outline"
                                    class="bg-yellow-500 text-white"
                                    @click="
                                        $emit(
                                            'openViewInspectionModal',
                                            container.id
                                        )
                                    "
                                >
                                    View Inspection
                                </Button>
                            </td>

                            <td class="p-2">
                                <CustomTooltip
                                    v-if="container.photo && container.photo.length === 0"
                                    :text="canCreateRecord ? 'Upload loading photos and documentation' : 'Requires Warehouse department permission'"
                                    position="top"
                                >
                                    <Button
                                        v-if="
                                            container.photo &&
                                            container.photo.length === 0
                                        "
                                        variant="outline"
                                        :class="[
                                            'text-white w-[150px]',
                                            container.stage === 'container_loading_report' && canCreateRecord
                                                ? 'bg-green-600 hover:bg-green-700'
                                                : 'bg-gray-400 cursor-not-allowed',
                                        ]"
                                        :disabled="container.stage !== 'container_loading_report' || !canCreateRecord"
                                        @click="
                                            container.stage === 'container_loading_report' && canCreateRecord
                                                ? $emit('openCreateContainerRecordModal', container.id)
                                                : null
                                        "
                                    >
                                        {{ canCreateRecord ? 'Create Record' : 'Report (Warehouse Only)' }}
                                    </Button>
                                </CustomTooltip>
                                <Button
                                    v-else
                                    class="text-white w-[150px] bg-blue-600 hover:bg-blue-700"
                                    @click="
                                        $emit(
                                            'openViewContainerRecordModal',
                                            container.id
                                        )
                                    "
                                >
                                    View Report
                                </Button>
                            </td>

                            <td class="p-2">
                                <Button
                                    v-if="container.status === 'completed'"
                                    variant="outline"
                                    class="bg-green-600 text-white"
                                    @click="
                                        $emit(
                                            'openViewContainerSecurityCheckingModal',
                                            container.id
                                        )
                                    "
                                    >View Security Check</Button
                                >

                                <CustomTooltip
                                    v-else-if="container.stage === 'onboarding_ready'"
                                    :text="canDoSecurityCheck ? 'Complete final security check for onboarding' : 'Requires Security department permission'"
                                    position="top"
                                >
                                    <Button
                                        v-if="container.stage === 'onboarding_ready'"
                                        variant="outline"
                                        :class="[
                                            'text-white',
                                            canDoSecurityCheck
                                                ? 'bg-blue-600 hover:bg-blue-700'
                                                : 'bg-gray-400 cursor-not-allowed'
                                        ]"
                                        :disabled="!canDoSecurityCheck"
                                        @click="canDoSecurityCheck ? $emit('openContainerSecurityCheckingModal', container.id) : null"
                                    >
                                        {{ canDoSecurityCheck ? 'Complete Onboarding' : 'Security Check (Security Only)' }}
                                    </Button>
                                </CustomTooltip>

                                <Button
                                    v-else
                                    variant="outline"
                                    class="bg-gray-400 text-white cursor-not-allowed"
                                    disabled
                                >
                                    Security Checking
                                </Button>
                            </td>
                            <td class="p-2">{{ capitalizeFirstLetter(container.status.replace(/_/g," ")) }}</td>
                            <td class="p-2">{{ capitalizeFirstLetter(container.stage.replace(/_/g," ")) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>

        <!-- Container Details Modal -->
        <ViewContainerDetailsModal
            v-model:show="showContainerDetailsModal"
            :container="selectedContainer"
            @close="showContainerDetailsModal = false"
        />
    </div>
</template>
