<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
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

interface Room {
    name: string;
    count: number;
}

interface BookingByHour {
    hour: number;
    booked: number;
    cancelled: number;
}

interface Reservation {
    id: number;
    bookingNo: string;
    roomName: string;
    bookedBy: string;
    department: string;
    date: string;
    timeStart: string;
    timeEnd: string;
    purpose: string;
    attendees: number;
    status: string;
    equipment: string;
}

const currentTime = ref(new Date());
const searchQuery = ref("");
const limitTable = ref("50");
const showDetailsModal = ref(false);
const showNewBookingModal = ref(false);
const selectedReservation = ref<Reservation | null>(null);

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

// Mock data - Replace with API calls
const roomsInUse = ref<Room[]>([
    { name: "Meeting Room 1", count: 3 },
    { name: "Meeting Room 2", count: 2 },
    { name: "Conference Room", count: 1 },
]);

const roomsAvailable = ref<Room[]>([
    { name: "Meeting Room 3", count: 1 },
    { name: "Training Room", count: 1 },
]);

const bookingByHour = ref<BookingByHour[]>([
    { hour: 8, booked: 2, cancelled: 0 },
    { hour: 9, booked: 5, cancelled: 1 },
    { hour: 10, booked: 8, cancelled: 0 },
    { hour: 11, booked: 6, cancelled: 2 },
    { hour: 12, booked: 3, cancelled: 1 },
    { hour: 13, booked: 4, cancelled: 0 },
    { hour: 14, booked: 7, cancelled: 1 },
    { hour: 15, booked: 5, cancelled: 0 },
    { hour: 16, booked: 4, cancelled: 1 },
    { hour: 17, booked: 2, cancelled: 0 },
]);

const reservations = ref<Reservation[]>([
    {
        id: 1,
        bookingNo: "B001",
        roomName: "Meeting Room 1",
        bookedBy: "John Doe",
        department: "IT",
        date: "30/10/2025",
        timeStart: "09:00",
        timeEnd: "10:30",
        purpose: "Team Standup",
        attendees: 8,
        status: "In Progress",
        equipment: "Projector, Whiteboard",
    },
    {
        id: 2,
        bookingNo: "B002",
        roomName: "Meeting Room 2",
        bookedBy: "Jane Smith",
        department: "HR",
        date: "30/10/2025",
        timeStart: "10:00",
        timeEnd: "11:00",
        purpose: "Interview",
        attendees: 3,
        status: "In Progress",
        equipment: "Video Conference",
    },
    {
        id: 3,
        bookingNo: "B003",
        roomName: "Conference Room",
        bookedBy: "Michael Chen",
        department: "Sales",
        date: "30/10/2025",
        timeStart: "11:00",
        timeEnd: "12:00",
        purpose: "Client Presentation",
        attendees: 12,
        status: "In Progress",
        equipment: "Projector, Microphone",
    },
    {
        id: 4,
        bookingNo: "B004",
        roomName: "Meeting Room 1",
        bookedBy: "Sarah Johnson",
        department: "Marketing",
        date: "30/10/2025",
        timeStart: "13:00",
        timeEnd: "14:00",
        purpose: "Campaign Planning",
        attendees: 5,
        status: "Upcoming",
        equipment: "Whiteboard",
    },
    {
        id: 5,
        bookingNo: "B005",
        roomName: "Training Room",
        bookedBy: "David Lee",
        department: "Operations",
        date: "30/10/2025",
        timeStart: "14:00",
        timeEnd: "16:00",
        purpose: "Safety Training",
        attendees: 20,
        status: "Upcoming",
        equipment: "Projector, Sound System",
    },
    {
        id: 6,
        bookingNo: "B006",
        roomName: "Meeting Room 2",
        bookedBy: "Emily Wong",
        department: "Finance",
        date: "30/10/2025",
        timeStart: "15:00",
        timeEnd: "16:30",
        purpose: "Budget Review",
        attendees: 6,
        status: "Upcoming",
        equipment: "Projector",
    },
        {
        id: 4,
        bookingNo: "B004",
        roomName: "Meeting Room 1",
        bookedBy: "Sarah Johnson",
        department: "Marketing",
        date: "30/10/2025",
        timeStart: "13:00",
        timeEnd: "14:00",
        purpose: "Campaign Planning",
        attendees: 5,
        status: "Upcoming",
        equipment: "Whiteboard",
    },
    {
        id: 5,
        bookingNo: "B005",
        roomName: "Training Room",
        bookedBy: "David Lee",
        department: "Operations",
        date: "30/10/2025",
        timeStart: "14:00",
        timeEnd: "16:00",
        purpose: "Safety Training",
        attendees: 20,
        status: "Upcoming",
        equipment: "Projector, Sound System",
    },
    {
        id: 6,
        bookingNo: "B006",
        roomName: "Meeting Room 2",
        bookedBy: "Emily Wong",
        department: "Finance",
        date: "30/10/2025",
        timeStart: "15:00",
        timeEnd: "16:30",
        purpose: "Budget Review",
        attendees: 6,
        status: "Upcoming",
        equipment: "Projector",
    },
]);

