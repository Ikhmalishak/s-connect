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
import { usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import axios from "axios";
import { router } from "@inertiajs/vue3";
import { Input } from "@/components/ui/input";
import { ref, computed, onMounted, onUnmounted } from "vue";
import {
    Breadcrumb,
    BreadcrumbPage,
    BreadcrumbSeparator,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
} from "@/components/ui/breadcrumb";

interface Props {
    data: VisitorForm[];
}

interface VisitorForm {
    id: number;
    visitor_name: string;
    vehicle_number: string;
    date: string;
    time_register: string;
    time_in: string;
    time_out: string;
    visitor_company: {
        id: number;
        name: string;
    };
    purpose: string;
    ic_number: string;
    passport: string;
    pass_number: string;
    phone_number: string;
    created_at?: string;
    updated_at?: string;
}

const { props } = usePage();
const visitorForms = ref<VisitorForm[]>(props.data as VisitorForm[]);

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

async function fetchVisitors() {
    try {
        const res = await axios.get("/api/visitors");
        visitorForms.value = res.data;
    } catch (e) {
        console.error("Failed to fetch visitors", e);
    }
}

function getRowClass(visitor: VisitorForm) {
    if (visitor.time_out) {
        return "bg-green-100 hover:bg-green-100 data-[state=hover]:bg-green-100";
    }
    if (visitor.time_in && !visitor.time_out) {
        return "text-white bg-red-400 hover:bg-red-300 data-[state=hover]:bg-red-300";
    }
    return "bg-white hover:bg-white data-[state=hover]:bg-white";
}

onMounted(() => {
    console.log("Mounted VisitorTable.vue");
    fetchVisitors();
    setInterval(fetchVisitors, 10000);
    intervalId = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    clearInterval(intervalId);
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
        <Card class="p-5 shadow-2xl shadow-opacity-60">
            <div class="flex space-x-4 justify-between mb-4">
                <div class="flex flex-row space-x-2">
                    <Input
                        v-model="searchQuery"
                        class="w-400"
                        placeholder="Search by name..."
                    />
                </div>
                <Button as-child>
                    <a href="/visitor/form">New Visitor</a>
                </Button>
            </div>

            <Table class="overflow-auto max-h-[400px] overflow-y-auto">
                <TableCaption>A list of all visitor records.</TableCaption>
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
                        <!-- <TableHead>Action</TableHead> -->
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
                        <TableCell>{{ visitor.visitor_name }}</TableCell>
                        <TableCell>{{ visitor.vehicle_number }}</TableCell>
                        <TableCell>{{ visitor.date }}</TableCell>
                        <TableCell>{{ visitor.time_register }}</TableCell>
                        <TableCell>
                            <template v-if="visitor.time_in">
                                {{ visitor.time_in }}
                            </template>
                            <template v-else>
                                <Button size="sm" @click="checkIn(visitor.id)">
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
                                    size="sm"
                                    @click="checkOut(visitor.id)"
                                    :disabled="!visitor.time_in"
                                >
                                    Check Out
                                </Button>
                            </template>
                        </TableCell>
                        <TableCell>{{
                            visitor.visitor_company?.name
                        }}</TableCell>
                        <TableCell>{{ visitor.purpose }}</TableCell>
                        <TableCell>{{ maskIC(visitor.ic_number) }}</TableCell>
                        <TableCell>{{ maskIC(visitor.passport) }}</TableCell>
                        <TableCell>{{ visitor.pass_number }}</TableCell>
                        <TableCell>{{ visitor.phone_number }}</TableCell>
                        <!-- <TableCell
                            ><Button as-child variant="outline" size="sm">
                                <a :href="`/visitor/${visitor.id}/edit`"
                                    >Edit</a
                                >
                            </Button>
                        </TableCell> -->
                    </TableRow>
                </TableBody>
            </Table>
        </Card>
    </AuthenticatedLayout>
</template>
