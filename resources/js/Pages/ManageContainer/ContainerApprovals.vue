<template>
    <AuthenticatedLayout>
        <template #breadcrumb>
            <Breadcrumb>
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink href="/"
                            >Container Inspection Management
                            System</BreadcrumbLink
                        >
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbPage>Container Approvals</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>
        </template>

        <Head title="Container Approvals" />

        <Card
            class="shadow-lg shadow-opacity-30 p-2 mb-4 text-2xl font-bold flex items-center justify-between bg-gray-100"
        >
            <div class="flex flex-row items-center">
                <img src="/assets/ss1.png" class="h-12 w-12" alt="" />
                <div>Container Inspection Management System</div>
            </div>
            <div class="flex flex-row items-center gap-10">
                <div
                    class="flex flex-row space-x-4 text-base font-normal text-gray-600 text-right"
                >
                    <div>{{ formattedDate }}</div>
                    <div>{{ formattedTime }}</div>
                </div>
            </div>
        </Card>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <Card class="p-4 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Pending Approvals</p>
                        <p class="text-2xl font-bold text-blue-600">{{ stats.pending }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </Card>

            <Card class="p-4 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Approved</p>
                        <p class="text-2xl font-bold text-green-600">{{ stats.approved }}</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </Card>

            <Card class="p-4 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Rejected</p>
                        <p class="text-2xl font-bold text-red-600">{{ stats.rejected }}</p>
                    </div>
                    <div class="p-3 bg-red-100 rounded-full">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                </div>
            </Card>
        </div>

        <div class="relative">
            <!-- Badge -->
            <div class="absolute -top-3 left-2 z-10">
                <span
                    class="bg-gray-100 text-black px-2 py-1 rounded-full text-xs font-semibold border shadow-md"
                >
                    {{
                        selectedStatus === "all"
                            ? "All Approvals"
                            : selectedStatus.charAt(0).toUpperCase() +
                              selectedStatus.slice(1) +
                              " Approvals"
                    }}
                </span>
            </div>

            <Card class="p-3 shadow-2xl max-h-[700px] shadow-opacity-60">
                <div class="flex flex-col space-y-4 mb-4">
                    <!-- Filters Row -->
                    <div class="flex flex-wrap items-center gap-4">
                        <div v-if="isSuperAdmin" class="flex items-center gap-2">
                            <div>Departments :</div>
                            <select
                                v-model="selectedDepartment"
                                @change="fetchApprovals"
                                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 px-4 py-2"
                            >
                                <option value="">All</option>
                                <option value="warehouse">Warehouse</option>
                                <option value="shipping">Shipping</option>
                                <option value="quality">Quality</option>
                                <option value="security">Security</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2">
                            <div>Status :</div>
                            <select
                                v-model="selectedStatus"
                                @change="fetchApprovals"
                                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 px-3 py-2"
                            >
                                <option value="all">All</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                    </div>

                    <!-- Results Summary -->
                    <div v-if="!loading" class="text-sm text-gray-600">
                        Showing {{ approvals.length }} approval{{
                            approvals.length !== 1 ? "s" : ""
                        }}
                        <span v-if="selectedDepartment">
                            in {{ selectedDepartment }} department</span
                        >
                    </div>
                </div>

                <div
                    class="flex-1 overflow-y-auto max-h-[500px] border border-gray-300"
                >
                    <!-- Loading State -->
                    <div v-if="loading" class="flex justify-center py-12">
                        <div
                            class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"
                        ></div>
                    </div>

                    <!-- Approvals Table -->
                    <div v-else-if="approvals.length > 0" class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                        <!-- Approval Sequence Legend -->
                        <div class="bg-blue-50 p-4 border-b border-blue-100">
                            <div class="flex items-center gap-2 text-sm text-blue-800 font-medium mb-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Sequential Approval Order: Warehouse → Quality → Shipping → Security
                            </div>
                            <div class="text-xs text-blue-600">
                                Only departments that meet the approval sequence requirements are shown below.
                            </div>
                        </div>

                        <Table>
                            <TableHeader class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <TableRow class="border-b border-gray-200">
                                    <TableHead class="w-[180px] font-semibold text-gray-700 py-4">Container Number</TableHead>
                                    <TableHead class="w-[130px] font-semibold text-gray-700 py-4">Department</TableHead>
                                    <TableHead class="w-[100px] font-semibold text-gray-700 py-4">Step</TableHead>
                                    <TableHead class="w-[130px] font-semibold text-gray-700 py-4">Status</TableHead>
                                    <TableHead class="w-[220px] font-semibold text-gray-700 py-4">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="approval in availableApprovals"
                                    :key="approval.id"
                                    class="hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-200 border-b border-gray-50"
                                >
                                    <TableCell class="py-4">
                                        <span
                                            class="text-blue-600 hover:text-blue-800 font-bold text-lg cursor-pointer hover:underline transition-colors"
                                            @click="openApprovalDetails(approval)"
                                        >
                                            {{ approval.shipment_transport.transport_number }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <span
                                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold"
                                            :class="{
                                                'bg-blue-100 text-blue-800': approval.department === 'warehouse',
                                                'bg-purple-100 text-purple-800': approval.department === 'shipping',
                                                'bg-green-100 text-green-800': approval.department === 'quality',
                                                'bg-orange-100 text-orange-800': approval.department === 'security',
                                                'bg-indigo-100 text-indigo-800': approval.department === 'management',
                                            }"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                            {{ approval.department.charAt(0).toUpperCase() + approval.department.slice(1) }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-800 font-bold text-sm">
                                            {{ getApprovalStep(approval.department) }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <span
                                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold shadow-sm"
                                            :class="{
                                                'bg-yellow-100 text-yellow-800 border border-yellow-200': approval.approval_status === 'pending',
                                                'bg-green-100 text-green-800 border border-green-200': approval.approval_status === 'approved',
                                                'bg-red-100 text-red-800 border border-red-200': approval.approval_status === 'rejected',
                                            }"
                                        >
                                            <svg v-if="approval.approval_status === 'pending'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <svg v-else-if="approval.approval_status === 'approved'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <svg v-else-if="approval.approval_status === 'rejected'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                            {{ approval.approval_status.charAt(0).toUpperCase() + approval.approval_status.slice(1) }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <div
                                            v-if="approval.approval_status === 'pending'"
                                            class="flex gap-2"
                                            @click.stop
                                        >
                                            <button
                                                @click="rejectApproval(approval)"
                                                class="inline-flex items-center gap-1 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                                Reject
                                            </button>
                                            <button
                                                @click="approveApproval(approval)"
                                                class="inline-flex items-center gap-1 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Approve
                                            </button>
                                        </div>
                                        <div v-else class="space-y-1">
                                            <div class="flex items-center gap-2 text-xs text-gray-600">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                <span class="font-medium">{{ approval.approved_by_name || "System" }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span>{{ formatDate(approval.approved_at) }}</span>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <p class="text-gray-500">No approvals found.</p>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Reject Modal -->
        <Dialog :open="showRejectModal" @open-change="showRejectModal = false">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Reject Container Approval</DialogTitle>
                    <DialogDescription>
                        Please provide a reason for rejecting this container
                        approval.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div>
                        <Label for="rejectRemarks">Remarks</Label>
                        <Textarea
                            id="rejectRemarks"
                            v-model="rejectRemarks"
                            placeholder="Enter rejection reason..."
                            rows="3"
                        />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showRejectModal = false">
                        Cancel
                    </Button>
                    <Button
                        @click="confirmReject"
                        :disabled="!rejectRemarks.trim()"
                        class="bg-red-600 hover:bg-red-700"
                    >
                        Reject
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Inspection Approval Detail Modal -->
        <InspectionApprovalDetailModal
            :show="showInspectionModal"
            :approval-id="selectedApprovalId"
            @close="showInspectionModal = false"
        />

        <!-- Loading Photos Modal -->
        <LoadingPhotosModal
            :show="showLoadingModal"
            :approval-id="selectedApprovalId"
            @close="showLoadingModal = false"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from "vue";
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Card } from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import ApprovalStatsCard from "@/Components/ManageContainer/ApprovalStatsCard.vue";
import InspectionApprovalDetailModal from "@/Components/ManageContainer/InspectionApprovalDetailModal.vue";
import LoadingPhotosModal from "@/Components/ManageContainer/LoadingPhotosModal.vue";
import {
    Breadcrumb,
    BreadcrumbPage,
    BreadcrumbSeparator,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
} from "@/components/ui/breadcrumb";
import axios from "axios";
import { router } from "@inertiajs/vue3";

const approvals = ref([]);
const loading = ref(false);
const selectedDepartment = ref("");
const selectedStatus = ref("all");
const showRejectModal = ref(false);
const rejectRemarks = ref("");
const selectedApproval = ref(null);
const userPermissions = ref([]);

// Modal states
const showInspectionModal = ref(false);
const showLoadingModal = ref(false);
const selectedApprovalId = ref(null);

const currentTime = ref(new Date());

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

const isSuperAdmin = computed(() => {
    return userPermissions.value.includes('superadmin');
});

const stats = computed(() => {
    const pending = approvals.value.filter(approval => approval.approval_status === 'pending').length;
    const approved = approvals.value.filter(approval => approval.approval_status === 'approved').length;
    const rejected = approvals.value.filter(approval => approval.approval_status === 'rejected').length;

    return {
        pending,
        approved,
        rejected
    };
});

// Approvals are now filtered by the backend, so we can use them directly
const availableApprovals = computed(() => {
    return approvals.value;
});

// Sequential approval order for step display
const approvalSequence = ['warehouse', 'quality', 'shipping', 'security'];

const getApprovalStep = (department) => {
    return approvalSequence.indexOf(department) + 1;
};

const getNextRequiredApproval = (containerId) => {
    const containerApprovals = approvals.value.filter(a =>
        a.shipment_transport.id === containerId && a.approval_type === 'loading'
    );

    for (const dept of approvalSequence) {
        const approval = containerApprovals.find(a => a.department === dept);
        if (!approval || approval.approval_status !== 'approved') {
            return dept;
        }
    }
    return null;
};

const fetchApprovals = async () => {
    loading.value = true;
    try {
        const params = {};
        if (selectedDepartment.value) {
            params.department = selectedDepartment.value;
        }
        if (selectedStatus.value) {
            params.status = selectedStatus.value;
        }

        const response = await axios.get("/container-approvals", { params });
        approvals.value = response.data.data;
    } catch (error) {
        console.error("Error fetching approvals:", error);
    } finally {
        loading.value = false;
    }
};

const approveApproval = async (approval) => {
    try {
        await axios.post(`/container-approvals/${approval.id}/approve`);
        await fetchApprovals(); // Refresh the list
    } catch (error) {
        console.error("Error approving:", error);
        alert("Failed to approve container");
    }
};

const rejectApproval = (approval) => {
    selectedApproval.value = approval;
    rejectRemarks.value = "";
    showRejectModal.value = true;
};

const confirmReject = async () => {
    if (!rejectRemarks.value.trim()) return;

    try {
        await axios.post(
            `/container-approvals/${selectedApproval.value.id}/reject`,
            {
                remarks: rejectRemarks.value,
            }
        );
        showRejectModal.value = false;
        selectedApproval.value = null;
        rejectRemarks.value = "";
        await fetchApprovals(); // Refresh the list
    } catch (error) {
        console.error("Error rejecting:", error);
        alert("Failed to reject container");
    }
};

const openApprovalDetails = (approval) => {
    selectedApprovalId.value = approval.id;
    if (approval.approval_type === "inspection") {
        showInspectionModal.value = true;
    } else if (approval.approval_type === "loading") {
        showLoadingModal.value = true;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return "";
    return new Date(dateString).toLocaleString();
};

let intervalId;

onMounted(async () => {
    intervalId = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);

    // Fetch user permissions
    try {
        const userResponse = await axios.get('/api/user/permissions');
        userPermissions.value = userResponse.data.permissions || [];
    } catch (error) {
        console.error('Error fetching user permissions:', error);
        userPermissions.value = [];
    }

    fetchApprovals();

    // Listen for container stage updates (real-time updates)
    if (window.Echo) {
        window.Echo.channel("containers")
            .listen(".container.stage.updated", (e) => {
                console.log("Container stage updated event received:", e);
                fetchApprovals(); // Refresh approvals when container stages change
            })
            .error((error) => {
                console.error("WebSocket error on containers channel:", error);
            });

        console.log(
            "Listening for ContainerStageUpdated events on approvals page."
        );
    } else {
        console.error(
            "Laravel Echo is not initialized. Please check resources/js/app.js."
        );
    }
});

onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }

    // Clean up WebSocket listeners
    if (window.Echo) {
        window.Echo.leave("containers");
        console.log("Stopped listening for events on containers channel.");
    }
});
</script>
