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
                        <BreadcrumbPage>Archive Container Reports</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>
        </template>

        <Head title="Archive Container Reports" />

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

        <div class="relative">
            <!-- Badge -->
            <div class="absolute -top-3 left-2 z-10">
                <span
                    class="bg-gray-100 text-black px-2 py-1 rounded-full text-xs font-semibold border shadow-md"
                >
                    Archive Container Reports
                </span>
            </div>

            <Card class="p-3 shadow-2xl max-h-[700px] shadow-opacity-60">
                <div class="flex flex-col space-y-4 mb-4">
                    <!-- Search Row -->
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2">
                            <div>Search :</div>
                            <input
                                v-model="searchQuery"
                                @input="debouncedSearch"
                                type="text"
                                placeholder="Search by container truck number or approver names..."
                                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 px-4 py-2"
                            />
                        </div>
                    </div>

                    <!-- Results Summary -->
                    <div v-if="!loading" class="text-sm text-gray-600">
                        Showing {{ reports.length }} of {{ total }} archive report{{
                            total !== 1 ? "s" : ""
                        }}
                        <span v-if="currentPage > 1"> (Page {{ currentPage }} of {{ lastPage }})</span>
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

                    <!-- Reports Table -->
                    <div v-else-if="reports.length > 0" class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                        <Table>
                            <TableHeader class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <TableRow class="border-b border-gray-200">
                                    <TableHead class="w-[150px] font-semibold text-gray-700 py-4">Container Truck</TableHead>
                                    <TableHead class="w-[120px] font-semibold text-gray-700 py-4">Loading Date</TableHead>
                                    <TableHead class="w-[150px] font-semibold text-gray-700 py-4">Warehouse Approver</TableHead>
                                    <TableHead class="w-[120px] font-semibold text-gray-700 py-4">QA Approver</TableHead>
                                    <TableHead class="w-[120px] font-semibold text-gray-700 py-4">Shipping Approver</TableHead>
                                    <TableHead class="w-[120px] font-semibold text-gray-700 py-4">Security Approver</TableHead>
                                    <TableHead class="w-[100px] font-semibold text-gray-700 py-4">Photos</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="detail in reports"
                                    :key="detail.id"
                                    class="hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-200 border-b border-gray-50"
                                >
                                    <TableCell class="py-4">
                                        <span
                                            class="font-medium text-blue-600 hover:text-blue-800 cursor-pointer hover:underline"
                                            @click="viewContainerDetails(detail)"
                                        >
                                            {{ detail.container_truck_number || 'N/A' }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <span class="text-sm text-gray-600">
                                            {{ formatDate(detail.archive_container_report?.skp_loadingdate) }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <div class="space-y-1">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ detail.archive_container_report?.skp_stapprovalnamewarehouse || 'N/A' }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ formatDateTime(detail.archive_container_report?.skp_approvalrequestdatetime) }}
                                            </div>
                                            <div class="text-xs">
                                                <span
                                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold"
                                                    :class="{
                                                        'bg-green-100 text-green-800': detail.archive_container_report?.skp_stapprovalresultwarehouse === 'Approved',
                                                        'bg-red-100 text-red-800': detail.archive_container_report?.skp_stapprovalresultwarehouse === 'Rejected',
                                                        'bg-yellow-100 text-yellow-800': detail.archive_container_report?.skp_stapprovalresultwarehouse === 'Pending',
                                                        'bg-gray-100 text-gray-800': !detail.archive_container_report?.skp_stapprovalresultwarehouse
                                                    }"
                                                >
                                                    {{ detail.archive_container_report?.skp_stapprovalresultwarehouse || 'N/A' }}
                                                </span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <div class="space-y-1">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ detail.archive_container_report?.skp_ndapprovalnameqa || 'N/A' }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ formatDateTime(detail.archive_container_report?.skp_ndapprovalrequestdatetime) }}
                                            </div>
                                            <div class="text-xs">
                                                <span
                                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold"
                                                    :class="{
                                                        'bg-green-100 text-green-800': detail.archive_container_report?.skp_ndapprovalresultqa === 'Approved',
                                                        'bg-red-100 text-red-800': detail.archive_container_report?.skp_ndapprovalresultqa === 'Rejected',
                                                        'bg-yellow-100 text-yellow-800': detail.archive_container_report?.skp_ndapprovalresultqa === 'Pending',
                                                        'bg-gray-100 text-gray-800': !detail.archive_container_report?.skp_ndapprovalresultqa
                                                    }"
                                                >
                                                    {{ detail.archive_container_report?.skp_ndapprovalresultqa || 'N/A' }}
                                                </span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <div class="space-y-1">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ detail.archive_container_report?.skp_rdapprovalnameshipping || 'N/A' }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ formatDateTime(detail.archive_container_report?.skp_rdapprovalrequestdatetime) }}
                                            </div>
                                            <div class="text-xs">
                                                <span
                                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold"
                                                    :class="{
                                                        'bg-green-100 text-green-800': detail.archive_container_report?.skp_rdapprovalresultshipping === 'Approved',
                                                        'bg-red-100 text-red-800': detail.archive_container_report?.skp_rdapprovalresultshipping === 'Rejected',
                                                        'bg-yellow-100 text-yellow-800': detail.archive_container_report?.skp_rdapprovalresultshipping === 'Pending',
                                                        'bg-gray-100 text-gray-800': !detail.archive_container_report?.skp_rdapprovalresultshipping
                                                    }"
                                                >
                                                    {{ detail.archive_container_report?.skp_rdapprovalresultshipping || 'N/A' }}
                                                </span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <div class="space-y-1">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ detail.archive_container_report?.skp_thapprovalnamesecurity || 'N/A' }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ formatDateTime(detail.archive_container_report?.skp_thapprovalrequestdatetime) }}
                                            </div>
                                            <div class="text-xs">
                                                <span
                                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold"
                                                    :class="{
                                                        'bg-green-100 text-green-800': detail.archive_container_report?.skp_thapprovalresultsecurity === 'Approved',
                                                        'bg-red-100 text-red-800': detail.archive_container_report?.skp_thapprovalresultsecurity === 'Rejected',
                                                        'bg-yellow-100 text-yellow-800': detail.archive_container_report?.skp_thapprovalresultsecurity === 'Pending',
                                                        'bg-gray-100 text-gray-800': !detail.archive_container_report?.skp_thapprovalresultsecurity
                                                    }"
                                                >
                                                    {{ detail.archive_container_report?.skp_thapprovalresultsecurity || 'N/A' }}
                                                </span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <button
                                            v-if="detail.archive_container_report?.photos && hasValidPhotos(detail.archive_container_report.photos)"
                                            @click="viewPhotos(detail)"
                                            class="inline-flex items-center gap-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-lg text-xs font-medium transition-all duration-200"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            View ({{ getValidPhotoCount(detail.archive_container_report?.photos) }})
                                        </button>
                                        <span v-else class="text-xs text-gray-500">No photos</span>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <p class="text-gray-500">No archive reports found.</p>
                    </div>
                </div>

                <!-- Pagination Controls -->
                <div v-if="lastPage > 1" class="flex items-center justify-between mt-4 px-4 py-3 bg-gray-50 border-t border-gray-200">
                    <div class="flex items-center gap-2">
                        <button
                            @click="goToPage(currentPage - 1)"
                            :disabled="currentPage <= 1"
                            class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Previous
                        </button>

                        <div class="flex items-center gap-1">
                            <button
                                v-for="page in visiblePages"
                                :key="page"
                                @click="goToPage(page)"
                                :class="[
                                    'px-3 py-1 text-sm border rounded-md',
                                    page === currentPage
                                        ? 'bg-blue-600 text-white border-blue-600'
                                        : 'border-gray-300 hover:bg-gray-100'
                                ]"
                            >
                                {{ page }}
                            </button>
                        </div>

                        <button
                            @click="goToPage(currentPage + 1)"
                            :disabled="currentPage >= lastPage"
                            class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Next
                        </button>
                    </div>

                    <div class="text-sm text-gray-600">
                        Page {{ currentPage }} of {{ lastPage }}
                    </div>
                </div>
            </Card>
        </div>

        <!-- Photos Modal -->
        <Teleport to="body">
            <Transition name="modal-fade">
                <div
                    v-if="showPhotosModal"
                    class="fixed inset-0 bg-black/50 backdrop-blur-md flex items-center justify-center z-[9999]"
                    @click.self="closePhotosModal"
                >
                    <Transition name="modal-scale" appear>
                        <div
                            v-if="showPhotosModal"
                            class="bg-white p-6 rounded-lg shadow-[0_4px_20px_rgba(255,255,255,0.6)] w-[95%] max-w-5xl max-h-[90vh] overflow-hidden"
                        >
                            <div class="flex justify-between items-center mb-6 pb-4 border-b">
                                <div>
                                    <h2 class="text-2xl font-bold text-red-700">
                                        Container Photos - {{ selectedReport?.containertrucknumber || 'N/A' }}
                                    </h2>
                                    <p class="text-sm text-gray-600 mt-1">
                                        Click photos to enlarge. Press ESC or click close to exit.
                                    </p>
                                </div>
                                <button
                                    @click="closePhotosModal"
                                    class="text-gray-500 hover:text-gray-700 text-2xl font-bold w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors"
                                >
                                    ×
                                </button>
                            </div>

                            <div v-if="selectedReport && selectedReport.photos" class="overflow-y-auto max-h-[60vh] pr-2">
                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                                    <div
                                        v-for="(photoPath, photoType) in selectedReport.photos"
                                        :key="photoType"
                                        class="flex flex-col items-center space-y-3"
                                    >
                                        <div class="text-sm font-medium text-gray-700 text-center min-h-[2.5rem] flex items-center capitalize w-full">
                                            {{ photoType.replace(/_/g, ' ') }}
                                        </div>
                                        <div class="w-full max-w-[200px] aspect-square">
                                            <div v-if="photoPath && photoPath.trim()" class="relative w-full h-full">
                                                <img
                                                    :src="'/storage/' + photoPath"
                                                    :alt="photoType"
                                                    class="w-full h-full object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity"
                                                    @click="openImageModal('/storage/' + photoPath, photoType)"
                                                    @error="handleImageError"
                                                />
                                            </div>
                                            <div v-else class="w-full h-full bg-gray-100 rounded-lg border border-gray-200 flex items-center justify-center">
                                                <div class="text-center text-gray-500">
                                                    <svg class="w-8 h-8 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    <p class="text-xs">No image</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end mt-6 pt-4 border-t">
                                <button
                                    @click="closePhotosModal"
                                    class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-md text-sm font-medium transition-colors"
                                >
                                    Close
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- Full Image Modal -->
        <Dialog :open="showImageModal" @open-change="showImageModal = false">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ selectedImageType.replace(/_/g, ' ') }}</DialogTitle>
                </DialogHeader>

                <div class="flex justify-center">
                    <img
                        :src="selectedImagePath"
                        :alt="selectedImageType"
                        class="max-w-full max-h-[60vh] object-contain rounded-lg"
                    />
                </div>

                <DialogFooter class="flex justify-center">
                    <Button
                        variant="outline"
                        @click="showImageModal = false"
                        class="px-6 py-2 text-lg font-semibold"
                    >
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Container Details Modal -->
        <Teleport to="body">
            <Transition name="modal-fade">
                <div
                    v-if="showDetailsModal"
                    class="fixed inset-0 bg-black/50 backdrop-blur-md flex items-center justify-center z-[9999]"
                    @click.self="closeDetailsModal"
                >
                    <Transition name="modal-scale" appear>
                        <div
                            v-if="showDetailsModal"
                            class="bg-white p-6 rounded-lg shadow-[0_4px_20px_rgba(255,255,255,0.6)] w-[95%] max-w-4xl max-h-[90vh] overflow-hidden"
                        >
                            <div class="flex justify-between items-center mb-6 pb-4 border-b">
                                <div>
                                    <h2 class="text-2xl font-bold text-blue-700">
                                        Container Details - {{ selectedReport?.container_truck_number || 'N/A' }}
                                    </h2>
                                    <p class="text-sm text-gray-600 mt-1">
                                        Complete container information from archive
                                    </p>
                                </div>
                                <button
                                    @click="closeDetailsModal"
                                    class="text-gray-500 hover:text-gray-700 text-2xl font-bold w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors"
                                >
                                    ×
                                </button>
                            </div>

                            <div v-if="selectedReport" class="overflow-y-auto max-h-[70vh] pr-2">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Basic Information -->
                                    <div class="space-y-4">
                                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Basic Information</h3>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Container/Truck</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.container_truck || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Container Number</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.container_truck_number || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Date</label>
                                                <p class="text-sm text-gray-900">{{ formatDate(selectedReport.date) }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Country</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.country || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Forwarder</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.forwarder || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Container Size</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.container_size || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Hauler</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.hauler || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">SKU Number</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.sku_number || 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Technical Details -->
                                    <div class="space-y-4">
                                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Technical Details</h3>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Model/Project</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.model_project || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Work Order</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.work_order || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">High Security Seal</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.high_security_seal ? 'Yes' : 'No' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">High Security Seal SN</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.high_security_seal_sn || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">GPS</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.gps ? 'Yes' : 'No' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">GPS Country</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.gps_country || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Fork Seal</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.fork_seal ? 'Yes' : 'No' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Fork Seal Size</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.fork_seal_size || 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- GPS & Seal Details -->
                                    <div class="space-y-4 md:col-span-2">
                                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">GPS & Seal Serial Numbers</h3>

                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Outside GPS SN</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.outside_gps_sn || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Inside GPS SN</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.inside_gps_sn || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Fork Seal SN</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.fork_seal_sn || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Temporary Seal</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.temporary_seal ? 'Yes' : 'No' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Temporary Seal SN</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.temporary_seal_sn || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">SKP Site</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.skp_site || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Created By</label>
                                                <p class="text-sm text-gray-900">{{ selectedReport.created_by || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Created On</label>
                                                <p class="text-sm text-gray-900">{{ formatDateTime(selectedReport.created_on) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center py-8">
                                <p class="text-gray-500">No container details available.</p>
                            </div>

                            <div class="flex justify-end mt-6 pt-4 border-t">
                                <button
                                    @click="closeDetailsModal"
                                    class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-md text-sm font-medium transition-colors"
                                >
                                    Close
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
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
import { Card } from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    Breadcrumb,
    BreadcrumbPage,
    BreadcrumbSeparator,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
} from "@/components/ui/breadcrumb";
import axios from "axios";

const reports = ref([]);
const loading = ref(false);
const searchQuery = ref("");
const showPhotosModal = ref(false);
const showImageModal = ref(false);
const showDetailsModal = ref(false);
const selectedReport = ref(null);
const selectedImagePath = ref("");
const selectedImageType = ref("");

// Pagination data
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);

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

// Pagination computed properties
const visiblePages = computed(() => {
    const pages = [];
    const totalPages = lastPage.value;
    const current = currentPage.value;

    // Show max 5 page buttons around current page
    let start = Math.max(1, current - 2);
    let end = Math.min(totalPages, current + 2);

    // Adjust if we're near the beginning or end
    if (end - start < 4) {
        if (start === 1) {
            end = Math.min(totalPages, start + 4);
        } else if (end === totalPages) {
            start = Math.max(1, end - 4);
        }
    }

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }

    return pages;
});

// Debounce search
let searchTimeout;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchReports();
    }, 500);
};

