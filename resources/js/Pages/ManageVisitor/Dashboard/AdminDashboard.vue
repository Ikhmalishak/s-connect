<script setup lang="ts">
import { usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Card } from "@/components/ui/card";
import axios from "axios";
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { Link } from "@inertiajs/vue3"; // Add this import
import {
    Breadcrumb,
    BreadcrumbPage,
    BreadcrumbSeparator,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
} from "@/components/ui/breadcrumb";
import VisitorStatsCards from "@/Components/VisitorTableComponent/VisitorStatCard.vue";
import VisitorTable from "@/Components/VisitorTableComponent/AdminVisitorTable.vue";
import VisitorModal from "@/Components/VisitorTableComponent/VisitorModal.vue";
import GatePassModal from "@/Components/VisitorTableComponent/GatePassModal.vue";
import CheckoutModal from "@/Components/VisitorTableComponent/CheckoutModal.vue";
import NotifyGuardModal from "@/Components/VisitorTableComponent/NotifyGuardModal.vue";
import DetailsModal from "@/Components/VisitorTableComponent/DetailsModal.vue";
import ReprintModal from "@/Components/VisitorTableComponent/ReprintModal.vue";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

interface GatePass {
    id: number;
    pass_number: string;
}

interface VisitorForm {
    id: number;
    pass_number: string;
    visitor_name: string;
    visitor_type: string;
    vehicle_number: string;
    date: string;
    time_register: string;
    time_in: string;
    time_out: string;
    visitor_company: string;
    purpose: string;
    ic_number: string;
    passport: string;
    phone_number: string;
    created_at?: string;
    updated_at?: string;
    total?: number;
    gate_pass_id?: number;
    gate_pass: GatePass;
    other_reasons: string;
    person_to_meet: string;
}

interface VisitorForm2 {
    visitor_type: string;
    total?: number;
}

interface VisitorInByHour {
    hour: string;
    total_in: number;
}

interface VisitorOutByHour {
    hour: string;
    total_out: number;
}

interface VisitorResponse {
    visitor: VisitorForm[];
    visitor_today: VisitorForm2[];
    visitor_inside: VisitorForm2[];
    visitor_in_by_hour: VisitorInByHour[];
    visitor_out_by_hour: VisitorOutByHour[];
}

const { props: pageProps } = usePage();
const visitorForms = ref<VisitorForm[]>(
    Array.isArray(pageProps.data) ? pageProps.data : []
);
const visitorOut = ref<VisitorForm2[]>([]);
const visitorIn = ref<VisitorForm2[]>([]);
const visitorInByHour = ref<VisitorInByHour[]>([]);
const visitorOutByHour = ref<VisitorOutByHour[]>([]);
const notifyVisitors = ref<VisitorForm[]>([]);
const limitTable = ref("50");
const searchQuery = ref("");
const currentTime = ref(new Date());
const isGatePassModalOpen = ref(false);
const gatePassList = ref([]);
const showVisitorModal = ref(false);
const visitorInsideList = ref([]);
const totalAvailableGatePass = ref(0);
const showCheckoutModal = ref(false);
const showNotifyModal = ref(false);
const selectedVisitor = ref(null);
const showDetailsModal = ref(false);
const showReprintModal = ref(false);
const selectedSite = ref("2");
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

async function fetchVisitors(site = selectedSite.value) {
    try {
        const res = await axios.get<VisitorResponse>(`/visitor/get-visitor-data`, {
            params: {
                site,
            },
        });
        visitorOut.value = res.data.visitor_today;
        visitorIn.value = res.data.visitor_inside;
        visitorInByHour.value = res.data.visitor_in_by_hour;
        visitorOutByHour.value = res.data.visitor_out_by_hour;
        console.log("Fetched:", res.data);
    } catch (e) {
        console.error("Failed to fetch visitors", e);
    }
}

async function fetchAdminVisitorTableData(
    limit = limitTable.value,
    keyword = searchQuery.value,
    site = selectedSite.value
) {
    console.log(limit, keyword);
    try {
        const res = await axios.get("/visitor/get-visitor-table-data", {
            params: {
                limit,
                keyword,
                site,
            },
        });
        console.log("inside fetch table data", res.data.visitor);

        visitorForms.value = res.data.visitor;
    } catch (e) {
        console.log("Failed to fetch visitors table data", e);
    }
}

async function fetchGatePass(site = selectedSite.value) {
    try {
        const res = await axios.get("/visitor/gate-passes", {
            params: {
                site,
            },
        });
        gatePassList.value = res.data.gate_pass;
        totalAvailableGatePass.value = res.data.available_gatepass;
        console.log("Fetched gate pass:", res.data);
    } catch (e) {
        console.error("Failed to fetch gate pass", e);
    }
}

async function fetchVisitorInside() {
    try {
        const res = await axios.get("/visitor/get-visitor-inside");
        console.log("Fetching visitor inside", res.data);
        visitorInsideList.value = res.data.data;
    } catch (e) {
        console.error("Failed to fetch visitor inside", e);
    }
}

const handleUpdateRemarks = async (data) => {
    try {
        console.log(data);
        const response = await axios.post(
            `/visitors/${data.visitorId}/remarks`,
            {
                remarks: data.remarks,
            }
        );

        if (response.data.success) {
            // Update local state
            selectedVisitor.value.remarks = data.remarks;
            console.log("Remarks updated successfully!");
        }
    } catch (error) {
        if (error.response) {
            // Server responded with error status
            console.error("Server error:", error.response.data.message);
            if (error.response.status === 422) {
                // Validation errors
                console.error("Validation errors:", error.response.data.errors);
            }
        } else {
            // Network error
            console.error("Network error:", error.message);
        }
    }
};

