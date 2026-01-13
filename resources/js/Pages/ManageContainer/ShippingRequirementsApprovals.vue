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
                        <BreadcrumbPage>Shipping Requirements Approvals</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>
        </template>

        <Head title="Shipping Requirements Approvals" />

        <Card
            class="shadow-lg shadow-opacity-30 p-2 mb-4 text-2xl font-bold flex items-center justify-between bg-gray-100"
        >
            <div class="flex flex-row items-center">
                <img src="/assets/ss1.png" class="h-12 w-12" alt="" />
                <div>Shipping Requirements Approvals</div>
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
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <Card class="p-4 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Pending</p>
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
                        <p class="text-sm font-medium text-gray-600">Approved Today</p>
                        <p class="text-2xl font-bold text-green-600">{{ stats.approved_today }}</p>
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
                        <p class="text-sm font-medium text-gray-600">Rejected Today</p>
                        <p class="text-2xl font-bold text-red-600">{{ stats.rejected_today }}</p>
                    </div>
                    <div class="p-3 bg-red-100 rounded-full">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                </div>
            </Card>

            <Card class="p-4 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Processed</p>
                        <p class="text-2xl font-bold text-purple-600">{{ stats.total_processed }}</p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-full">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Tabs -->
        <div class="mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button
                        @click="activeTab = 'pending'"
                        :class="[
                            activeTab === 'pending'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        Pending Approvals
                        <span v-if="stats.pending > 0" class="ml-2 bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                            {{ stats.pending }}
                        </span>
                    </button>
                    <button
                        @click="activeTab = 'history'"
                        :class="[
                            activeTab === 'history'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        Change History
                    </button>
                </nav>
            </div>
        </div>

        <!-- Pending Approvals Tab -->
        <div v-if="activeTab === 'pending'" class="relative">
            <!-- Badge -->
            <div class="absolute -top-3 left-2 z-10">
                <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-semibold border shadow-md">
                    Pending Approvals
                </span>
            </div>

            <Card class="p-3 shadow-2xl max-h-[700px] shadow-opacity-60">
                <div class="flex flex-col space-y-4 mb-4">
                    <!-- Results Summary -->
                    <div v-if="!loading" class="text-sm text-gray-600">
                        Showing {{ pendingRequests.length }} pending request{{ pendingRequests.length !== 1 ? "s" : "" }}
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

                    <!-- Change Requests Table -->
                    <div v-else-if="pendingRequests.length > 0" class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                        <Table>
                            <TableHeader class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <TableRow class="border-b border-gray-200">
                                    <TableHead class="w-[200px] font-semibold text-gray-700 py-4">Requirement</TableHead>
                                    <TableHead class="w-[150px] font-semibold text-gray-700 py-4">Change Type</TableHead>
                                    <TableHead class="w-[130px] font-semibold text-gray-700 py-4">Status</TableHead>
                                    <TableHead class="w-[150px] font-semibold text-gray-700 py-4">Requested By</TableHead>
                                    <TableHead class="w-[220px] font-semibold text-gray-700 py-4">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="request in pendingRequests"
                                    :key="request.id"
                                    class="hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-200 border-b border-gray-50"
                                >
                                    <TableCell class="py-4">
                                        <div class="space-y-1">
                                            <div class="font-semibold text-blue-600">{{ request.shipping_requirement.destination }}</div>
                                            <div class="text-xs text-gray-500">{{ request.shipping_requirement.region }}</div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <span
                                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold"
                                            :class="{
                                                'bg-orange-100 text-orange-800': request.change_type === 'update',
                                                'bg-red-100 text-red-800': request.change_type === 'delete',
                                                'bg-green-100 text-green-800': request.change_type === 'create',
                                            }"
                                        >
                                            <svg v-if="request.change_type === 'update'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            <svg v-else-if="request.change_type === 'delete'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            <svg v-else-if="request.change_type === 'create'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            {{ request.change_type.charAt(0).toUpperCase() + request.change_type.slice(1) }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <span
                                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold shadow-sm"
                                            :class="{
                                                'bg-yellow-100 text-yellow-800 border border-yellow-200': request.status === 'pending',
                                                'bg-green-100 text-green-800 border border-green-200': request.status === 'approved',
                                                'bg-red-100 text-red-800 border border-red-200': request.status === 'rejected',
                                            }"
                                        >
                                            <svg v-if="request.status === 'pending'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <svg v-else-if="request.status === 'approved'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <svg v-else-if="request.status === 'rejected'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                            {{ request.status.charAt(0).toUpperCase() + request.status.slice(1) }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2 text-xs text-gray-600">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                <span class="font-medium">{{ request.requester.name }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span>{{ formatDate(request.created_at) }}</span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <div class="flex gap-2" @click.stop>
                                            <button
                                                v-if="request.status === 'pending'"
                                                @click="viewChangeDetails(request)"
                                                class="inline-flex items-center gap-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                View Details
                                            </button>
                                            <div v-if="request.status === 'pending'" class="flex gap-2">
                                                <button
                                                    @click="approveRequest(request)"
                                                    class="inline-flex items-center gap-1 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    Approve
                                                </button>
                                                <button
                                                    @click="rejectRequest(request)"
                                                    class="inline-flex items-center gap-1 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    Reject
                                                </button>
                                            </div>
                                            <div v-else class="space-y-1">
                                                <div class="flex items-center gap-2 text-xs text-gray-600">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    </svg>
                                                    <span class="font-medium">{{ request.reviewer?.name || "System" }}</span>
                                                </div>
                                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <span>{{ formatDate(request.reviewed_at) }}</span>
                                                </div>
                                                <div v-if="request.review_comments" class="text-xs text-gray-600 mt-1 p-2 bg-gray-50 rounded">
                                                    <strong>Comments:</strong> {{ request.review_comments }}
                                                </div>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <p class="text-gray-500">No pending requests found.</p>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Change History Tab -->
        <div v-if="activeTab === 'history'" class="relative">
            <!-- Badge -->
            <div class="absolute -top-3 left-2 z-10">
                <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded-full text-xs font-semibold border shadow-md">
                    Change History
                </span>
            </div>

            <Card class="p-3 shadow-2xl max-h-[700px] shadow-opacity-60">
                <div class="flex flex-col space-y-4 mb-4">
                    <!-- Filters Row -->
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2">
                            <div>Status :</div>
                            <select
                                v-model="historyFilterStatus"
                                @change="fetchChangeRequests"
                                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 px-3 py-2"
                            >
                                <option value="all">All Processed</option>
                                <option value="approved">Approved Only</option>
                                <option value="rejected">Rejected Only</option>
                            </select>
                        </div>
                    </div>

                    <!-- Results Summary -->
                    <div v-if="!loading" class="text-sm text-gray-600">
                        Showing {{ processedRequests.length }} processed request{{ processedRequests.length !== 1 ? "s" : "" }}
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

                    <!-- Change History Table -->
                    <div v-else-if="processedRequests.length > 0" class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                        <Table>
                            <TableHeader class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <TableRow class="border-b border-gray-200">
                                    <TableHead class="w-[200px] font-semibold text-gray-700 py-4">Requirement</TableHead>
                                    <TableHead class="w-[120px] font-semibold text-gray-700 py-4">Action</TableHead>
                                    <TableHead class="w-[150px] font-semibold text-gray-700 py-4">Processed By</TableHead>
                                    <TableHead class="w-[150px] font-semibold text-gray-700 py-4">Processed At</TableHead>
                                    <TableHead class="w-[200px] font-semibold text-gray-700 py-4">Details</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="request in processedRequests"
                                    :key="request.id"
                                    class="hover:bg-gradient-to-r hover:from-gray-50 hover:to-gray-50 transition-all duration-200 border-b border-gray-50"
                                >
                                    <TableCell class="py-4">
                                        <div class="space-y-1">
                                            <div class="font-semibold text-blue-600">{{ request.shipping_requirement.destination }}</div>
                                            <div class="text-xs text-gray-500">{{ request.shipping_requirement.region }}</div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <span
                                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold"
                                            :class="{
                                                'bg-green-100 text-green-800': request.status === 'approved',
                                                'bg-red-100 text-red-800': request.status === 'rejected',
                                            }"
                                        >
                                            <svg v-if="request.status === 'approved'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <svg v-else-if="request.status === 'rejected'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                            {{ request.status.charAt(0).toUpperCase() + request.status.slice(1) }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2 text-xs text-gray-600">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                <span class="font-medium">{{ request.reviewer?.name || "System" }}</span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <div class="text-sm text-gray-600">
                                            {{ formatDate(request.reviewed_at) }}
                                        </div>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <div class="space-y-2">
                                            <div class="text-xs text-gray-600">
                                                <strong>Requested by:</strong> {{ request.requester.name }}
                                            </div>
                                            <div class="text-xs text-gray-600">
                                                <strong>Requested:</strong> {{ formatDate(request.created_at) }}
                                            </div>
                                            <div v-if="request.review_comments" class="text-xs text-gray-600 mt-1 p-2 bg-gray-50 rounded">
                                                <strong>Comments:</strong> {{ request.review_comments }}
                                            </div>
                                            <button
                                                @click="viewChangeDetails(request)"
                                                class="text-xs text-blue-600 hover:text-blue-800 underline"
                                            >
                                                View full details
                                            </button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <p class="text-gray-500">No processed change requests found.</p>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Change Details Modal -->
        <Dialog :open="showDetailsModal" @open-change="showDetailsModal = false">
            <DialogContent class="max-w-2xl max-h-[80vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Change Request Details</DialogTitle>
                    <DialogDescription>
                        Review the proposed changes before approving or rejecting.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedRequest" class="space-y-6">
                    <!-- Current Values -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Current Shipping Requirement</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div><strong>Region:</strong> {{ selectedRequest.shipping_requirement.region }}</div>
                            <div><strong>Destination:</strong> {{ selectedRequest.shipping_requirement.destination }}</div>
                            <div><strong>Risk Level:</strong> {{ selectedRequest.shipping_requirement.risk_level }}</div>
                            <div><strong>Strength:</strong> {{ selectedRequest.shipping_requirement.strength_mm }}</div>
                            <div class="col-span-2"><strong>Requires Seals:</strong> {{ selectedRequest.shipping_requirement.requires_seals ? 'Yes' : 'No' }}</div>
                        </div>
                    </div>

                    <!-- Proposed Changes -->
                    <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-400">
                        <h4 class="text-sm font-medium text-blue-900 mb-3">Proposed Changes</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div><strong>Region:</strong> {{ selectedRequest.proposed_data.region }}</div>
                            <div><strong>Destination:</strong> {{ selectedRequest.proposed_data.destination }}</div>
                            <div><strong>Risk Level:</strong> {{ selectedRequest.proposed_data.risk_level }}</div>
                            <div><strong>Strength:</strong> {{ selectedRequest.proposed_data.strength_mm }}</div>
                            <div class="col-span-2"><strong>Requires Seals:</strong> {{ selectedRequest.proposed_data.requires_seals ? 'Yes' : 'No' }}</div>
                        </div>
                    </div>

                    <!-- Attachment -->
                    <div v-if="selectedRequest.attachment_path" class="bg-green-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-green-900 mb-2">Supporting Documentation</h4>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <a
                                :href="'/storage/' + selectedRequest.attachment_path"
                                target="_blank"
                                class="text-green-700 hover:text-green-800 underline font-medium"
                            >
                                View Attachment
                            </a>
                        </div>
                    </div>

                    <!-- Request Info -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Request Information</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div><strong>Requested By:</strong> {{ selectedRequest.requester.name }}</div>
                            <div><strong>Requested At:</strong> {{ formatDate(selectedRequest.created_at) }}</div>
                            <div><strong>Change Type:</strong> {{ selectedRequest.change_type.charAt(0).toUpperCase() + selectedRequest.change_type.slice(1) }}</div>
                            <div><strong>Status:</strong> {{ selectedRequest.status.charAt(0).toUpperCase() + selectedRequest.status.slice(1) }}</div>
                        </div>
                    </div>
                </div>

                <DialogFooter v-if="selectedRequest && selectedRequest.status === 'pending'">
                    <Button variant="outline" @click="showDetailsModal = false">
                        Close
                    </Button>
                    <Button @click="approveRequest(selectedRequest)" class="bg-green-600 hover:bg-green-700">
                        Approve Changes
                    </Button>
                    <Button @click="rejectRequest(selectedRequest)" variant="destructive">
                        Reject Changes
                    </Button>
                </DialogFooter>
                <DialogFooter v-else-if="selectedRequest">
                    <div class="text-center text-gray-500">
                        {{ selectedRequest.status.charAt(0).toUpperCase() + selectedRequest.status.slice(1) }}
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Reject Modal -->
        <Dialog :open="showRejectModal" @open-change="showRejectModal = false">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Reject Change Request</DialogTitle>
                    <DialogDescription>
                        Please provide a reason for rejecting this change request.
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
import {
    Breadcrumb,
    BreadcrumbPage,
    BreadcrumbSeparator,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
} from "@/components/ui/breadcrumb";
import axios from "axios";

const changeRequests = ref([]);
const loading = ref(false);
const selectedStatus = ref("pending");
const historyFilterStatus = ref("all");
const showRejectModal = ref(false);
const showDetailsModal = ref(false);
const rejectRemarks = ref("");
const selectedRequest = ref(null);
const activeTab = ref("pending");

const currentTime = ref(new Date());
const stats = ref({
    pending: 0,
    approved_today: 0,
    rejected_today: 0,
    total_processed: 0,
});

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

const pendingRequests = computed(() =>
    changeRequests.value.filter(request => request.status === 'pending')
);

const processedRequests = computed(() => {
    let filtered = changeRequests.value.filter(request => request.status !== 'pending');

    if (historyFilterStatus.value === 'approved') {
        filtered = filtered.filter(request => request.status === 'approved');
    } else if (historyFilterStatus.value === 'rejected') {
        filtered = filtered.filter(request => request.status === 'rejected');
    }

    return filtered;
});

const fetchChangeRequests = async () => {
    loading.value = true;
    try {
        const params = {};
        if (selectedStatus.value !== "all") {
            params.status = selectedStatus.value;
        }

        const response = await axios.get("/api/shipping-requirements/pending-change-requests", { params });
        changeRequests.value = response.data.data;

        // Calculate stats
        const today = new Date().toDateString();
        stats.value = {
            pending: response.data.data.filter(r => r.status === 'pending').length,
            approved_today: response.data.data.filter(r => r.status === 'approved' && new Date(r.reviewed_at).toDateString() === today).length,
            rejected_today: response.data.data.filter(r => r.status === 'rejected' && new Date(r.reviewed_at).toDateString() === today).length,
            total_processed: response.data.data.filter(r => r.status !== 'pending').length,
        };
    } catch (error) {
        console.error("Error fetching change requests:", error);
    } finally {
        loading.value = false;
    }
};

const approveRequest = async (request) => {
    try {
        await axios.post(`/api/shipping-requirements/approve-change/${request.id}`);
        await fetchChangeRequests(); // Refresh the list
        showDetailsModal.value = false;
    } catch (error) {
        console.error("Error approving request:", error);
        alert("Failed to approve change request");
    }
};

const rejectRequest = (request) => {
    selectedRequest.value = request;
    rejectRemarks.value = "";
    showRejectModal.value = true;
    showDetailsModal.value = false;
};

const confirmReject = async () => {
    if (!rejectRemarks.value.trim()) return;

    try {
        await axios.post(
            `/api/shipping-requirements/reject-change/${selectedRequest.value.id}`,
            {
                review_comments: rejectRemarks.value,
            }
        );
        showRejectModal.value = false;
        selectedRequest.value = null;
        rejectRemarks.value = "";
        await fetchChangeRequests(); // Refresh the list
    } catch (error) {
        console.error("Error rejecting request:", error);
        alert("Failed to reject change request");
    }
};

const viewChangeDetails = (request) => {
    selectedRequest.value = request;
    showDetailsModal.value = true;
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

    fetchChangeRequests();

    // Listen for real-time updates
    if (window.Echo) {
        window.Echo.private('shipping-requirements')
            .listen('.change.requested', (e) => {
                console.log('🔄 New change request received via WebSocket:', e);
                // Refresh the change requests list
                fetchChangeRequests();
            })
            .listen('.change.processed', (e) => {
                console.log('✅ Change request processed via WebSocket:', e);
                // Refresh the change requests list
                fetchChangeRequests();
            })
            .error((error) => {
                console.error('❌ WebSocket error on shipping-requirements channel:', error);
            });

        console.log('🎧 Listening for shipping requirement change events on channel: shipping-requirements');
    } else {
        console.error('❌ Laravel Echo is not initialized. Please check resources/js/app.js.');
    }
});

onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }

    // Clean up WebSocket listeners
    if (window.Echo) {
        window.Echo.leave('shipping-requirements');
        console.log('Stopped listening for shipping requirement change events.');
    }
});
</script>