const fetchReports = async (page = 1) => {
    loading.value = true;
    try {
        const params = { page };
        if (searchQuery.value) {
            params.search = searchQuery.value;
        }

        const response = await axios.get("/api/archive-container-reports", { params });
        reports.value = response.data.data;
        currentPage.value = response.data.current_page;
        lastPage.value = response.data.last_page;
        total.value = response.data.total;
    } catch (error) {
        console.error("Error fetching archive reports:", error);
    } finally {
        loading.value = false;
    }
};

const viewContainerDetails = (report) => {
    selectedReport.value = report;
    showDetailsModal.value = true;
};

const viewPhotos = (detail) => {
    selectedReport.value = detail.archive_container_report;
    showPhotosModal.value = true;
};

const openImageModal = (imagePath, imageType) => {
    selectedImagePath.value = imagePath;
    selectedImageType.value = imageType;
    showPhotosModal.value = false; // Close photos modal first
    showImageModal.value = true; // Then open image modal
};

const closePhotosModal = () => {
    showPhotosModal.value = false;
    selectedReport.value = null;
};

const closeDetailsModal = () => {
    showDetailsModal.value = false;
    selectedReport.value = null;
};

const hasValidPhotos = (photos) => {
    if (!photos || typeof photos !== 'object') return false;
    return Object.values(photos).some(path => path && path.trim());
};

const getValidPhotoCount = (photos) => {
    if (!photos || typeof photos !== 'object') return 0;
    return Object.values(photos).filter(path => path && path.trim()).length;
};

const handleImageError = (event) => {
    // Hide the broken image and show placeholder
    event.target.style.display = 'none';
    const placeholder = event.target.parentElement.querySelector('.image-placeholder');
    if (placeholder) {
        placeholder.style.display = 'flex';
    }
};

const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    return new Date(dateString).toLocaleDateString();
};

const formatDateTime = (dateString) => {
    if (!dateString) return "N/A";
    return new Date(dateString).toLocaleString();
};

const goToPage = (page) => {
    if (page >= 1 && page <= lastPage.value) {
        fetchReports(page);
    }
};

onMounted(() => {
    const intervalId = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);

    fetchReports();

    // Cleanup interval on unmount
    return () => clearInterval(intervalId);
});
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.modal-scale-enter-active,
.modal-scale-leave-active {
    transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.modal-scale-enter-from,
.modal-scale-leave-to {
    opacity: 0;
    transform: perspective(1000px) rotateX(-90deg) scale(0.3) translateY(-200px);
}
</style>
