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
import { DonutChart } from "@/components/ui/chart-donut";
import { AreaChart } from "@/components/ui/chart-area";

interface VisitorForm {
    id: number;
    visitor: [];
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
    pass_number: string;
    phone_number: string;
    created_at?: string;
    updated_at?: string;
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
    visitor_today: VisitorForm[];
    visitor_inside: VisitorForm[];
    visitor_in_by_hour: VisitorInByHour[];
    visitor_out_by_hour: VisitorOutByHour[];
}

const { props } = usePage();
const visitorForms = ref<VisitorForm[]>(props.data as VisitorForm[]);
const visitorOut = ref<VisitorForm[]>([]);
const visitorIn = ref<VisitorForm[]>([]);
const visitorInByHour = ref<VisitorInByHour[]>([]);
const visitorOutByHour = ref<VisitorOutByHour[]>([]);
const limitTable = ref("10");
const searchQuery = ref("");
const currentTime = ref(new Date());

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

//function to mask ic number
function maskIC(ic: string) {
    if (!ic) return "";
    // Keep last 5 digits, mask the rest with *
    const visiblePart = ic.slice(-6);
    const maskedPart = "*".repeat(Math.max(0, ic.length - 6));
    return `${maskedPart}${visiblePart}`;
}

function maskPhoneNum(phoneNum: string) {
    if (!phoneNum) return "";
    // Keep last 5 digits, mask the rest with *
    const visiblePart = phoneNum.slice(-4);
    const maskedPart = "*".repeat(Math.max(0, phoneNum.length - 5));
    return `${maskedPart}${visiblePart}`;
}

//)
async function checkIn(id: number) {
    try {
        await axios.post(`/visitor/${id}/check-in`);
        await fetchVisitors(); // Refresh data without navigating
    } catch (error) {
        console.error(error);
    }
}