function openDetailsModal(visitorId) {
    selectedVisitor.value = visitorForms.value.find((v) => v.id === visitorId);
    showDetailsModal.value = true;
}

onMounted(() => {
    console.log("Mounted VisitorTable.vue");
    fetchGatePass();
    fetchVisitorInside();
    fetchAdminVisitorTableData();
    fetchVisitors();

    intervalId = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);

    if (window.Echo) {
        // Existing listener
        window.Echo.channel("visitors")
            .listen(".visitor.registered", (e) => {
                console.log("New VisitorRegistered event received:", e);
                fetchAdminVisitorTableData();
                fetchGatePass();
            })
            .error((error) => {
                console.error("WebSocket error on visitors channel:", error);
            });

        // New NotifyGuard listener
        window.Echo.channel("guard")
            .listen(".notify.guard", (e) => {
                console.log("NotifyGuard event received:", e);

                if (e.visitors && e.visitors.length > 0) {
                    console.log("inside here");
                    notifyVisitors.value = e.visitors;
                    showNotifyModal.value = true;
                }
            })
            .error((error) => {
                console.error("WebSocket error on guard channel:", error);
            });

        // New NotifyGuard listener
        window.Echo.channel("guard")
            .listen(".guard.scan", (e) => {
                console.log("GuardScanInAndOut event received:", e);
                fetchAdminVisitorTableData();
            })
            .error((error) => {
                console.error("WebSocket error on guard channel:", error);
            });

        console.log(
            "Listening for VisitorRegistered and NotifyGuard and GuardScanInAndOut events via Reverb."
        );
    } else {
        console.error(
            "Laravel Echo is not initialized. Please check resources/js/app.js."
        );
    }
});

watch(limitTable, (newVal) => {
    console.log("Limit changed to:", newVal);
    fetchAdminVisitorTableData();
});

watch(searchQuery, (newVal) => {
    console.log("Search query changed to:", newVal);
    fetchAdminVisitorTableData();
});

watch(selectedSite, (newVal) => {
    console.log("Site changed", newVal);
    fetchAdminVisitorTableData();
    fetchGatePass();
    fetchVisitors();
});
watch(isGatePassModalOpen, (newValue, oldValue) => {
    console.log(`Modal changed: ${oldValue} -> ${newValue}`);
    if (newValue) {
        console.log("Modal is now open!");
    } else {
        console.log("Modal is now closed.");
    }
});

onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }
    if (window.Echo) {
        window.Echo.leave("visitors");
        window.Echo.leave("guard");
        console.log(
            'Stopped listening for VisitorRegistered events on "visitors" channel and "guard".'
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
                            >Visitor Management System</BreadcrumbLink
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
                <div>Visitor Management System</div>
            </div>
            <div class="flex flex-row items-center gap-10">
                <div class="flex items-center gap-2">
                    <label class="text-sm whitespace-nowrap"
                        >Select Site :</label
                    >
                    <Select v-model="selectedSite">
                        <SelectTrigger class="w-[180px]">
                            <SelectValue placeholder="Select Site" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectLabel>Site</SelectLabel>
                                <SelectItem value="1">Site 1</SelectItem>
                                <SelectItem value="2">Site 2</SelectItem>
                                <SelectItem value="3">Site 3</SelectItem>
                                <SelectItem value="4">Site 4</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>
                <div
                    class="flex flex-row space-x-4 text-base font-normal text-gray-600 text-right"
                >
                    <div>{{ formattedDate }}</div>
                    <div>{{ formattedTime }}</div>
                </div>
            </div>
        </Card>

        <NotifyGuardModal
            :show="showNotifyModal"
            :visitors="notifyVisitors"
            @close="showNotifyModal = false"
        />

        <VisitorStatsCards
            :visitor-in="visitorIn"
            :visitor-out="visitorOut"
            :visitor-in-by-hour="visitorInByHour"
            :visitor-out-by-hour="visitorOutByHour"
            @donut-click="showVisitorModal = true"
        />

        <VisitorTable
            :visitors="visitorForms"
            :limit="limitTable"
            :count="totalAvailableGatePass"
            @update:limit="limitTable = $event"
            @search="searchQuery = $event"
            @open-gate-pass-modal="isGatePassModalOpen = true"
            @open-details-modal="openDetailsModal"
            @open-reprint-modal="showReprintModal = true"
        />

        <VisitorModal
            :show="showVisitorModal"
            :visitors="visitorInsideList"
            :current-time="currentTime"
            @close="showVisitorModal = false"
        />

        <GatePassModal
            :show="isGatePassModalOpen"
            :gate-passes="gatePassList"
            @close="isGatePassModalOpen = false"
        />

        <DetailsModal
            :show="showDetailsModal"
            :visitors="selectedVisitor"
            @close="showDetailsModal = false"
            @updateRemarks="handleUpdateRemarks"
        />

        <ReprintModal
            :show="showReprintModal"
            @close="showReprintModal = false"
        />
        <CheckoutModal
            :show="showCheckoutModal"
            :site="selectedSite"
            @refresh="
                () => {
                    fetchGatePass();
                    fetchVisitorInside();
                    fetchAdminVisitorTableData();
                }
            "
            @close="showCheckoutModal = false"
        />
    </AuthenticatedLayout>
</template>