const totalInUse = computed(() =>
    roomsInUse.value.reduce((sum, room) => sum + room.count, 0)
);

const totalAvailable = computed(() =>
    roomsAvailable.value.reduce((sum, room) => sum + room.count, 0)
);

const maxBookings = computed(() =>
    Math.max(...bookingByHour.value.map((d) => d.booked + d.cancelled))
);

const inUsePercentage = computed(() => {
    const total = totalInUse.value + totalAvailable.value;
    return total > 0 ? (totalInUse.value / total) * 100 : 0;
});

const availablePercentage = computed(() => {
    const total = totalInUse.value + totalAvailable.value;
    return total > 0 ? (totalAvailable.value / total) * 100 : 0;
});

function openDetailsModal(reservationId: number) {
    selectedReservation.value =
        reservations.value.find((r) => r.id === reservationId) || null;
    showDetailsModal.value = true;
}

// Add your API fetch functions here
// async function fetchRoomStatus() {
//     try {
//         const res = await axios.get('/api/rooms/status');
//         roomsInUse.value = res.data.inUse;
//         roomsAvailable.value = res.data.available;
//     } catch (e) {
//         console.error('Failed to fetch room status', e);
//     }
// }

onMounted(() => {
    intervalId = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
    
    // Call your fetch functions here
    // fetchRoomStatus();
    // fetchBookingByHour();
    // fetchReservations();
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
                            >Room Reservation System</BreadcrumbLink
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
                <div>Room Reservation System</div>
            </div>

            <div
                class="flex flex-row space-x-4 text-base font-normal text-gray-600 text-right"
            >
                <div>{{ formattedDate }}</div>
                <div>{{ formattedTime }}</div>
            </div>
        </Card>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Room Status -->
            <Card class="p-6">
                <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                        />
                    </svg>
                    Room Status
                </h2>
                <div class="grid grid-cols-2 gap-6">
                    <!-- In Use -->
                    <div class="text-center">
                        <div
                            class="relative inline-flex items-center justify-center mb-4"
                        >
                            <svg class="w-32 h-32 transform -rotate-90">
                                <circle
                                    cx="64"
                                    cy="64"
                                    r="56"
                                    stroke="#fee2e2"
                                    stroke-width="16"
                                    fill="none"
                                />
                                <circle
                                    cx="64"
                                    cy="64"
                                    r="56"
                                    stroke="#ef4444"
                                    stroke-width="16"
                                    fill="none"
                                    :stroke-dasharray="`${(inUsePercentage / 100) * 352} 352`"
                                    stroke-linecap="round"
                                />
                            </svg>
                            <div class="absolute text-2xl font-bold">
                                {{ totalInUse }}
                            </div>
                        </div>
                        <h3 class="font-semibold text-red-600 mb-2">In Use</h3>
                        <div class="space-y-1 text-sm">
                            <div
                                v-for="(room, idx) in roomsInUse"
                                :key="idx"
                                class="flex items-center justify-between"
                            >
                                <span class="flex items-center gap-1">
                                    <div
                                        class="w-2 h-2 rounded-full bg-red-500"
                                    ></div>
                                    {{ room.name }}
                                </span>
                                <span class="font-medium">{{
                                    room.count
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Available -->
                    <div class="text-center">
                        <div
                            class="relative inline-flex items-center justify-center mb-4"
                        >
                            <svg class="w-32 h-32 transform -rotate-90">
                                <circle
                                    cx="64"
                                    cy="64"
                                    r="56"
                                    stroke="#d1fae5"
                                    stroke-width="16"
                                    fill="none"
                                />
                                <circle
                                    cx="64"
                                    cy="64"
                                    r="56"
                                    stroke="#10b981"
                                    stroke-width="16"
                                    fill="none"
                                    :stroke-dasharray="`${(availablePercentage / 100) * 352} 352`"
                                    stroke-linecap="round"
                                />
                            </svg>
                            <div class="absolute text-2xl font-bold">
                                {{ totalAvailable }}
                            </div>
                        </div>
                        <h3 class="font-semibold text-green-600 mb-2">
                            Available
                        </h3>
                        <div class="space-y-1 text-sm">
                            <div
                                v-for="(room, idx) in roomsAvailable"
                                :key="idx"
                                class="flex items-center justify-between"
                            >
                                <span class="flex items-center gap-1">
                                    <div
                                        class="w-2 h-2 rounded-full bg-green-500"
                                    ></div>
                                    {{ room.name }}
                                </span>
                                <span class="font-medium">{{
                                    room.count
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Booking Chart -->
            <Card class="p-6">
                <h2 class="text-lg font-semibold mb-4">Bookings by Hour</h2>
                <div class="relative h-64">
                    <svg class="w-full h-full">
                        <!-- Y-axis labels -->
                        <text
                            v-for="val in [0, 5, 10]"
                            :key="val"
                            x="20"
                            :y="250 - (val / maxBookings) * 220"
                            class="text-xs fill-gray-500"
                        >
                            {{ val }}
                        </text>

                        <!-- Chart bars -->
                        <g v-for="(data, idx) in bookingByHour" :key="idx">
                            <!-- Booked -->
                            <rect
                                :x="60 + idx * 50"
                                :y="250 - (data.booked / maxBookings) * 200"
                                width="20"
                                :height="(data.booked / maxBookings) * 200"
                                fill="#ef4444"
                                rx="2"
                            />
                            <!-- Cancelled -->
                            <rect
                                :x="82 + idx * 50"
                                :y="
                                    250 -
                                    (data.cancelled / maxBookings) * 200
                                "
                                width="20"
                                :height="
                                    (data.cancelled / maxBookings) * 200
                                "
                                fill="#10b981"
                                rx="2"
                            />
                            <!-- X-axis label -->
                            <text
                                :x="80 + idx * 50"
                                y="270"
                                class="text-xs fill-gray-500"
                                text-anchor="middle"
                            >
                                {{ data.hour }}:00
                            </text>
                        </g>
                    </svg>
                </div>
                <div class="flex justify-center gap-6 mt-4 text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                        <span>Booked</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span>Cancelled</span>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Reservation List -->
        <Card class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Reservation List</h2>
                <div class="flex items-center gap-4">
                    <Input
                        v-model="searchQuery"
                        placeholder="Search..."
                        class="w-64"
                    />
                    <Select v-model="limitTable">
                        <SelectTrigger class="w-24">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="10">10</SelectItem>
                            <SelectItem value="25">25</SelectItem>
                            <SelectItem value="50">50</SelectItem>
                            <SelectItem value="100">100</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button @click="showNewBookingModal = true">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 mr-2"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        New Booking
                    </Button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b bg-gray-50">
                            <th
                                class="px-4 py-3 text-left text-sm font-semibold"
                            >
                                No
                            </th>
                            <th
                                class="px-4 py-3 text-left text-sm font-semibold"
                            >
                                Booking #
                            </th>
                            <th
                                class="px-4 py-3 text-left text-sm font-semibold"
                            >
                                Room Name
                            </th>
                            <th
                                class="px-4 py-3 text-left text-sm font-semibold"
                            >
                                Booked By
                            </th>
                            <th
                                class="px-4 py-3 text-left text-sm font-semibold"
                            >
                                Department
                            </th>
                            <th
                                class="px-4 py-3 text-left text-sm font-semibold"
                            >
                                Date
                            </th>
                            <th
                                class="px-4 py-3 text-left text-sm font-semibold"
                            >
                                Time Start
                            </th>
                            <th
                                class="px-4 py-3 text-left text-sm font-semibold"
                            >
                                Time End
                            </th>
                            <th
                                class="px-4 py-3 text-left text-sm font-semibold"
                            >
                                Purpose
                            </th>
                            <th
                                class="px-4 py-3 text-left text-sm font-semibold"
                            >
                                Attendees
                            </th>
                            <th
                                class="px-4 py-3 text-left text-sm font-semibold"
                            >
                                Status
                            </th>
                            <th
                                class="px-4 py-3 text-left text-sm font-semibold"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(booking, idx) in reservations"
                            :key="booking.id"
                            :class="[
                                'border-b hover:bg-gray-50',
                                booking.status === 'In Progress'
                                    ? 'bg-red-50'
                                    : '',
                            ]"
                        >
                            <td class="px-4 py-3 text-sm">{{ idx + 1 }}</td>
                            <td class="px-4 py-3 text-sm font-medium">
                                {{ booking.bookingNo }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                {{ booking.roomName }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                {{ booking.bookedBy }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                {{ booking.department }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                {{ booking.date }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                {{ booking.timeStart }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                {{ booking.timeEnd }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                {{ booking.purpose }}
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                {{ booking.attendees }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span
                                    :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        booking.status === 'In Progress'
                                            ? 'bg-red-100 text-red-700'
                                            : 'bg-blue-100 text-blue-700',
                                    ]"
                                >
                                    {{ booking.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <button
                                    @click="openDetailsModal(booking.id)"
                                    class="p-1 hover:bg-gray-200 rounded"
                                    title="View Details"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>

        <!-- Details Modal -->
        <Dialog v-model:open="showDetailsModal">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Booking Details</DialogTitle>
                </DialogHeader>
                <div
                    v-if="selectedReservation"
                    class="grid grid-cols-2 gap-4"
                >
                    <div>
                        <label class="text-sm font-semibold text-gray-600"
                            >Booking Number</label
                        >
                        <p class="text-gray-900">
                            {{ selectedReservation.bookingNo }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-600"
                            >Room Name</label
                        >
                        <p class="text-gray-900">
                            {{ selectedReservation.roomName }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-600"
                            >Booked By</label
                        >
                        <p class="text-gray-900">
                            {{ selectedReservation.bookedBy }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-600"
                            >Department</label
                        >
                        <p class="text-gray-900">
                            {{ selectedReservation.department }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-600"
                            >Date</label
                        >
                        <p class="text-gray-900">
                            {{ selectedReservation.date }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-600"
                            >Time</label
                        >
                        <p class="text-gray-900">
                            {{ selectedReservation.timeStart }} -
                            {{ selectedReservation.timeEnd }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-600"
                            >Attendees</label
                        >
                        <p class="text-gray-900">
                            {{ selectedReservation.attendees }} people
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-600"
                            >Status</label
                        >
                        <p class="text-gray-900">
                            {{ selectedReservation.status }}
                        </p>
                    </div>
                    <div class="col-span-2">
                        <label class="text-sm font-semibold text-gray-600"
                            >Purpose</label
                        >
                        <p class="text-gray-900">
                            {{ selectedReservation.purpose }}
                        </p>
                    </div>
                    <div class="col-span-2">
                        <label class="text-sm font-semibold text-gray-600"
                            >Equipment</label
                        >
                        <p class="text-gray-900">
                            {{ selectedReservation.equipment }}
                        </p>
                    </div>
                </div>
                <DialogFooter class="gap-3">
                    <Button variant="outline">Cancel Booking</Button>
                    <Button>Edit Booking</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>