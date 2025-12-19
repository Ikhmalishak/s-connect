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

        <ContainerStatCard />

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

                    <!-- Approvals List -->
                    <div v-else-if="approvals.length > 0" class="space-y-2 p-2">
                        <div
                            v-for="approval in approvals"
                            :key="approval.id"
                            class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow bg-white cursor-pointer"
                            @click="openApprovalDetails(approval)"
                        >
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex-1">
                                    <h3
                                        class="text-lg font-semibold text-gray-900"
                                    >
                                        Container #{{
                                            approval.shipment_transport
                                                .transport_number
                                        }}
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        SKU:
                                        {{
                                            approval.shipment_transport
                                                .sku_number
                                        }}
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        Forwarder:
                                        {{
                                            approval.shipment_transport
                                                .forwarder
                                        }}
                                    </p>
                                </div>
                                <div class="text-right ml-4">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mb-2 block"
                                        :class="{
                                            'bg-yellow-100 text-yellow-800':
                                                approval.approval_status ===
                                                'pending',
                                            'bg-green-100 text-green-800':
                                                approval.approval_status ===
                                                'approved',
                                            'bg-red-100 text-red-800':
                                                approval.approval_status ===
                                                'rejected',
                                        }"
                                    >
                                        {{
                                            approval.approval_status
                                                .charAt(0)
                                                .toUpperCase() +
                                            approval.approval_status.slice(1)
                                        }}
                                    </span>
                                    <p class="text-xs text-gray-500">
                                        Department:
                                        {{
                                            approval.department
                                                .charAt(0)
                                                .toUpperCase() +
                                            approval.department.slice(1)
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="flex justify-end space-x-3"
                                v-if="approval.approval_status === 'pending'"
                            >
                                <button
                                    @click.stop="rejectApproval(approval)"
                                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                                >
                                    Reject
                                </button>
                                <button
                                    @click.stop="approveApproval(approval)"
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                                >
                                    Approve
                                </button>
                            </div>

                            <div
                                v-if="approval.approval_status !== 'pending'"
                                class="mt-3 p-3 bg-gray-50 rounded-md"
                            >
                                <p class="text-sm text-gray-600">
                                    <strong>Processed by:</strong>
                                    {{ approval.approved_by_name || "System" }}
                                </p>
                                <p
                                    v-if="approval.remarks"
                                    class="text-sm text-gray-600 mt-1"
                                >
                                    <strong>Remarks:</strong>
                                    {{ approval.remarks }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ formatDate(approval.approved_at) }}
                                </p>
                            </div>
                        </div>
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
import ContainerStatCard from "@/Components/ManageContainer/ContainerStatCard.vue";
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
