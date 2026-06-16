<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import { useToast } from "@/components/ui/toast";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Card } from "@/components/ui/card";
import {
    Breadcrumb,
    BreadcrumbPage,
    BreadcrumbSeparator,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
} from "@/components/ui/breadcrumb";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogTrigger,
    DialogFooter,
} from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import {
    Search,
    Plus,
    Edit,
    Trash2,
    ChevronUp,
    ChevronDown,
} from "lucide-vue-next";

// Types
interface ContainerGpsRecord {
    id: number;
    overhaul_id: string;
    reject_reason: string;
    remark: string | null;
    date: string;
    created_at: string;
    updated_at: string;
}

// Reactive data
const records = ref<ContainerGpsRecord[]>([]);
const loading = ref(false);
const searchQuery = ref("");
const sortBy = ref<keyof ContainerGpsRecord>("created_at");
const sortDirection = ref("desc");
const currentPage = ref(1);
const perPage = ref(50);
const totalPages = ref(1);

// Form data for create/edit
const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const editingRecord = ref<ContainerGpsRecord | null>(null);
const formData = ref({
    overhaul_id: "",
    reject_reason: "",
    remark: "",
    date: "",
});
const formLoading = ref(false);
const formMessage = ref("");
const formMessageType = ref("");

// Delete state
const showDeleteDialog = ref(false);
const deletingRecord = ref<ContainerGpsRecord | null>(null);
const deleteMessage = ref("");
const deleteMessageType = ref("");

// Toast
const { toast } = useToast();

// Computed properties
const sortedRecords = computed(() => {
    return [...records.value].sort((a, b) => {
        const key = sortBy.value;
        const aVal = a[key];
        const bVal = b[key];
        const aStr = aVal === null ? '' : String(aVal);
        const bStr = bVal === null ? '' : String(bVal);
        if (sortDirection.value === 'asc') {
            return aStr > bStr ? 1 : -1;
        } else {
            return aStr < bStr ? 1 : -1;
        }
    });
});

const paginatedRecords = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return sortedRecords.value.slice(start, end);
});

// Methods
const fetchRecords = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams({
            search: searchQuery.value,
            sort_by: sortBy.value,
            sort_direction: sortDirection.value,
            per_page: perPage.value.toString(),
        });

        const response = await axios.get(`/api/container-gps?${params}`);
        records.value = response.data.data;
        totalPages.value = response.data.last_page || 1;
        currentPage.value = response.data.current_page || 1;
    } catch (error) {
        console.error("Failed to fetch container GPS records:", error);
        toast({
            title: "Error",
            description: "Failed to load container GPS records",
            variant: "destructive",
        });
    } finally {
        loading.value = false;
    }
};

const handleSort = (column: keyof ContainerGpsRecord) => {
    if (sortBy.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortDirection.value = 'asc';
    }
    fetchRecords();
};

const openCreateDialog = () => {
    formData.value = {
        overhaul_id: "",
        reject_reason: "",
        remark: "",
        date: new Date().toISOString().split('T')[0],
    };
    showCreateDialog.value = true;
};

const openEditDialog = (record: any) => {
    editingRecord.value = record;
    formData.value = {
        overhaul_id: record.overhaul_id,
        reject_reason: record.reject_reason,
        remark: record.remark,
        date: record.date,
    };
    showEditDialog.value = true;
};

const openDeleteDialog = (record: any) => {
    deletingRecord.value = record;
    deleteMessage.value = "";
    deleteMessageType.value = "";
    showDeleteDialog.value = true;
};

const createRecord = async () => {
    formLoading.value = true;
    formMessage.value = "";
    formMessageType.value = "";
    try {
        await axios.post('/api/container-gps', formData.value);

        formMessage.value = "Container GPS record created successfully";
        formMessageType.value = "success";

        setTimeout(() => {
            formMessage.value = "";
            formMessageType.value = "";
            showCreateDialog.value = false;
        }, 2000);

        fetchRecords();
    } catch (error: any) {
        console.error("Failed to create record:", error);
        const message = error.response?.data?.message || "Failed to create record";
        formMessage.value = message;
        formMessageType.value = "error";
    } finally {
        formLoading.value = false;
    }
};

const updateRecord = async () => {
    if (!editingRecord.value) return;

    formLoading.value = true;
    formMessage.value = "";
    formMessageType.value = "";
    try {
        await axios.put(`/api/container-gps/${editingRecord.value.id}`, formData.value);

        formMessage.value = "Container GPS record updated successfully";
        formMessageType.value = "success";

        setTimeout(() => {
            formMessage.value = "";
            formMessageType.value = "";
            showEditDialog.value = false;
            editingRecord.value = null;
        }, 2000);

        fetchRecords();
    } catch (error: any) {
        console.error("Failed to update record:", error);
        const message = error.response?.data?.message || "Failed to update record";
        formMessage.value = message;
        formMessageType.value = "error";
    } finally {
        formLoading.value = false;
    }
};

