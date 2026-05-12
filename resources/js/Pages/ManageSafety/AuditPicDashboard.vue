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
import AuditPicTable from "@/Components/ManageSafety/AuditPicTable.vue";
import CreateAuditPicModal from "@/Components/ManageSafety/CreateAuditPicModal.vue";
import DeleteAuditPicModal from "@/Components/ManageSafety/DeleteAuditPicModal.vue";

const currentTime = ref(new Date());
let intervalId: ReturnType<typeof setInterval>;

const showCreatePicModal = ref(false);
const showDeletePicModal = ref(false);
const selectedPic = ref<any>(null);

// Refresh trigger
const refreshKey = ref(0);

function handleOpenCreatePicModal() {
    showCreatePicModal.value = true;
}

function handleOpenDeletePicModal(pic: any) {
    selectedPic.value = pic;
    showDeletePicModal.value = true;
}

function handlePicSaved() {
    refreshKey.value++;
}

function handlePicDeleted() {
    refreshKey.value++;
}

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

onMounted(() => {
    intervalId = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
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
                        <BreadcrumbLink href="/">EHS Management System</BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbPage>Manage PIC (EHS Audit)</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>
        </template>

        <Card class="shadow-lg shadow-opacity-30 p-2 mb-4 text-2xl font-bold flex items-center justify-between bg-gray-100">
            <div class="flex flex-row items-center">
                <img src="/assets/ss1.png" class="h-12 w-12" alt="" />
                <div>EHS Management System</div>
            </div>
            <div class="flex flex-row items-center gap-10">
                <div class="flex flex-row space-x-4 text-base font-normal text-gray-600 text-right">
                    <div>{{ formattedDate }}</div>
                    <div>{{ formattedTime }}</div>
                </div>
            </div>
        </Card>

        <AuditPicTable
            :key="'table-' + refreshKey"
            @open-create-pic-modal="handleOpenCreatePicModal"
            @open-delete-pic-modal="handleOpenDeletePicModal"
        />

        <CreateAuditPicModal
            :show="showCreatePicModal"
            @close="showCreatePicModal = false"
            @saved="handlePicSaved"
        />

        <DeleteAuditPicModal
            :show="showDeletePicModal"
            :pic="selectedPic"
            @close="showDeletePicModal = false"
            @deleted="handlePicDeleted"
        />
    </AuthenticatedLayout>
</template>
