<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Card } from "@/components/ui/card";
import { ref, computed, onMounted, onUnmounted } from "vue";
import {
    Breadcrumb,
    BreadcrumbPage,
    BreadcrumbSeparator,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
} from "@/components/ui/breadcrumb";
import SafetyStatCard from "@/Components/ManageSafety/SafetyStatCard.vue";
import SafetyTable from "@/Components/ManageSafety/SafetyTable.vue";
import ViewAuditDetailsModal from "@/Components/ManageSafety/ViewAuditDetailsModal.vue";
import CreateAuditModal from "@/Components/ManageSafety/CreateAuditModal.vue";

const currentTime = ref(new Date());
const openCreateAuditModal = ref(false);
const openAuditDetailModal = ref(false);
const currentAuditDetailDetails = ref<any>(null);
let intervalId: ReturnType<typeof setInterval>;

// Ref to trigger table refresh
const tableRefreshKey = ref(0);

function handleOpenAuditDetailModal(audit: any) {
    console.log("Opening View Details for audit:", audit?.id);
    currentAuditDetailDetails.value = audit;
    openAuditDetailModal.value = true;
}

const formattedDate = computed(() =>
    currentTime.value.toLocaleDateString("en-GB", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
    }),
);

const formattedTime = computed(() =>
    currentTime.value.toLocaleTimeString("en-GB"),
);

onMounted(() => {
    console.log("Mounted VisitorTable.vue");

    intervalId = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
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
                            >Safety Inspection Management System</BreadcrumbLink
                        >
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbPage>Dashboard</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>
        </template>

        <Card
            class="shadow-lg shadow-opacity-30 p-2 mb-4 text-2xl font-bold flex items-center justify-between bg-gray-100"
        >
            <div class="flex flex-row items-center">
                <img src="/assets/ss1.png" class="h-12 w-12" alt="" />
                <div>Safety Inspection Management System</div>
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
        <SafetyStatCard />
        <SafetyTable
            :key="tableRefreshKey"
            @open-audit-detail-modal="handleOpenAuditDetailModal"
            @open-create-audit-modal="openCreateAuditModal = true"
        />

        <ViewAuditDetailsModal
            v-model:show="openAuditDetailModal"
            :id="currentAuditDetailDetails"
            @close="
                () => {
                    openAuditDetailModal = false;
                }
            "
        />

        <CreateAuditModal
            v-model:show="openCreateAuditModal"
            @close="
                () => {
                    openCreateAuditModal = false;
                    tableRefreshKey++;
                }
            "
            @save="
                () => {
                    tableRefreshKey++;
                }
            "
        />
    </AuthenticatedLayout>
</template>