const deleteRecord = async () => {
    if (!deletingRecord.value) return;

    deleteMessage.value = "";
    deleteMessageType.value = "";
    try {
        await axios.delete(`/api/container-gps/${deletingRecord.value.id}`);

        toast({
            title: "Success",
            description: "Container GPS record deleted successfully",
        });

        showDeleteDialog.value = false;
        deletingRecord.value = null;
        fetchRecords();
    } catch (error: any) {
        console.error("Failed to delete record:", error);
        const message = error.response?.data?.message || "Failed to delete record";
        deleteMessage.value = message;
        deleteMessageType.value = "error";
    }
};

const formatDate = (dateString: string) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
};

// Lifecycle
onMounted(() => {
    fetchRecords();
});
</script>

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
                        <BreadcrumbPage>Container GPS Records</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>
        </template>

        <Card class="shadow-lg shadow-opacity-30 p-2 mb-4 text-2xl font-bold flex items-center justify-between bg-gray-100">
            <div class="flex flex-row items-center">
                <img src="/assets/ss1.png" class="h-12 w-12" alt="" />
                <div>Container GPS Management</div>
            </div>
        </Card>

        <!-- Search and Actions -->
        <Card class="p-6 mb-6">
            <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                        <Input
                            v-model="searchQuery"
                            @input="fetchRecords"
                            placeholder="Search by overhaul ID, reject reason..."
                            class="pl-10 w-80"
                        />
                    </div>
                </div>

                <Dialog v-model:open="showCreateDialog">
                    <DialogTrigger as-child>
                        <Button @click="openCreateDialog" class="bg-green-600 hover:bg-green-700">
                            <Plus class="h-4 w-4 mr-2" />
                            Add GPS Record
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="max-w-lg max-h-[90vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Create Container GPS Record</DialogTitle>
                            <DialogDescription>
                                Record a rejected container GPS that follows shipping requirements.
                            </DialogDescription>
                        </DialogHeader>
                        <div class="space-y-4">
                            <div>
                                <Label for="overhaul_id">Overhaul ID *</Label>
                                <Input id="overhaul_id" v-model="formData.overhaul_id" placeholder="e.g., OVH-2024-001" />
                            </div>
                            <div>
                                <Label for="reject_reason">Reject Reason *</Label>
                                <Input id="reject_reason" v-model="formData.reject_reason" placeholder="e.g., GPS device malfunction" />
                            </div>
                            <div>
                                <Label for="remark">Remark</Label>
                                <Input id="remark" v-model="formData.remark" placeholder="Optional remarks" />
                            </div>
                            <div>
                                <Label for="date">Date *</Label>
                                <Input id="date" v-model="formData.date" type="date" />
                            </div>
                        </div>

                        <!-- Inline Message -->
                        <div v-if="formMessage" :class="[
                            'mt-4 p-3 rounded-md text-sm',
                            formMessageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'
                        ]">
                            {{ formMessage }}
                        </div>

                        <DialogFooter>
                            <Button variant="outline" @click="showCreateDialog = false">Cancel</Button>
                            <Button @click="createRecord" :disabled="formLoading">
                                <span v-if="formLoading">Creating...</span>
                                <span v-else>Create Record</span>
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </Card>

        <!-- Records Table -->
        <Card class="p-6">
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-50"
                                @click="handleSort('overhaul_id')"
                            >
                                <div class="flex items-center">
                                    Overhaul ID
                                    <ChevronUp
                                        v-if="sortBy === 'overhaul_id' && sortDirection === 'asc'"
                                        class="h-4 w-4 ml-1"
                                    />
                                    <ChevronDown
                                        v-if="sortBy === 'overhaul_id' && sortDirection === 'desc'"
                                        class="h-4 w-4 ml-1"
                                    />
                                </div>
                            </TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-50"
                                @click="handleSort('reject_reason')"
                            >
                                <div class="flex items-center">
                                    Reject Reason
                                    <ChevronUp
                                        v-if="sortBy === 'reject_reason' && sortDirection === 'asc'"
                                        class="h-4 w-4 ml-1"
                                    />
                                    <ChevronDown
                                        v-if="sortBy === 'reject_reason' && sortDirection === 'desc'"
                                        class="h-4 w-4 ml-1"
                                    />
                                </div>
                            </TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-50"
                                @click="handleSort('remark')"
                            >
                                <div class="flex items-center">
                                    Remark
                                    <ChevronUp
                                        v-if="sortBy === 'remark' && sortDirection === 'asc'"
                                        class="h-4 w-4 ml-1"
                                    />
                                    <ChevronDown
                                        v-if="sortBy === 'remark' && sortDirection === 'desc'"
                                        class="h-4 w-4 ml-1"
                                    />
                                </div>
                            </TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-50"
                                @click="handleSort('date')"
                            >
                                <div class="flex items-center">
                                    Date
                                    <ChevronUp
                                        v-if="sortBy === 'date' && sortDirection === 'asc'"
                                        class="h-4 w-4 ml-1"
                                    />
                                    <ChevronDown
                                        v-if="sortBy === 'date' && sortDirection === 'desc'"
                                        class="h-4 w-4 ml-1"
                                    />
                                </div>
                            </TableHead>
                            <TableHead>Created At</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="loading">
                            <TableCell colspan="6" class="text-center py-8">
                                <div class="flex items-center justify-center">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
                                    <span class="ml-2">Loading records...</span>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-else-if="paginatedRecords.length === 0">
                            <TableCell colspan="6" class="text-center py-8 text-gray-500">
                                No container GPS records found
                            </TableCell>
                        </TableRow>
                        <TableRow
                            v-else
                            v-for="record in paginatedRecords"
                            :key="record.id"
                        >
                            <TableCell class="font-medium">{{ record.overhaul_id }}</TableCell>
                            <TableCell>{{ record.reject_reason }}</TableCell>
                            <TableCell>{{ record.remark || '-' }}</TableCell>
                            <TableCell>{{ formatDate(record.date) }}</TableCell>
                            <TableCell class="text-sm text-gray-600">
                                {{ formatDate(record.created_at) }}
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="openEditDialog(record)"
                                    >
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        class="text-red-600 hover:text-red-700"
                                        @click="openDeleteDialog(record)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="flex items-center justify-between mt-4">
                <div class="text-sm text-gray-700">
                    Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, records.length) }} of {{ records.length }} entries
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="currentPage === 1"
                        @click="currentPage--"
                    >
                        Previous
                    </Button>
                    <span class="text-sm">
                        Page {{ currentPage }} of {{ totalPages }}
                    </span>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="currentPage === totalPages"
                        @click="currentPage++"
                    >
                        Next
                    </Button>
                </div>
            </div>
        </Card>

        <!-- Edit Dialog -->
        <Dialog v-model:open="showEditDialog">
            <DialogContent class="max-w-lg max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Edit Container GPS Record</DialogTitle>
                    <DialogDescription>
                        Update the container GPS record details.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-4">
                    <div>
                        <Label for="edit-overhaul_id">Overhaul ID *</Label>
                        <Input id="edit-overhaul_id" v-model="formData.overhaul_id" placeholder="e.g., OVH-2024-001" />
                    </div>
                    <div>
                        <Label for="edit-reject_reason">Reject Reason *</Label>
                        <Input id="edit-reject_reason" v-model="formData.reject_reason" placeholder="e.g., GPS device malfunction" />
                    </div>
                    <div>
                        <Label for="edit-remark">Remark</Label>
                        <Input id="edit-remark" v-model="formData.remark" placeholder="Optional remarks" />
                    </div>
                    <div>
                        <Label for="edit-date">Date *</Label>
                        <Input id="edit-date" v-model="formData.date" type="date" />
                    </div>
                </div>

                <!-- Inline Message -->
                <div v-if="formMessage" :class="[
                    'mt-4 p-3 rounded-md text-sm',
                    formMessageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'
                ]">
                    {{ formMessage }}
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showEditDialog = false">Cancel</Button>
                    <Button @click="updateRecord" :disabled="formLoading">
                        <span v-if="formLoading">Updating...</span>
                        <span v-else>Update Record</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <AlertDialog v-model:open="showDeleteDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Container GPS Record</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to delete this container GPS record?
                        This action cannot be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <!-- Record Info -->
                <div v-if="deletingRecord" class="bg-gray-50 p-3 rounded-md mb-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Record Details</h4>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div><strong>Overhaul ID:</strong> {{ deletingRecord.overhaul_id }}</div>
                        <div><strong>Reject Reason:</strong> {{ deletingRecord.reject_reason }}</div>
                        <div><strong>Date:</strong> {{ formatDate(deletingRecord.date) }}</div>
                    </div>
                </div>

                <!-- Inline Message -->
                <div v-if="deleteMessage" :class="[
                    'p-3 rounded-md text-sm',
                    deleteMessageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'
                ]">
                    {{ deleteMessage }}
                </div>

                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        @click="deleteRecord"
                        class="bg-red-600 hover:bg-red-700"
                    >
                        Delete
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AuthenticatedLayout>
</template>