async function checkOut(id: number) {
    try {
        await axios.post(`/visitor/${id}/check-out`);
        await fetchVisitors(); // Refresh data without navigating
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

onMounted(() => {
    console.log("Mounted VisitorTable.vue");
    fetchVisitors();
    setInterval(fetchVisitors, 150000);
    intervalId = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

watch(limitTable, (newVal) => {
    console.log("Limit changed to:", newVal);
    fetchVisitors(newVal);
});

onUnmounted(() => {
    clearInterval(intervalId);
});

const visitorInsidePieChart = computed(() => {
    const data = visitorIn.value.map((item) => ({
        name: item.visitor_type || "Unknown",
        total: item.total || 0,
    }));
    return data.length ? data : [{ name: "No Data", total: 0 }];
});

const visitorOutsidePieChart = computed(() => {
    const data = visitorOut.value.map((item) => ({
        name: item.visitor_type || "Unknown",
        total: item.total || 0,
    }));
    return data.length ? data : [{ name: "No Data", total: 0 }];
});

const visitorInOutByHourData = computed(() => {
    const inMap = new Map(
        visitorInByHour.value.map((item) => [Number(item.hour), item.total_in])
    );

    const outMap = new Map(
        visitorOutByHour.value.map((item) => [
            Number(item.hour),
            item.total_out,
        ])
    );

    return Array.from({ length: 24 }, (_, hour) => ({
        name: hour.toString().padStart(2, "0"),
        total_in: inMap.get(hour) ?? 0,
        total_out: outMap.get(hour) ?? 0,
    }));
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

function getRowClass(visitor: VisitorForm) {
    if (visitor.time_out) {
        return "bg-green-100 hover:bg-green-100 data-[state=hover]:bg-green-100";
    }
    if (visitor.time_in && !visitor.time_out) {
        return "text-white bg-red-400 hover:bg-red-300 data-[state=hover]:bg-red-300";
    }
    return "bg-white hover:bg-white data-[state=hover]:bg-white";
}
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

        <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-8 gap-2 mb-4">
            <!-- Donut Card with Fixed Height -->
            <div class="relative lg:col-span-4">
                <!-- Overlay Badge -->
                <div class="absolute -top-3 left-2 z-10">
                    <span
                        class="bg-gray-100 text-black px-2 py-1 rounded-full text-xs font-small border shadow-md"
                    >
                        Visitor Status
                    </span>
                </div>

                <Card class="shadow-2xl shadow-opacity-60 h-[280px]">
                    <!-- Remove the old Badge since we have the overlay now -->
                    <div
                        class="flex flex-wrap justify-center items-center gap-4 p-4 h-full"
                    >
                        <div class="flex-1 h-full flex justify-center">
                            <div class="w-full max-w-[250px] h-[200px]">
                                <DonutChart
                                    index="name"
                                    :category="'total'"
                                    :data="visitorInsidePieChart"
                                    :type="'donut'"
                                    :colors="[
                                        visitorInsidePieChart.length === 1 &&
                                        visitorInsidePieChart[0].name ===
                                            'No Data'
                                            ? 'gray'
                                            : 'hsl(0, 100%, 70%)',
                                        'hsl(0, 85%, 60%)',
                                        'hsl(0, 75%, 50%)',
                                    ]"
                                    centralSubLabel="In"
                                    class="w-4/5 h-4/5"
                                />
                            </div>
                        </div>
                        <div class="flex-1 h-full flex justify-center">
                            <div class="w-full max-w-[250px] h-[200px]">
                                <DonutChart
                                    index="name"
                                    :category="'total'"
                                    :data="visitorOutsidePieChart"
                                    :type="'donut'"
                                    :colors="[
                                        visitorOutsidePieChart.length === 1 &&
                                        visitorOutsidePieChart[0].name ===
                                            'No Data'
                                            ? 'gray'
                                            : 'hsl(120, 50%, 75%)',
                                        'hsl(120, 50%, 45%)',
                                        'hsl(120, 50%, 25%)',
                                    ]"
                                    central-sub-label="Out"
                                    class="w-4/5 h-4/5"
                                />
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <div class="relative lg:col-span-4">
                <!-- Overlay Badge -->
                <div class="absolute -top-3 left-2 z-10">
                    <span
                        class="bg-gray-100 text-black px-2 py-1 rounded-full text-xs font-small border shadow-md"
                    >
                        Visitor In by Hour
                    </span>
                </div>
                <!-- Area Chart Card with Fixed Height -->
                <Card
                    class="p-5 shadow-2xl shadow-opacity-60 lg:col-span-2 h-[280px]"
                >
                    <div class="h-full">
                        <AreaChart
                            :data="visitorInOutByHourData"
                            index="name"
                            :categories="['total_in', 'total_out']"
                            :colors="['blue', 'pink', 'orange', 'red']"
                            class="w-full h-3/4"
                        />
                    </div>
                </Card>
            </div>
        </div>

        <div class="relative">
            <!-- Badge -->
            <div class="absolute -top-3 left-2 z-10">
                <span
                    class="bg-gray-100 text-black px-2 py-1 rounded-full text-xs font-semibold border shadow-md"
                >
                    Visitor List
                </span>
            </div>

            <Card class="p-5 shadow-2xl max-h-[700px] shadow-opacity-60">
                <div class="flex space-x-4 justify-between mb-2">
                    <div class="flex items-center gap-4">
                        <div class="flex flex-row space-x-2">
                            <Input
                                v-model="searchQuery"
                                class="w-400 bg-gray-300"
                                placeholder="Search by name..."
                            />
                        </div>
                        <div>
                            <Select v-model="limitTable">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select limit" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="10">10</SelectItem>
                                        <SelectItem value="25">25</SelectItem>
                                        <SelectItem value="30">30</SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <!-- Tooltip Wrapper -->
                    <div class="relative group">
                        <Link href="/visitor/form/new">
                            <button class="p-2 hover:bg-gray-100 rounded">
                                <img
                                    src="/assets/add-user.png"
                                    alt="Add User"
                                    class="w-6 h-6"
                                />
                            </button>
                        </Link>

                        <!-- Tooltip -->
                        <div
                            class="absolute right-full top-1/2 -translate-y-1/2 ml-2 bg-black text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10"
                        >
                            Add Visitor
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto max-h-[420px]">
                    <Table class="min-w-full">
                        <TableHeader>
                            <TableRow
                                class="border border-gray-300 font-black divide-x divide-gray-300 text-black"
                            >
                                <TableHead class="font-black text-black"
                                    >Visitor Name</TableHead
                                >
                                <TableHead class="font-black text-black"
                                    >Vehicle Number</TableHead
                                >
                                <TableHead class="font-black text-black"
                                    >Date</TableHead
                                >
                                <TableHead class="font-black text-black"
                                    >Time Register</TableHead
                                >
                                <TableHead class="font-black text-black"
                                    >Time In</TableHead
                                >
                                <TableHead class="font-black text-black"
                                    >Time Out</TableHead
                                >
                                <TableHead class="font-black text-black"
                                    >Company</TableHead
                                >
                                <TableHead class="font-black text-black"
                                    >Reasons</TableHead
                                >
                                <TableHead class="font-black text-black"
                                    >IC Number</TableHead
                                >
                                <TableHead class="font-black text-black"
                                    >Passport</TableHead
                                >
                                <TableHead class="font-black text-black"
                                    >Pass Number</TableHead
                                >
                                <TableHead class="font-black text-black"
                                    >Phone Number</TableHead
                                >
                                <TableHead class="font-black text-black"
                                    >Visitor Type</TableHead
                                >
                            </TableRow>
                        </TableHeader>
                        <TableBody
                            class="border border-gray-300 divide-x divide-gray-300"
                        >
                            <TableRow
                                v-for="visitor in filteredVisitors"
                                :key="visitor.id"
                                :class="[
                                    getRowClass(visitor),
                                    'border border-gray-300 divide-x divide-gray-300',
                                ]"
                            >
                                <TableCell>{{
                                    visitor.visitor_name
                                }}</TableCell>
                                <TableCell>{{
                                    visitor.vehicle_number
                                }}</TableCell>
                                <TableCell>{{ visitor.date }}</TableCell>
                                <TableCell>{{
                                    visitor.time_register
                                }}</TableCell>
                                <TableCell>
                                    <template v-if="visitor.time_in">
                                        {{ visitor.time_in }}
                                    </template>
                                    <template v-else>
                                        <Button
                                            @click="checkIn(visitor.id)"
                                            class="h-6 px-2 text-xs leading-none"
                                        >
                                            Check In
                                        </Button>
                                    </template>
                                </TableCell>
                                <TableCell>
                                    <template v-if="visitor.time_out">
                                        {{ visitor.time_out }}
                                    </template>
                                    <template v-else>
                                        <Button
                                            @click="checkOut(visitor.id)"
                                            class="h-6 px-2 text-xs leading-none"
                                        >
                                            Check Out
                                        </Button>
                                    </template>
                                </TableCell>
                                <TableCell>{{
                                    visitor.visitor_company
                                }}</TableCell>
                                <TableCell>{{ visitor.purpose }}</TableCell>
                                <TableCell>{{
                                    maskIC(visitor.ic_number)
                                }}</TableCell>
                                <TableCell>{{
                                    maskIC(visitor.passport)
                                }}</TableCell>
                                <TableCell>{{ visitor.pass_number }}</TableCell>
                                <TableCell>{{
                                    maskPhoneNum(visitor.phone_number)
                                }}</TableCell>
                                <TableCell>{{
                                    visitor.visitor_type
                                }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
