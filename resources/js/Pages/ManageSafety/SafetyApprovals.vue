<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
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
    Breadcrumb,
    BreadcrumbPage,
    BreadcrumbSeparator,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
} from "@/components/ui/breadcrumb";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import FindingsDetailModal from "@/Components/ManageSafety/FindingsDetailModal.vue";
import axios from "axios";

const approvals = ref([]);
const loading = ref(false);
const stats = ref({ pending_review: 0, approved: 0, rejected: 0 });
const selectedStatus = ref("all");

// Reject modal
const showRejectModal = ref(false);
const rejectReason = ref("");
const selectedSession = ref(null);

// Detail modal
const showDetailModal = ref(false);

const currentTime = ref(new Date());

let intervalId;

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

function handleOpenDetail(session) {
    selectedSession.value = session;
    showDetailModal.value = true;
}

function handleApprove(session) {
    axios
        .post(`/safety/corrective-action/approve/${session.id}`)
        .then(() => {
            fetchApprovals();
            fetchStats();
        })
        .catch((err) => {
            console.error("Error approving:", err);
            alert("Failed to approve corrective action");
        });
}

function handleReject(session) {
    selectedSession.value = session;
    rejectReason.value = "";
    showRejectModal.value = true;
}

function confirmReject() {
    if (!rejectReason.value.trim()) return;

    axios
        .post(`/safety/corrective-action/reject/${selectedSession.value.id}`, {
            rejection_reason: rejectReason.value,
        })
        .then(() => {
            showRejectModal.value = false;
            selectedSession.value = null;
            rejectReason.value = "";
            fetchApprovals();
            fetchStats();
        })
        .catch((err) => {
            console.error("Error rejecting:", err);
            alert("Failed to reject corrective action");
        });
}

function getSubmitterName(audit) {
    const actions = audit.answers?.map(a => a.finding_action).filter(Boolean) || [];
    if (actions.length > 0) {
        return actions[0].submitter?.name || 'N/A';
    }
    return 'N/A';
}

function getStatusLabel(status) {
    switch (status) {
        case 'pending_review': return 'Pending Review';
        case 'approved': return 'Approved';
        case 'rejected': return 'Rejected';
        case 'mixed': return 'Mixed';
        default: return status || 'Unknown';
    }
}

async function fetchApprovals() {
    loading.value = true;
    try {
        const params = {};
        if (selectedStatus.value !== "all") {
            params.status = selectedStatus.value;
        }
        const res = await axios.get("/safety/pending-approvals", { params });
        approvals.value = res.data.data;
    } catch (err) {
        console.error("Error fetching approvals:", err);
    } finally {
        loading.value = false;
    }
}

async function fetchStats() {
    try {
        const res = await axios.get("/safety/approval-stats");
        stats.value = res.data.data;
    } catch (err) {
        console.error("Error fetching stats:", err);
    }
}

onMounted(() => {
    intervalId = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);

    fetchApprovals();
    fetchStats();
});

onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
});
</script>

