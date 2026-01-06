<script setup lang="ts">
import { useToast } from "@/components/ui/toast";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Card } from "@/components/ui/card";
import axios from "axios";
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import {
    Breadcrumb,
    BreadcrumbPage,
    BreadcrumbSeparator,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
} from "@/components/ui/breadcrumb";
import ContainerStatCard from "@/Components/ManageContainer/ContainerStatCard.vue";
import ContainerTable from "@/Components/ManageContainer/ContainerTable.vue";
import CreateContainerFormModal from "@/Components/ManageContainer/CreateContainerFormModal.vue";
import ContainerInspectionFormModal from "@/Components/ManageContainer/ContainerInspectionFormModal.vue";
import ViewInspectionModal from "@/Components/ManageContainer/ViewInspectionModal.vue";
import CreateContainerRecordModal from "@/Components/ManageContainer/CreateContainerRecordModal.vue";
import ViewContainerRecordModal from "@/Components/ManageContainer/ViewContainerRecordModal.vue";
import CreateContainerSecurityCheckingModal from "@/Components/ManageContainer/CreateContainerSecurityCheckingModal.vue";
import ViewContainerSecurityCheckingModal from "@/Components/ManageContainer/ViewContainerSecurityCheckingModal.vue";
import Button from "@/components/ui/button/Button.vue";
import { h } from "vue"
import { ToastAction } from "@/components/ui/toast"

const currentTime = ref(new Date());
const openCreateContainerModal = ref(false);
const openCreateContainerInspectionModal = ref(false);
const openEditContainerInspectionModal = ref(false);
const openViewContainerInspectionModal = ref(false);
const openCreateContainerRecordModal = ref(false);
const openViewContainerRecordModal = ref(false);
const openContainerSecurityCheckingModal = ref(false);
const openViewContainerSecurityCheckingModal = ref(false);
const currentCreateContainerId = ref<number | null>(null);
const currentViewContainerId = ref<number | null>(null);
const currentCreateContainerRecordId = ref<number | null>(null);
const currentEditContainerId = ref<number | null>(null);
const currentViewContainerRecordId = ref<number | null>(null);
const currentContainerSecurityCheckingId = ref<number | null>(null);
const currentViewContainerSecurityCheckingId = ref<number | null>(null);
const containers = ref([]);

let intervalId;
const { toast } = useToast()

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

function handleOpenCreatContainerInspectionModal(containerId: number) {
    console.log(
        "Opening Create Container Inspection Modal for ID:",
        containerId
    );
    currentCreateContainerId.value = containerId;
    openCreateContainerInspectionModal.value = true;
}

function handleOpenViewContainerInspectionModal(containerId: number) {
    console.log("Opening View Container Inspection Modal for ID:", containerId);
    currentViewContainerId.value = containerId;
    console.log("Current View Container ID:", currentViewContainerId.value);
    openViewContainerInspectionModal.value = true;
}

function handleOpenCreateContainerRecordModal(containerId: number) {
    console.log("Opening Create Container Record Modal for ID:", containerId);
    currentCreateContainerRecordId.value = containerId;
    console.log("Current View Container ID:", currentViewContainerId.value);
    openCreateContainerRecordModal.value = true;
}

function handleOpenViewContainerRecordModal(containerId: number) {
    console.log("Opening View Container Record Modal for ID:", containerId);
    currentViewContainerRecordId.value = containerId;
    console.log(
        "Current View Container ID:",
        currentViewContainerRecordId.value
    );
    openViewContainerRecordModal.value = true;
}

function handleOpenContainerSecurityCheckingModal(containerId: number) {
    console.log("Opening Container Security Checking Modal for ID:", containerId);
    currentContainerSecurityCheckingId.value = containerId;
    console.log(
        "Current Container Security Checking",
        currentContainerSecurityCheckingId.value
    );
    openContainerSecurityCheckingModal.value = true;
}

function handleOpenViewContainerSecurityCheckingModal(containerId: number) {
    console.log("Opening Container Security Checking Modal for ID:", containerId);
    currentViewContainerSecurityCheckingId.value = containerId;
    console.log(
        "Current Container Security Checking",
        currentViewContainerSecurityCheckingId.value
    );
    openViewContainerSecurityCheckingModal.value = true;
}

async function fetchContainers() {
    // Fetch container data from API
    const res = await axios.get("/containers");
    console.log("Fetched containers:", res.data.data);
    containers.value = res.data.data;
}

