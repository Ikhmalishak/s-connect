<script setup lang="ts">
import { ref } from "vue";

interface Booking {
    id: number;
    roomId: number;
    startTime: string;
    endTime: string;
    title: string;
    status: "available" | "booked" | "unavailable";
}

interface Room {
    id: number;
    name: string;
    capacity: number;
}

const SLOT_HEIGHT = 60;
const START_HOUR = 8;
const GAP = 4; // space above & below each booking

const rooms = ref<Room[]>([
    { id: 1, name: "Conference Room A", capacity: 10 },
    { id: 2, name: "Conference Room B", capacity: 8 },
    { id: 3, name: "Meeting Room 1", capacity: 6 },
    { id: 4, name: "Meeting Room 2", capacity: 6 },
    { id: 5, name: "Board Room", capacity: 12 },
    { id: 1, name: "Conference Room A", capacity: 10 },
    { id: 2, name: "Conference Room B", capacity: 8 },
    { id: 3, name: "Meeting Room 1", capacity: 6 }
]);

const timeSlots = ref([
    "08:00",
    "09:00",
    "10:00",
    "11:00",
    "12:00",
    "13:00",
    "14:00",
    "15:00",
    "16:00",
    "17:00",
]);

const bookings = ref<Booking[]>([
    {
        id: 1,
        roomId: 1,
        startTime: "09:00",
        endTime: "10:00",
        title: "Team Meeting",
        status: "booked",
    },
    {
        id: 2,
        roomId: 1,
        startTime: "10:00",
        endTime: "11:00",
        title: "Client Call",
        status: "booked",
    },
    {
        id: 3,
        roomId: 1,
        startTime: "14:00",
        endTime: "15:00",
        title: "Workshop",
        status: "booked",
    },
    {
        id: 4,
        roomId: 2,
        startTime: "08:00",
        endTime: "09:00",
        title: "Standup",
        status: "booked",
    },
    {
        id: 5,
        roomId: 2,
        startTime: "13:00",
        endTime: "14:00",
        title: "Training",
        status: "booked",
    },
    {
        id: 6,
        roomId: 2,
        startTime: "14:00",
        endTime: "15:30",
        title: "Review",
        status: "booked",
    },
    {
        id: 6,
        roomId: 2,
        startTime: "15:30",
        endTime: "16:30",
        title: "Review",
        status: "booked",
    },
    {
        id: 7,
        roomId: 3,
        startTime: "10:00",
        endTime: "11:00",
        title: "Interview",
        status: "booked",
    },
    {
        id: 8,
        roomId: 3,
        startTime: "11:00",
        endTime: "12:00",
        title: "Maintenance",
        status: "unavailable",
    },
    {
        id: 9,
        roomId: 4,
        startTime: "09:00",
        endTime: "10:00",
        title: "Planning",
        status: "booked",
    },
    {
        id: 10,
        roomId: 4,
        startTime: "15:00",
        endTime: "16:00",
        title: "Demo",
        status: "booked",
    },
    {
        id: 11,
        roomId: 4,
        startTime: "16:00",
        endTime: "17:00",
        title: "Retrospective",
        status: "booked",
    },
    {
        id: 12,
        roomId: 5,
        startTime: "11:00",
        endTime: "12:00",
        title: "Board Meeting",
        status: "booked",
    },
    {
        id: 13,
        roomId: 5,
        startTime: "12:00",
        endTime: "13:00",
        title: "Lunch & Learn",
        status: "booked",
    },
    {
        id: 14,
        roomId: 5,
        startTime: "13:00",
        endTime: "14:00",
        title: "Strategy",
        status: "booked",
    },
]);

function timeToMinutes(time: string): number {
    const [hours, minutes] = time.split(":").map(Number);
    return hours * 60 + minutes;
}

