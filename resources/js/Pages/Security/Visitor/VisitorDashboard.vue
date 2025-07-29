<script setup lang="ts">
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { usePage, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import axios from "axios";
import { Input } from "@/components/ui/input";
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import {
    Breadcrumb,
    BreadcrumbPage,
    BreadcrumbSeparator,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
} from "@/components/ui/breadcrumb";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import VisitorStatsCards from "@/Components/VisitorTableComponent/VisitorStatCard.vue";
import VisitorTable from "@/Components/VisitorTableComponent/VisitorTable.vue";
import VisitorModal from "@/Components/VisitorTableComponent/VisitorModal.vue";
import GatePassModal from "@/Components/VisitorTableComponent/GatePassModal.vue";
import NotificationBadge from "@/Components/NotificationBadge.vue";
import { IdCardLanyard } from "lucide-vue-next";

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
const visitorForms = ref<VisitorForm[]>(pageProps.data as VisitorForm[]);
const visitorOut = ref<VisitorForm2[]>([]);
const visitorIn = ref<VisitorForm2[]>([]);
const visitorInByHour = ref<VisitorInByHour[]>([]);
const visitorOutByHour = ref<VisitorOutByHour[]>([]);
const limitTable = ref("10");
const searchQuery = ref("");
const currentTime = ref(new Date());
const isGatePassModalOpen = ref(false);
const gatePassList = ref([]);
const showVisitorModal = ref(false);
const visitorInsideList = ref([]);
const unreadMessages = 120;
let intervalId;

const filteredVisitors = computed(() => {
    const items = visitorForms.value;
    if (!searchQuery.value) return items;
    return items.filter((visitor) =>
        visitor.visitor_name
            .toLowerCase()
            .includes(searchQuery.value.toLowerCase())
    );
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

async function checkIn(id: number) {
    try {
        await axios.post(`/visitor/${id}/check-in`);
        await fetchVisitors();
    } catch (error) {
        console.error(error);
    }
}

async function checkOut(id: number) {
    try {
        await axios.post(`/visitor/${id}/check-out`);
        await fetchVisitors();
    } catch (error) {
        console.error(error);
    }
}

async function fetchVisitors(limit = limitTable.value) {
    try {
        const res = await axios.get<VisitorResponse>(
            `api/visitors?limit=${limit}`
        );
        visitorForms.value = res.data.visitor;
        visitorOut.value = res.data.visitor_today;
        visitorIn.value = res.data.visitor_inside;
        visitorInByHour.value = res.data.visitor_in_by_hour;
        visitorOutByHour.value = res.data.visitor_out_by_hour;
        console.log("Fetched:", res.data);
    } catch (e) {
        console.error("Failed to fetch visitors", e);
    }
}

async function fetchGatePassList() {
    try {
        const res = await axios.get("gate-passes");
        gatePassList.value = res.data;
        console.log("Fetched gate pass:", res.data);
    } catch (e) {
        console.error("Failed to fetch gate pass", e);
    }
}

async function fetchVisitorInside() {
    try {
        const res = await axios.get("/visitor/visitor-inside");
        console.log("Fetching visitor inside", res.data);
        visitorInsideList.value = res.data.data;
    } catch (e) {
        console.error("Failed to fetch visitor inside", e);
    }
}

onMounted(() => {
    console.log("Mounted VisitorTable.vue");
    fetchVisitors();
    fetchGatePassList();
    fetchVisitorInside();

    intervalId = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);

    if (window.Echo) {
        window.Echo.channel("visitors")
            .listen(".visitor.registered", (e) => {
                console.log("New VisitorRegistered event received:", e);
                fetchVisitors();
            })
            .error((error) => {
                console.error("WebSocket error on visitors channel:", error);
            });
        console.log(
            'Listening for VisitorRegistered events on "visitors" channel via Reverb.'
        );
    } else {
        console.error(
            "Laravel Echo is not initialized. Please check resources/js/app.js."
        );
    }
});

watch(limitTable, (newVal) => {
    console.log("Limit changed to:", newVal);
    fetchVisitors(newVal);
});

watch(searchQuery, (newVal) => {
    console.log("Search query changed to:", newVal);
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
        console.log(
            'Stopped listening for VisitorRegistered events on "visitors" channel.'
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
            class="shadow-lg shadow-opacity-30 p-4 mb-4 text-2xl font-bold flex items-center justify-between bg-gray-100"
        >
            <div>Visitor Management System</div>
            <div
                class="flex flex-row space-x-4 text-base font-normal text-gray-600 text-right"
            >
                <div>{{ formattedDate }}</div>
                <div>{{ formattedTime }}</div>
            </div>
        </Card>

        <VisitorStatsCards
            :visitor-in="visitorIn"
            :visitor-out="visitorOut"
            :visitor-in-by-hour="visitorInByHour"
            :visitor-out-by-hour="visitorOutByHour"
            @donut-click="showVisitorModal = true"
        />

        <VisitorTable
            :visitors="filteredVisitors"
            :limit="limitTable"
            @update:limit="limitTable = $event"
            @search="searchQuery = $event"
            @check-in="checkIn"
            @check-out="checkOut"
            @open-gate-pass-modal="isGatePassModalOpen = true"
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
    </AuthenticatedLayout>
</template>