function handleUpdateContainers(updatedContainers) {
    containers.value = updatedContainers;
}

onMounted(() => {
    console.log("Mounted VisitorTable.vue");

    intervalId = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);

    fetchContainers();

    if (window.Echo) {
        // Existing listener
        window.Echo.channel("visitors")
            .listen(".visitor.registered", (e) => {
                console.log("New VisitorRegistered event received:", e);
            })
            .error((error) => {
                console.error("WebSocket error on visitors channel:", error);
            });

        // New NotifyGuard listener
        window.Echo.channel("guard")
            .listen(".notify.guard", (e) => {
                console.log("NotifyGuard event received:", e);
            })
            .error((error) => {
                console.error("WebSocket error on guard channel:", error);
            });

        // New NotifyGuard listener
        window.Echo.channel("guard")
            .listen(".guard.scan", (e) => {
                console.log("GuardScanInAndOut event received:", e);
            })
            .error((error) => {
                console.error("WebSocket error on guard channel:", error);
            });

        // Listen for container stage updates
        window.Echo.channel("containers")
            .listen(".container.stage.updated", (e) => {
                console.log("Container stage updated event received:", e);
                fetchContainers(); // Refresh containers when stage changes
            })
            .error((error) => {
                console.error("WebSocket error on containers channel:", error);
            });

        console.log(
            'Listening for VisitorRegistered, NotifyGuard, GuardScanInAndOut, and ContainerStageUpdated events via Reverb.'
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
    if (window.Echo) {
        window.Echo.leave("visitors");
        window.Echo.leave("guard");
        window.Echo.leave("containers");
        console.log(
            'Stopped listening for events on "visitors", "guard", and "containers" channels.'
        );
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

        <ContainerTable
            :containers="containers"
            @open-create-container-modal="openCreateContainerModal = true"
            @open-container-inspection-modal="
                handleOpenCreatContainerInspectionModal
            "
            @open-view-inspection-modal="handleOpenViewContainerInspectionModal"
            @open-create-container-record-modal="
                handleOpenCreateContainerRecordModal
            "
            @open-view-container-record-modal="
                handleOpenViewContainerRecordModal
            "
            @open-container-security-checking-modal="
            handleOpenContainerSecurityCheckingModal"
            @open-view-container-security-checking-modal="
            handleOpenViewContainerSecurityCheckingModal"
            @update-containers="handleUpdateContainers"
        />
        <CreateContainerFormModal
            v-model:show="openCreateContainerModal"
            @close="
                () => {
                    openCreateContainerModal = false;
                    fetchContainers();
                }
            "
        />

        <ContainerInspectionFormModal
            v-model:show="openCreateContainerInspectionModal"
            :id="currentCreateContainerId"
            :is-edit-mode="false"
            @close="
                () => {
                    openCreateContainerInspectionModal = false;
                    fetchContainers();
                }
            "
        />

        <ContainerInspectionFormModal
            v-model:show="openEditContainerInspectionModal"
            :id="currentEditContainerId"
            :is-edit-mode="true"
            @close="
                () => {
                    openEditContainerInspectionModal = false;
                    fetchContainers();
                }
            "
        />

        <ViewInspectionModal
            v-model:show="openViewContainerInspectionModal"
            :id="currentViewContainerId"
            @close="
                () => {
                    openViewContainerInspectionModal = false;
                }
            "
        />

        <CreateContainerRecordModal
            v-model:show="openCreateContainerRecordModal"
            :id="currentCreateContainerRecordId"
            @close="
                () => {
                    openCreateContainerRecordModal = false;
                    fetchContainers();
                }
            "
        />

        <ViewContainerRecordModal
            v-model:show="openViewContainerRecordModal"
            :id="currentViewContainerRecordId"
            @close="
                () => {
                    openViewContainerRecordModal = false;
                }
            "
        />

        <CreateContainerSecurityCheckingModal
            v-model:show="openContainerSecurityCheckingModal"
            :id="currentContainerSecurityCheckingId"
            @close="
                () => {
                    openContainerSecurityCheckingModal = false;
                    fetchContainers();
                }
            "
        />

        <ViewContainerSecurityCheckingModal
           v-model:show="openViewContainerSecurityCheckingModal"
            :id="currentViewContainerSecurityCheckingId"
            @close="
                () => {
                    openViewContainerSecurityCheckingModal = false;
                }
            "
        />
    </AuthenticatedLayout>
</template>
