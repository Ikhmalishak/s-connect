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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
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
import { Checkbox } from "@/components/ui/checkbox";
import { Textarea } from "@/components/ui/textarea";
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

// Reactive data
const requirements = ref([]);
const loading = ref(false);
const searchQuery = ref("");
const sortBy = ref("created_at");
const sortDirection = ref("desc");
const currentPage = ref(1);
const perPage = ref(50);
const totalPages = ref(1);

// Form data for create/edit
const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const editingRequirement = ref(null);
const formData = ref({
    region: "",
    destination: "",
    risk_level: "",
    strength: "",
    requires_gps: false,
    attachment: null,
});
const formLoading = ref(false);
const formMessage = ref("");
const formMessageType = ref(""); // "success" or "error"

// Delete form data
const deleteReason = ref("");
const deleteAttachment = ref(null);
const deleteFormMessage = ref("");
const deleteFormMessageType = ref("");

// Refs
const attachmentRef = ref(null);
const editAttachmentRef = ref(null);
const deleteAttachmentRef = ref(null);

// Toast
const { toast } = useToast();

// Computed properties
const sortedRequirements = computed(() => {
    return [...requirements.value].sort((a, b) => {
        let aVal = a[sortBy.value];
        let bVal = b[sortBy.value];

        if (sortBy.value === 'requires_gps') {
            aVal = aVal ? 1 : 0;
            bVal = bVal ? 1 : 0;
        }

        if (sortDirection.value === 'asc') {
            return aVal > bVal ? 1 : -1;
        } else {
            return aVal < bVal ? 1 : -1;
        }
    });
});

const paginatedRequirements = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return sortedRequirements.value.slice(start, end);
});

// Methods
const fetchRequirements = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams({
            search: searchQuery.value,
            sort_by: sortBy.value,
            sort_direction: sortDirection.value,
            per_page: perPage.value.toString(),
        });

        const response = await axios.get(`/api/shipping-requirements?${params}`);
        requirements.value = response.data.data;
        totalPages.value = response.data.last_page;
        currentPage.value = response.data.current_page;
    } catch (error) {
        console.error("Failed to fetch requirements:", error);
        toast({
            title: "Error",
            description: "Failed to load shipping requirements",
            variant: "destructive",
        });
    } finally {
        loading.value = false;
    }
};

const handleSort = (column: string) => {
    if (sortBy.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortDirection.value = 'asc';
    }
    fetchRequirements();
};

const openCreateDialog = () => {
    formData.value = {
        region: "",
        destination: "",
        risk_level: "",
        strength: "",
        requires_gps: false,
        attachment: null,
    };
    showCreateDialog.value = true;
};

const openEditDialog = (requirement: any) => {
    editingRequirement.value = requirement;
    formData.value = {
        region: requirement.region,
        destination: requirement.destination,
        risk_level: requirement.risk_level,
        strength: requirement.strength,
        requires_gps: requirement.requires_gps,
        attachment: null,
    };
    showEditDialog.value = true;
};