function getBookingStyle(booking: Booking) {
    const startMinutes = timeToMinutes(booking.startTime);
    const endMinutes = timeToMinutes(booking.endTime);
    const duration = endMinutes - startMinutes;

    const gridStartMinutes = START_HOUR * 60;
    const relativeStart = startMinutes - gridStartMinutes;

    const top = (relativeStart / 60) * SLOT_HEIGHT + GAP;
    const height = (duration / 60) * SLOT_HEIGHT - GAP * 2;

    return {
        top: `${top}px`,
        height: `${height}px`,
    };
}

function getBookingsForRoom(roomId: number): Booking[] {
    return bookings.value.filter((b) => b.roomId === roomId);
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 p-6">
        <div class="max-w-full mx-auto">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    Room Reservation Dashboard
                </h1>
                <p class="text-gray-600">
                    Click on booking blocks to toggle between Available and
                    Booked
                </p>
            </div>

            <!-- Horizontal Scroll Wrapper -->
            <div class="overflow-x-auto">
                <div
                    class="grid relative"
                    :style="`grid-template-columns: 6rem repeat(${rooms.length}, minmax(180px, 1fr));`"
                >
                    <!-- Sticky Header Row -->
                    <div class="sticky left-0 z-30 bg-gray-50 border-r">
                    </div>
                    <div
                        v-for="room in rooms"
                        :key="room.id"
                        class="p-4 border-b border-r bg-white sticky top-0 z-20"
                    >
                        <img src="/assets/meeting.jpeg" alt="" />

                        <div
                            class="font-semibold text-gray-800 whitespace-nowrap"
                        >
                            {{ room.name }}
                        </div>
                        <div class="text-xs text-gray-500">
                            Capacity: {{ room.capacity }}
                        </div>
                    </div>

                    <!-- Sticky Left Time Column -->
                    <div class="sticky left-0 z-30 bg-gray-50 border-r">
                        <div
                            v-for="time in timeSlots"
                            :key="time"
                            class="h-[60px] flex items-center justify-end pr-3 text-sm border-t"
                        >
                            {{ time }}
                        </div>
                    </div>

                    <!-- Room Columns -->
                    <div
                        v-for="room in rooms"
                        :key="room.id"
                        class="relative border-r min-w-[180px]"
                    >
                        <!-- Grid background lines -->
                        <div
                            v-for="time in timeSlots"
                            :key="time"
                            class="h-[60px] border-t"
                        ></div>

                        <!-- Bookings -->
                        <div
                            v-for="booking in getBookingsForRoom(room.id)"
                            :key="booking.id"
                            :style="getBookingStyle(booking)"
                            :class="[
                                'absolute left-2 right-2 rounded-lg p-2 cursor-pointer transition-all hover:shadow-lg z-10',
                                booking.status === 'available' &&
                                    'bg-emerald-100 border-2 border-emerald-400 hover:bg-emerald-200',
                                booking.status === 'booked' &&
                                    'bg-indigo-500 border-2 border-indigo-600 text-white hover:bg-indigo-600',
                                booking.status === 'unavailable' &&
                                    'bg-gray-200 border-2 border-gray-400 text-gray-500 cursor-not-allowed',
                            ]"
                        >
                            <div
                                class="text-sm font-semibold whitespace-nowrap"
                            >
                                {{ booking.title }}
                            </div>
                            <div class="text-xs">
                                {{ booking.startTime }} - {{ booking.endTime }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="mt-6 bg-white rounded-lg shadow-lg p-4">
                <h3 class="font-semibold text-gray-800 mb-3">Status Legend</h3>
                <div class="flex gap-6 flex-wrap">
                    <div class="flex items-center gap-2">
                        <div
                            class="w-12 h-8 bg-emerald-100 border-2 border-emerald-400 rounded"
                        ></div>
                        <span class="text-sm text-gray-700">Available</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div
                            class="w-12 h-8 bg-indigo-500 border-2 border-indigo-600 rounded"
                        ></div>
                        <span class="text-sm text-gray-700">Booked</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div
                            class="w-12 h-8 bg-gray-200 border-2 border-gray-400 rounded"
                        ></div>
                        <span class="text-sm text-gray-700">Unavailable</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