<template>
    <AuthenticatedLayout>
        <template #breadcrumb>
            <Breadcrumb>
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink href="/"
                            >EHS Management System</BreadcrumbLink
                        >
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbPage>Safety Approvals</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>
        </template>

        <Head title="Safety Approvals" />

        <Card
            class="shadow-lg shadow-opacity-30 p-2 mb-4 text-2xl font-bold flex items-center justify-between bg-gray-100"
        >
            <div class="flex flex-row items-center">
                <img src="/assets/ss1.png" class="h-12 w-12" alt="" />
                <div>EHS Management System - Safety Approvals</div>
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
                        <p class="text-sm font-medium text-gray-600">Pending Review</p>
                        <p class="text-2xl font-bold text-yellow-600">{{ stats.pending_review }}</p>
                    </div>
                    <div class="p-3 bg-yellow-100 rounded-full">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

        <!-- Approvals Table -->
        <div class="relative">
            <div class="absolute -top-3 left-2 z-10">
                <span
                    class="bg-gray-100 text-black px-2 py-1 rounded-full text-xs font-semibold border shadow-md"
                >
                    Corrective Action Approvals
                </span>
            </div>

            <Card class="p-3 shadow-2xl max-h-[700px] shadow-opacity-60">
                <div class="flex flex-col space-y-4 mb-4">
                    <div class="flex items-center gap-4">
                        <div>
                            <Select v-model="selectedStatus" @update:model-value="fetchApprovals">
                                <SelectTrigger class="w-32">
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="all">All Status</SelectItem>
                                        <SelectItem value="pending_review">Pending Review</SelectItem>
                                        <SelectItem value="approved">Approved</SelectItem>
                                        <SelectItem value="rejected">Rejected</SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <div v-if="!loading" class="text-sm text-gray-600">
                        Showing {{ approvals.length }} approval{{ approvals.length !== 1 ? 's' : '' }}
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto max-h-[500px] border border-gray-300">
                    <div v-if="loading" class="flex justify-center py-12">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    </div>

                    <table v-else-if="approvals.length > 0" class="min-w-full">
                        <thead class="sticky top-0 bg-gray-100 z-40 border border-b-gray-300">
                            <tr class="border border-gray-300 font-black divide-x divide-gray-300 text-black">
                                <th class="font-black text-black text-center bg-gray-100 p-3 sticky top-0 z-20 border-r border-gray-300 text-sm">No</th>
                                <th class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm">Site</th>
                                <th class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm">Audit Type</th>
                                <th class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm">Department</th>
                                <th class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm">Submitted By</th>
                                <th class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm">Status</th>
                                <th class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="border border-gray-300 divide-x divide-gray-300">
                            <tr v-for="(audit, index) in approvals" :key="audit.id" class="text-center text-sm border border-gray-300 divide-x divide-gray-300 p-2">
                                <td class="p-2">{{ index + 1 }}</td>
                                <td class="p-2">{{ audit.site?.name }}</td>
                                <td class="p-2">{{ audit.audit_type?.name }}</td>
                                <td class="p-2">{{ audit.department?.name }}</td>
                                <td class="p-2">{{ getSubmitterName(audit) }}</td>
                                <td class="p-2">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                        :class="audit.approval_status === 'approved' ? 'bg-green-100 text-green-800' :
                                               audit.approval_status === 'rejected' ? 'bg-red-100 text-red-800' :
                                               audit.approval_status === 'pending_review' ? 'bg-yellow-100 text-yellow-800' :
                                               'bg-gray-100 text-gray-800'">
                                        {{ getStatusLabel(audit.approval_status) }}
                                    </span>
                                </td>
                                <td class="p-2">
                                    <div class="flex gap-2 justify-center">
                                        <Button variant="outline" size="sm" class="bg-blue-600 hover:bg-blue-700 text-white text-xs" @click="handleOpenDetail(audit)">
                                            Details
                                        </Button>
                                        <Button v-if="audit.approval_status === 'pending_review'" variant="outline" size="sm" class="bg-green-600 hover:bg-green-700 text-white text-xs" @click="handleApprove(audit)">
                                            Approve
                                        </Button>
                                        <Button v-if="audit.approval_status === 'pending_review'" variant="outline" size="sm" class="bg-red-600 hover:bg-red-700 text-white text-xs" @click="handleReject(audit)">
                                            Reject
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

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
                    <DialogTitle>Reject Corrective Action</DialogTitle>
                    <DialogDescription>
                        Please provide a reason for rejecting this corrective action.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div>
                        <Label for="rejectReason">Reason for Rejection</Label>
                        <Textarea id="rejectReason" v-model="rejectReason" placeholder="Enter rejection reason..." rows="3" />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showRejectModal = false">Cancel</Button>
                    <Button @click="confirmReject" :disabled="!rejectReason.trim()" class="bg-red-600 hover:bg-red-700">Reject</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Detail Modal (uses shared FindingsDetailModal) -->
        <FindingsDetailModal
            :show="showDetailModal"
            :session="selectedSession"
            @close="showDetailModal = false"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
</style>