const createRequirement = async () => {
    formLoading.value = true;
    formMessage.value = "";
    formMessageType.value = "";
    try {
        const formDataToSend = new FormData();
        formDataToSend.append('change_type', 'create');
        formDataToSend.append('proposed_data[region]', formData.value.region);
        formDataToSend.append('proposed_data[destination]', formData.value.destination);
        formDataToSend.append('proposed_data[risk_level]', formData.value.risk_level);
        formDataToSend.append('proposed_data[strength]', formData.value.strength);
        formDataToSend.append('proposed_data[requires_gps]', formData.value.requires_gps ? '1' : '0');

        if (formData.value.attachment) {
            formDataToSend.append('attachment', formData.value.attachment);
        }

        await axios.post('/api/shipping-requirements/request-change', formDataToSend, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        formMessage.value = "Shipping requirement creation request submitted for approval";
        formMessageType.value = "success";

        // Clear success message after 3 seconds and close dialog
        setTimeout(() => {
            formMessage.value = "";
            formMessageType.value = "";
            showCreateDialog.value = false;
        }, 3000);

        fetchRequirements();
    } catch (error: any) {
        console.error("Failed to submit create request:", error);
        const message = error.response?.data?.message || "Failed to submit creation request";
        formMessage.value = message;
        formMessageType.value = "error";
    } finally {
        formLoading.value = false;
    }
};

const updateRequirement = async () => {
    if (!editingRequirement.value) return;

    formLoading.value = true;
    formMessage.value = "";
    formMessageType.value = "";
    try {
        const formDataToSend = new FormData();
        formDataToSend.append('change_type', 'update');
        formDataToSend.append('shipping_requirement_id', editingRequirement.value.id.toString());
        formDataToSend.append('proposed_data[region]', formData.value.region);
        formDataToSend.append('proposed_data[destination]', formData.value.destination);
        formDataToSend.append('proposed_data[risk_level]', formData.value.risk_level);
        formDataToSend.append('proposed_data[strength]', formData.value.strength);
        formDataToSend.append('proposed_data[requires_gps]', formData.value.requires_gps ? '1' : '0');

        if (formData.value.attachment) {
            formDataToSend.append('attachment', formData.value.attachment);
        }

        await axios.post('/api/shipping-requirements/request-change', formDataToSend, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        formMessage.value = "Shipping requirement change request submitted for approval";
        formMessageType.value = "success";

        // Clear success message after 3 seconds and close dialog
        setTimeout(() => {
            formMessage.value = "";
            formMessageType.value = "";
            showEditDialog.value = false;
            editingRequirement.value = null;
        }, 3000);

        fetchRequirements();
    } catch (error: any) {
        console.error("Failed to submit update request:", error);
        const message = error.response?.data?.message || "Failed to submit update request";
        formMessage.value = message;
        formMessageType.value = "error";
    } finally {
        formLoading.value = false;
    }
};

const deleteRequirement = async (requirement: any) => {
    try {
        const formDataToSend = new FormData();
        formDataToSend.append('change_type', 'delete');
        formDataToSend.append('shipping_requirement_id', requirement.id.toString());

        await axios.post('/api/shipping-requirements/request-change', formDataToSend, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        toast({
            title: "Success",
            description: "Shipping requirement deletion request submitted for approval",
        });
        fetchRequirements();
    } catch (error: any) {
        console.error("Failed to submit delete request:", error);
        const message = error.response?.data?.message || "Failed to submit deletion request";
        toast({
            title: "Error",
            description: message,
            variant: "destructive",
        });
    }
};

const getRiskBadgeVariant = (risk: string) => {
    switch (risk.toLowerCase()) {
        case 'high':
            return 'destructive';
        case 'medium':
            return 'destroy'; // Yellow variant for testing
        case 'low':
            return 'secondary';
        default:
            return 'outline';
    }
};

const getSealsBadgeVariant = (requires: boolean) => {
    return requires ? 'default' : 'secondary';
};

// File handling methods
const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            toast({
                title: "Error",
                description: "File size must be less than 5MB",
                variant: "destructive",
            });
            return;
        }
        formData.value.attachment = file;
    }
};

const removeAttachment = () => {
    formData.value.attachment = null;
    if (attachmentRef.value) {
        (attachmentRef.value as HTMLInputElement).value = '';
    }
};

// Delete-specific methods
const handleDeleteFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            toast({
                title: "Error",
                description: "File size must be less than 5MB",
                variant: "destructive",
            });
            return;
        }
        deleteAttachment.value = file;
    }
};

const removeDeleteAttachment = () => {
    deleteAttachment.value = null;
    if (deleteAttachmentRef.value) {
        (deleteAttachmentRef.value as HTMLInputElement).value = '';
    }
};

const triggerDeleteFileUpload = () => {
    const input = document.getElementById('delete-attachment') as HTMLInputElement;
    input?.click();
};

const clearDeleteForm = () => {
    deleteReason.value = "";
    deleteAttachment.value = null;
    if (deleteAttachmentRef.value) {
        (deleteAttachmentRef.value as HTMLInputElement).value = '';
    }
};

