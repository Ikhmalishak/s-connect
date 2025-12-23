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
    DialogTrigger,
    DialogFooter,
} from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
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
    strength_mm: "",
    requires_seals: false,
});
const formLoading = ref(false);

// Toast
const { toast } = useToast();

// Computed properties
const sortedRequirements = computed(() => {
    return [...requirements.value].sort((a, b) => {
        let aVal = a[sortBy.value];
        let bVal = b[sortBy.value];

        if (sortBy.value === 'requires_seals') {
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
        strength_mm: "",
        requires_seals: false,
    };
    showCreateDialog.value = true;
};

const openEditDialog = (requirement: any) => {
    editingRequirement.value = requirement;
    formData.value = {
        region: requirement.region,
        destination: requirement.destination,
        risk_level: requirement.risk_level,
        strength_mm: requirement.strength_mm,
        requires_seals: requirement.requires_seals,
    };
    showEditDialog.value = true;
};

const createRequirement = async () => {
    formLoading.value = true;
    try {
        await axios.post('/api/shipping-requirements', formData.value);
        toast({
            title: "Success",
            description: "Shipping requirement created successfully",
                        variant: "destroy",
        });
        showCreateDialog.value = false;
        fetchRequirements();
    } catch (error: any) {
        console.error("Failed to create requirement:", error);
        const message = error.response?.data?.message || "Failed to create shipping requirement";
        toast({
            title: "Error",
            description: message,
            variant: "destructive",
        });
    } finally {
        formLoading.value = false;
    }
};

const updateRequirement = async () => {
    if (!editingRequirement.value) return;

    formLoading.value = true;
    try {
        await axios.put(`/api/shipping-requirements/${editingRequirement.value.id}`, formData.value);
        toast({
            title: "Success",
            description: "Shipping requirement updated successfully",
        });
        showEditDialog.value = false;
        editingRequirement.value = null;
        fetchRequirements();
    } catch (error: any) {
        console.error("Failed to update requirement:", error);
        const message = error.response?.data?.message || "Failed to update shipping requirement";
        toast({
            title: "Error",
            description: message,
            variant: "destructive",
        });
    } finally {
        formLoading.value = false;
    }
};

const deleteRequirement = async (requirement: any) => {
    try {
        await axios.delete(`/api/shipping-requirements/${requirement.id}`);
        toast({
            title: "Success",
            description: "Shipping requirement deleted successfully",
        });
        fetchRequirements();
    } catch (error: any) {
        console.error("Failed to delete requirement:", error);
        toast({
            title: "Error",
            description: "Failed to delete shipping requirement",
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

// Lifecycle
onMounted(() => {
    fetchRequirements();
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
                    <DialogContent class="max-w-md">
                        <DialogHeader>
                            <DialogTitle>Add Shipping Requirement</DialogTitle>
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
                                <Label for="strength_mm">Strength (mm) *</Label>
                                <Input id="strength_mm" v-model="formData.strength_mm" placeholder="e.g., 8mm, 3mm" />
                            </div>
                            <div class="flex items-center space-x-2">
                                <Checkbox id="requires_seals" v-model="formData.requires_seals" />
                                <Label for="requires_seals">Requires GPS and Fork Seals</Label>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" @click="showCreateDialog = false">Cancel</Button>
                            <Button @click="createRequirement" :disabled="formLoading">
                                <span v-if="formLoading">Creating...</span>
                                <span v-else>Create</span>
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </Card>

        <!-- Requirements Table -->
        <Card class="p-6">
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
                                @click="handleSort('requires_seals')"
                            >
                                <div class="flex items-center">
                                    Requires Seals
                                    <ChevronUp
                                        v-if="sortBy === 'requires_seals' && sortDirection === 'asc'"
                                        class="h-4 w-4 ml-1"
                                    />
                                    <ChevronDown
                                        v-if="sortBy === 'requires_seals' && sortDirection === 'desc'"
                                        class="h-4 w-4 ml-1"
                                    />
                                </div>
                            </TableHead>
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
                        <TableRow v-else v-for="requirement in paginatedRequirements" :key="requirement.id">
                            <TableCell class="font-medium">{{ requirement.region }}</TableCell>
                            <TableCell>{{ requirement.destination }}</TableCell>
                            <TableCell>
                                <Badge :variant="getRiskBadgeVariant(requirement.risk_level)">
                                    {{ requirement.risk_level }}
                                </Badge>
                            </TableCell>
                            <TableCell>{{ requirement.strength_mm }}</TableCell>
                            <TableCell>
                                <Badge :variant="getSealsBadgeVariant(requirement.requires_seals)">
                                    {{ requirement.requires_seals ? 'Yes' : 'No' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="openEditDialog(requirement)"
                                    >
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <AlertDialog>
                                        <AlertDialogTrigger as-child>
                                            <Button variant="outline" size="sm" class="text-red-600 hover:text-red-700">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </AlertDialogTrigger>
                                        <AlertDialogContent>
                                            <AlertDialogHeader>
                                                <AlertDialogTitle>Delete Shipping Requirement</AlertDialogTitle>
                                                <AlertDialogDescription>
                                                    Are you sure you want to delete the requirement for {{ requirement.destination }}?
                                                    This action cannot be undone.
                                                </AlertDialogDescription>
                                            </AlertDialogHeader>
                                            <AlertDialogFooter>
                                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                <AlertDialogAction
                                                    @click="deleteRequirement(requirement)"
                                                    class="bg-red-600 hover:bg-red-700"
                                                >
                                                    Delete
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
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Edit Shipping Requirement</DialogTitle>
                </DialogHeader>
                <div class="space-y-4">
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
                        <Label for="edit-strength_mm">Strength (mm) *</Label>
                        <Input id="edit-strength_mm" v-model="formData.strength_mm" placeholder="e.g., 8mm, 3mm" />
                    </div>
                    <div class="flex items-center space-x-2">
                        <Checkbox id="edit-requires_seals" v-model="formData.requires_seals" />
                        <Label for="edit-requires_seals">Requires GPS and Fork Seals</Label>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showEditDialog = false">Cancel</Button>
                    <Button @click="updateRequirement" :disabled="formLoading">
                        <span v-if="formLoading">Updating...</span>
                        <span v-else>Update</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
