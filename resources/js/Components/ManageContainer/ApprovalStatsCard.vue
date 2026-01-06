<template>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <!-- Pending Approvals Card -->
        <div class="bg-gradient-to-r from-yellow-400 to-yellow-500 rounded-lg p-6 text-white shadow-lg hover:shadow-xl transition-shadow duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm font-medium">Pending Approvals</p>
                    <p class="text-3xl font-bold">{{ stats.pending }}</p>
                </div>
                <div class="bg-yellow-300 bg-opacity-30 rounded-full p-3">
                    <svg class="w-8 h-8 text-yellow-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Approved Approvals Card -->
        <div class="bg-gradient-to-r from-green-400 to-green-500 rounded-lg p-6 text-white shadow-lg hover:shadow-xl transition-shadow duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">Approved</p>
                    <p class="text-3xl font-bold">{{ stats.approved }}</p>
                </div>
                <div class="bg-green-300 bg-opacity-30 rounded-full p-3">
                    <svg class="w-8 h-8 text-green-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Rejected Approvals Card -->
        <div class="bg-gradient-to-r from-red-400 to-red-500 rounded-lg p-6 text-white shadow-lg hover:shadow-xl transition-shadow duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm font-medium">Rejected</p>
                    <p class="text-3xl font-bold">{{ stats.rejected }}</p>
                </div>
                <div class="bg-red-300 bg-opacity-30 rounded-full p-3">
                    <svg class="w-8 h-8 text-red-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    approvals: {
        type: Array,
        default: () => []
    }
});

const stats = computed(() => {
    const pending = props.approvals.filter(approval => approval.approval_status === 'pending').length;
    const approved = props.approvals.filter(approval => approval.approval_status === 'approved').length;
    const rejected = props.approvals.filter(approval => approval.approval_status === 'rejected').length;

    return {
        pending,
        approved,
        rejected
    };
});
</script>