const submitDeleteRequest = async (requirement: any) => {
    if (!deleteReason.value.trim() || !deleteAttachment.value) {
        return;
    }

    deleteFormMessage.value = "";
    deleteFormMessageType.value = "";

    try {
        const formDataToSend = new FormData();
        formDataToSend.append('change_type', 'delete');
        formDataToSend.append('shipping_requirement_id', requirement.id.toString());
        formDataToSend.append('proposed_data[reason]', deleteReason.value);

        if (deleteAttachment.value) {
            formDataToSend.append('attachment', deleteAttachment.value);
        }

        await axios.post('/api/shipping-requirements/request-change', formDataToSend, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        deleteFormMessage.value = "Shipping requirement deletion request submitted for approval";
        deleteFormMessageType.value = "success";

        // Clear success message after 3 seconds
        setTimeout(() => {
            deleteFormMessage.value = "";
            deleteFormMessageType.value = "";
        }, 3000);

        clearDeleteForm();
        fetchRequirements();
    } catch (error: any) {
        console.error("Failed to submit delete request:", error);
        const message = error.response?.data?.message || "Failed to submit deletion request";
        deleteFormMessage.value = message;
        deleteFormMessageType.value = "error";
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
    fetchRequirements();

    // Listen for real-time updates
    if (window.Echo) {
        window.Echo.private('shipping-requirements')
            .listen('.change.requested', (e) => {
                console.log('Shipping requirement change requested:', e);
                // Refresh the requirements list to show pending status
                fetchRequirements();
            })
            .listen('.change.processed', (e) => {
                console.log('Shipping requirement change processed:', e);
                // Refresh the requirements list to show updated data
                fetchRequirements();
            })
            .error((error) => {
                console.error('WebSocket error on shipping-requirements channel:', error);
            });

        console.log('Listening for shipping requirement change events.');
    } else {
        console.error('Laravel Echo is not initialized. Please check resources/js/app.js.');
    }
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
                        <BreadcrumbPage>Shipping Requirements</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>
        </template>

        <Card class="shadow-lg shadow-opacity-30 p-2 mb-4 text-2xl font-bold flex items-center justify-between bg-gray-100">
            <div class="flex flex-row items-center">
                <img src="/assets/ss1.png" class="h-12 w-12" alt="" />
                <div>Shipping Requirements Management</div>
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
                            @input="fetchRequirements"
                            placeholder="Search regions, destinations, or risk levels..."
                            class="pl-10 w-80"
                        />
                    </div>
                    <Select v-model="perPage" @update:model-value="fetchRequirements">
                        <SelectTrigger class="w-32">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="10">10 per page</SelectItem>
                            <SelectItem :value="25">25 per page</SelectItem>
                            <SelectItem :value="50">50 per page</SelectItem>
                            <SelectItem :value="100">100 per page</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <Dialog v-model:open="showCreateDialog">
                    <DialogTrigger as-child>
                        <Button @click="openCreateDialog" class="bg-green-600 hover:bg-green-700">
                            <Plus class="h-4 w-4 mr-2" />
                            Add Requirement
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="max-w-lg max-h-[90vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Request New Shipping Requirement</DialogTitle>
                            <DialogDescription>
                                Your request will be submitted for approval. Supporting documentation is required.
                            </DialogDescription>
                        </DialogHeader>
                        <div class="space-y-4">
                            <div>
                                <Label for="region">Region *</Label>
                                <Input id="region" v-model="formData.region" placeholder="e.g., Americas, APAC, EMEA" />
                            </div>
                            <div>
                                <Label for="destination">Destination *</Label>
                                <Input id="destination" v-model="formData.destination" placeholder="e.g., Canada, Japan, Germany" />
                            </div>
                            <div>
                                <Label for="risk_level">Risk Level *</Label>
                                <Select v-model="formData.risk_level">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select risk level" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="High">High</SelectItem>
                                        <SelectItem value="Medium">Medium</SelectItem>
                                        <SelectItem value="Low">Low</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label for="strength">Strength *</Label>
                                <Input id="strength" v-model="formData.strength" placeholder="e.g., 8, 3" />
                            </div>
                            <div class="flex items-center space-x-2">
                                <Checkbox id="requires_gps" v-model="formData.requires_gps" />
                                <Label for="requires_gps">Requires GPS</Label>
                            </div>

                            <!-- Attachment Upload -->
                            <div>
                                <Label for="create-attachment">Supporting Documentation *</Label>
                                <div class="mt-1">
                                    <input
                                        type="file"
                                        id="create-attachment"
                                        ref="attachmentRef"
                                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                        @change="handleFileSelect"
                                        class="hidden"
                                    />
                                    <div
                                        @click="() => attachmentRef?.click?.()"
                                        class="border-2 border-dashed border-gray-300 rounded-md p-4 text-center cursor-pointer hover:border-gray-400 transition-colors"
                                    >
                                        <div v-if="!formData.attachment" class="space-y-2">
                                            <div class="text-gray-400">
                                                <svg class="mx-auto h-8 w-8" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </div>
                                            <p class="text-sm text-gray-600">
                                                Click to upload supporting documents
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                PDF, DOC, DOCX, JPG, PNG up to 5MB
                                            </p>
                                        </div>
                                        <div v-else class="space-y-2">
                                            <div class="text-green-600">
                                                <svg class="mx-auto h-8 w-8" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ formData.attachment.name }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ (formData.attachment.size / 1024 / 1024).toFixed(2) }} MB
                                            </p>
                                            <button
                                                @click.stop="removeAttachment"
                                                class="text-xs text-red-600 hover:text-red-800"
                                            >
                                                Remove file
                                            </button>
                                        </div>
                                    </div>
                                </div>
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
                            <Button @click="createRequirement" :disabled="formLoading">
                                <span v-if="formLoading">Submitting...</span>
                                <span v-else>Submit for Approval</span>
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </Card>

        <!-- Requirements Table -->
        <Card class="p-6">
            <!-- Status Legend -->
            <div class="mb-4 flex items-center gap-6 text-sm">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-yellow-100 border border-yellow-300 rounded"></div>
                    <span>Pending Approval</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-white border border-gray-200 rounded"></div>
                    <span>Approved</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-50"
                                @click="handleSort('region')"
                            >
                                <div class="flex items-center">
                                    Region
                                    <ChevronUp
                                        v-if="sortBy === 'region' && sortDirection === 'asc'"
                                        class="h-4 w-4 ml-1"
                                    />
                                    <ChevronDown
                                        v-if="sortBy === 'region' && sortDirection === 'desc'"
                                        class="h-4 w-4 ml-1"
                                    />
                                </div>
                            </TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-50"
                                @click="handleSort('destination')"
                            >
                                <div class="flex items-center">
                                    Destination
                                    <ChevronUp
                                        v-if="sortBy === 'destination' && sortDirection === 'asc'"
                                        class="h-4 w-4 ml-1"
                                    />
                                    <ChevronDown
                                        v-if="sortBy === 'destination' && sortDirection === 'desc'"
                                        class="h-4 w-4 ml-1"
                                    />
                                </div>
                            </TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-50"
                                @click="handleSort('risk_level')"
                            >
                                <div class="flex items-center">
                                    Risk Level
                                    <ChevronUp
                                        v-if="sortBy === 'risk_level' && sortDirection === 'asc'"
                                        class="h-4 w-4 ml-1"
                                    />
                                    <ChevronDown
                                        v-if="sortBy === 'risk_level' && sortDirection === 'desc'"
                                        class="h-4 w-4 ml-1"
                                    />
                                </div>
                            </TableHead>
                            <TableHead>Strength</TableHead>
                            <TableHead
                                class="cursor-pointer hover:bg-gray-50"
                                @click="handleSort('requires_gps')"
                            >
                                <div class="flex items-center">
                                    Requires GPS
                                    <ChevronUp
                                        v-if="sortBy === 'requires_gps' && sortDirection === 'asc'"
                                        class="h-4 w-4 ml-1"
                                    />
                                    <ChevronDown
                                        v-if="sortBy === 'requires_gps' && sortDirection === 'desc'"
                                        class="h-4 w-4 ml-1"
                                    />
                                </div>
                            </TableHead>
                            <TableHead>Last Updated</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="loading">
                            <TableCell colspan="6" class="text-center py-8">
                                <div class="flex items-center justify-center">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
                                    <span class="ml-2">Loading requirements...</span>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-else-if="paginatedRequirements.length === 0">
                            <TableCell colspan="6" class="text-center py-8 text-gray-500">
                                No shipping requirements found
                            </TableCell>
                        </TableRow>
                        <TableRow
                            v-else
                            v-for="requirement in paginatedRequirements"
                            :key="requirement.id"
                            :class="requirement.status === 'pending' ? 'bg-yellow-50' : ''"
                        >
                            <TableCell class="font-medium">{{ requirement.region }}</TableCell>
                            <TableCell>{{ requirement.destination }}</TableCell>
                            <TableCell>
                                <Badge :variant="getRiskBadgeVariant(requirement.risk_level)">
                                    {{ requirement.risk_level }}
                                </Badge>
                            </TableCell>
                            <TableCell>{{ requirement.strength }}</TableCell>
                            <TableCell>
                                <Badge :variant="getSealsBadgeVariant(requirement.requires_gps)">
                                    {{ requirement.requires_gps ? 'Yes' : 'No' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-sm text-gray-600">
                                <div v-if="requirement.approved_at">
                                    {{ formatDate(requirement.approved_at) }}
                                </div>
                                <div v-else-if="requirement.change_requested_at">
                                    <span class="text-yellow-600">Pending Approval</span>
                                </div>
                                <div v-else>
                                    {{ formatDate(requirement.created_at) }}
                                </div>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        :disabled="requirement.status === 'pending'"
                                        @click="openEditDialog(requirement)"
                                    >
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <AlertDialog>
                                        <AlertDialogTrigger as-child>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="text-red-600 hover:text-red-700"
                                                :disabled="requirement.status === 'pending'"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </AlertDialogTrigger>
                                        <AlertDialogContent class="max-w-lg max-h-[90vh] overflow-y-auto">
                                            <AlertDialogHeader>
                                                <AlertDialogTitle>Request Shipping Requirement Deletion</AlertDialogTitle>
                                                <AlertDialogDescription>
                                                    Your deletion request will be submitted for approval. Please provide justification and supporting documentation.
                                                </AlertDialogDescription>
                                            </AlertDialogHeader>

                                            <!-- Current Requirement Info -->
                                            <div class="bg-gray-50 p-3 rounded-md mb-4">
                                                <h4 class="text-sm font-medium text-gray-900 mb-2">Requirement to Delete</h4>
                                                <div class="grid grid-cols-2 gap-2 text-sm">
                                                    <div><strong>Region:</strong> {{ requirement.region }}</div>
                                                    <div><strong>Destination:</strong> {{ requirement.destination }}</div>
                                                    <div><strong>Risk Level:</strong> {{ requirement.risk_level }}</div>
                                                    <div><strong>Strength:</strong> {{ requirement.strength }}</div>
                                                    <div class="col-span-2"><strong>Requires GPS:</strong> {{ requirement.requires_gps ? 'Yes' : 'No' }}</div>
                                                </div>
                                            </div>

                                            <!-- Deletion Reason -->
                                            <div class="space-y-4">
                                                <div>
                                                    <Label for="delete-reason">Deletion Reason *</Label>
                                                    <Textarea
                                                        id="delete-reason"
                                                        v-model="deleteReason"
                                                        placeholder="Please explain why this shipping requirement should be deleted..."
                                                        rows="3"
                                                        class="mt-1"
                                                    />
                                                </div>

                                                <!-- Attachment Upload -->
                                                <div>
                                                    <Label for="delete-attachment">Supporting Documentation *</Label>
                                                    <div class="mt-1">
                                                        <input
                                                            type="file"
                                                            id="delete-attachment"
                                                            ref="deleteAttachmentRef"
                                                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                                            @change="handleDeleteFileSelect"
                                                            class="hidden"
                                                        />
                                                        <div
                                                            @click="triggerDeleteFileUpload"
                                                            class="border-2 border-dashed border-gray-300 rounded-md p-4 text-center cursor-pointer hover:border-gray-400 transition-colors"
                                                        >
                                                            <div v-if="!deleteAttachment" class="space-y-2">
                                                                <div class="text-gray-400">
                                                                    <svg class="mx-auto h-8 w-8" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                                    </svg>
                                                                </div>
                                                                <p class="text-sm text-gray-600">
                                                                    Click to upload supporting documents
                                                                </p>
                                                                <p class="text-xs text-gray-500">
                                                                    PDF, DOC, DOCX, JPG, PNG up to 5MB
                                                                </p>
                                                            </div>
                                                            <div v-else class="space-y-2">
                                                                <div class="text-green-600">
                                                                    <svg class="mx-auto h-8 w-8" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                                    </svg>
                                                                </div>
                                                                <p class="text-sm font-medium text-gray-900">
                                                                    {{ deleteAttachment.name }}
                                                                </p>
                                                                <p class="text-xs text-gray-500">
                                                                    {{ (deleteAttachment.size / 1024 / 1024).toFixed(2) }} MB
                                                                </p>
                                                                <button
                                                                    @click.stop="removeDeleteAttachment"
                                                                    class="text-xs text-red-600 hover:text-red-800"
                                                                >
                                                                    Remove file
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Inline Message -->
                                            <div v-if="deleteFormMessage" :class="[
                                                'mt-4 p-3 rounded-md text-sm',
                                                deleteFormMessageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'
                                            ]">
                                                {{ deleteFormMessage }}
                                            </div>

                                            <AlertDialogFooter>
                                                <AlertDialogCancel @click="clearDeleteForm">Cancel</AlertDialogCancel>
                                                <AlertDialogAction
                                                    @click="submitDeleteRequest(requirement)"
                                                    :disabled="!deleteReason.trim() || !deleteAttachment"
                                                    class="bg-red-600 hover:bg-red-700"
                                                >
                                                    Submit for Approval
                                                </AlertDialogAction>
                                            </AlertDialogFooter>
                                        </AlertDialogContent>
                                    </AlertDialog>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="flex items-center justify-between mt-4">
                <div class="text-sm text-gray-700">
                    Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, requirements.length) }} of {{ requirements.length }} entries
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
                            <DialogTitle>Request Shipping Requirement Changes</DialogTitle>
                            <DialogDescription>
                                Your changes will be submitted for approval. Supporting documentation is required.
                            </DialogDescription>
                        </DialogHeader>
                <div class="space-y-4">
                    <!-- Current Values (Read-only) -->
                    <div class="bg-gray-50 p-3 rounded-md">
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Current Values</h4>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div><strong>Region:</strong> {{ editingRequirement?.region }}</div>
                            <div><strong>Destination:</strong> {{ editingRequirement?.destination }}</div>
                            <div><strong>Risk Level:</strong> {{ editingRequirement?.risk_level }}</div>
                            <div><strong>Strength:</strong> {{ editingRequirement?.strength }}</div>
                            <div class="col-span-2"><strong>Requires GPS:</strong> {{ editingRequirement?.requires_gps ? 'Yes' : 'No' }}</div>
                        </div>
                    </div>

                    <!-- New Values -->
                    <div class="border-t pt-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Proposed Changes</h4>
                        <div class="space-y-3">
                            <div>
                                <Label for="edit-region">Region *</Label>
                                <Input id="edit-region" v-model="formData.region" placeholder="e.g., Americas, APAC, EMEA" />
                            </div>
                            <div>
                                <Label for="edit-destination">Destination *</Label>
                                <Input id="edit-destination" v-model="formData.destination" placeholder="e.g., Canada, Japan, Germany" />
                            </div>
                            <div>
                                <Label for="edit-risk_level">Risk Level *</Label>
                                <Select v-model="formData.risk_level">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select risk level" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="High">High</SelectItem>
                                        <SelectItem value="Medium">Medium</SelectItem>
                                        <SelectItem value="Low">Low</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label for="edit-strength">Strength *</Label>
                                <Input id="edit-strength" v-model="formData.strength" placeholder="e.g., 8, 3" />
                            </div>
                            <div class="flex items-center space-x-2">
                                <Checkbox id="edit-requires_gps" v-model="formData.requires_gps" />
                                <Label for="edit-requires_gps">Requires GPS</Label>
                            </div>
                        </div>
                    </div>

                    <!-- Attachment Upload -->
                    <div class="border-t pt-4">
                        <div>
                            <Label for="attachment">Supporting Documentation *</Label>
                            <div class="mt-1">
                                <input
                                    type="file"
                                    id="edit-attachment"
                                    ref="editAttachmentRef"
                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                    @change="handleFileSelect"
                                    class="hidden"
                                />
                                <div
                                    @click="() => editAttachmentRef?.click?.()"
                                    class="border-2 border-dashed border-gray-300 rounded-md p-4 text-center cursor-pointer hover:border-gray-400 transition-colors"
                                >
                                    <div v-if="!formData.attachment" class="space-y-2">
                                        <div class="text-gray-400">
                                            <svg class="mx-auto h-8 w-8" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </div>
                                        <p class="text-sm text-gray-600">
                                            Click to upload supporting documents
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            PDF, DOC, DOCX, JPG, PNG up to 5MB
                                        </p>
                                    </div>
                                    <div v-else class="space-y-2">
                                        <div class="text-green-600">
                                            <svg class="mx-auto h-8 w-8" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ formData.attachment.name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ (formData.attachment.size / 1024 / 1024).toFixed(2) }} MB
                                        </p>
                                        <button
                                            @click.stop="removeAttachment"
                                            class="text-xs text-red-600 hover:text-red-800"
                                        >
                                            Remove file
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                    <Button @click="updateRequirement" :disabled="formLoading">
                        <span v-if="formLoading">Submitting...</span>
                        <span v-else>Submit for Approval</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
