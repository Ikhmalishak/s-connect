<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Calendar, Clock, User, MapPin, Plus } from "lucide-vue-next";
import { usePage } from "@inertiajs/vue3";

const page = usePage();

const roomId = page.props.roomId;

console.log("Room ID:", roomId);

interface RoomData {
    id: number;
    name: string;
    capacity: number;
    location: string;
    site: string;
}

interface Booking {
    id: number;
    purpose: string;
    user_name: string;
    user_id: string;
    start_time: string;
    end_time: string;
}

const roomData = ref<RoomData>({
    id: 1,
    name: "Meeting Room A",
    capacity: 8,
    location: "2nd Floor, East Wing",
    site: "Site 1",
});

const bookings = ref<Booking[]>([
    {
        id: 1,
        purpose: "Team Standup",
        user_name: "John Doe",
        user_id: "509260",
        start_time: "2025-11-04T08:00:00Z",
        end_time: "2025-11-04T09:00:00Z",
    },
    {
        id: 2,
        purpose: "Client Presentation",
        user_name: "Sarah Smith",
        user_id: "509261",
        start_time: "2025-11-04T10:00:00Z",
        end_time: "2025-11-04T11:30:00Z",
    },
    {
        id: 3,
        purpose: "Project Review",
        user_name: "Mike Johnson",
        user_id: "509262",
        start_time: "2025-11-04T14:00:00Z",
        end_time: "2025-11-04T15:00:00Z",
    },
    {
        id: 4,
        purpose: "Training Session",
        user_name: "Emily Brown",
        user_id: "509263",
        start_time: "2025-11-04T16:00:00Z",
        end_time: "2025-11-04T17:00:00Z",
    },
]);

const currentTime = ref(new Date());
let timer: number | undefined;

onMounted(() => {
    timer = window.setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});

const formattedDate = computed(() =>
    currentTime.value.toLocaleDateString("en-US", {
        weekday: "long",
        month: "long",
        day: "numeric",
        year: "numeric",
    })
);

const formattedTime = computed(() =>
    currentTime.value.toLocaleTimeString("en-US", {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
    })
);

const formatTime = (date: string) =>
    new Date(date).toLocaleTimeString("en-US", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
    });

interface RoomStatus {
    status: "occupied" | "available";
    booking?: Booking;
    minutesLeft?: number;
    nextBooking?: Booking;
    minutesUntil?: number;
}

const status = computed<RoomStatus>(() => {
    const now = currentTime.value.getTime();

    const current = bookings.value.find((b) => {
        const start = new Date(b.start_time).getTime();
        const end = new Date(b.end_time).getTime();
        return now >= start && now < end;
    });

    if (current) {
        const minutesLeft = Math.round(
            (new Date(current.end_time).getTime() - now) / 60000
        );
        return { status: "occupied", booking: current, minutesLeft };
    }

    const upcoming = bookings.value
        .filter((b) => new Date(b.start_time).getTime() > now)
        .sort(
            (a, b) =>
                new Date(a.start_time).getTime() -
                new Date(b.start_time).getTime()
        );

    if (upcoming.length > 0) {
        const next = upcoming[0];
        const minutesUntil = Math.round(
            (new Date(next.start_time).getTime() - now) / 60000
        );
        return { status: "available", nextBooking: next, minutesUntil };
    }

    return { status: "available" };
});

const currentBookingId = computed<number | null>(() =>
    status.value.status === "occupied" && status.value.booking
        ? status.value.booking.id
        : null
);

const isPast = (booking: Booking) =>
    new Date(booking.end_time).getTime() < currentTime.value.getTime();

const isOccupied = computed(() => status.value.status === "occupied");
</script>

<template>
    <div
        class="min-h-screen w-full bg-gradient-to-br from-slate-50 to-slate-100 p-4 sm:p-6 lg:p-8"
    >
        <div class="max-w-7xl mx-auto h-full flex flex-col gap-4 sm:gap-6">
            <!-- Header Card -->
            <div
                class="bg-white rounded-xl sm:rounded-2xl shadow-lg p-4 sm:p-6"
            >
                <div
                    class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                >
                    <div class="flex items-center gap-3 sm:gap-4">
                        <div
                            class="bg-gradient-to-br from-blue-500 to-indigo-600 p-3 sm:p-4 rounded-lg sm:rounded-xl flex-shrink-0"
                        >
                            <MapPin class="w-6 h-6 sm:w-8 sm:h-8 text-white" />
                        </div>
                        <div>
                            <h1
                                class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800"
                            >
                                {{ roomData.name }}
                            </h1>
                            <div
                                class="flex items-center gap-2 sm:gap-4 text-xs sm:text-sm text-gray-600 mt-1"
                            >
                                <span class="flex items-center gap-1">
                                    <User class="w-3 h-3 sm:w-4 sm:h-4" />
                                    Capacity: {{ roomData.capacity }}
                                </span>
                                <span class="hidden sm:inline">•</span>
                                <span class="hidden sm:inline">{{
                                    roomData.location
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="text-xs sm:text-sm text-gray-600">
                            {{ formattedDate }}
                        </div>
                        <div
                            class="text-2xl sm:text-3xl font-bold text-gray-800 font-mono"
                        >
                            {{ formattedTime }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Banner -->
            <div
                :class="[
                    'rounded-xl sm:rounded-3xl shadow-2xl p-6 sm:p-8 lg:p-12 transition-all duration-500',
                    isOccupied
                        ? 'bg-gradient-to-br from-emerald-500 via-green-600 to-teal-500'
                        : 'bg-gradient-to-br from-emerald-500 via-green-600 to-teal-500',
                ]"
            >
                <div class="text-center text-white">
                    <div
                        class="text-4xl sm:text-6xl lg:text-8xl font-black mb-2 sm:mb-4 drop-shadow-lg"
                    >
                        {{ isOccupied ? "🔴 OCCUPIED" : "✅ AVAILABLE" }}
                    </div>

                    <!-- Occupied Status -->
                    <div
                        v-if="isOccupied && status.booking"
                        class="mt-4 sm:mt-8 space-y-2 sm:space-y-4"
                    >
                        <div
                            class="text-xl sm:text-2xl lg:text-3xl font-semibold bg-white/20 backdrop-blur-sm rounded-xl sm:rounded-2xl py-2 sm:py-4 px-4 sm:px-8 inline-block"
                        >
                            {{ status.booking.purpose }}
                        </div>
                        <div class="text-lg sm:text-xl lg:text-2xl opacity-90">
                            Organized by: {{ status.booking.user_name }}
                        </div>
                        <div
                            class="text-base sm:text-lg lg:text-xl opacity-80 mt-2 sm:mt-4"
                        >
                            Ends in {{ status.minutesLeft }} minute{{
                                status.minutesLeft !== 1 ? "s" : ""
                            }}
                            at
                            {{ formatTime(status.booking.end_time) }}
                        </div>
                    </div>

                    <!-- Available with Next Booking -->
                    <div
                        v-else-if="!isOccupied && status.nextBooking"
                        class="mt-4 sm:mt-8 space-y-2 sm:space-y-4"
                    >
                        <div
                            class="text-xl sm:text-2xl lg:text-3xl font-semibold"
                        >
                            Free for the next {{ status.minutesUntil }} minute{{
                                status.minutesUntil !== 1 ? "s" : ""
                            }}
                        </div>
                        <div
                            class="text-lg sm:text-xl lg:text-2xl opacity-90 bg-white/20 backdrop-blur-sm rounded-xl sm:rounded-2xl py-2 sm:py-4 px-4 sm:px-8 inline-block mt-2 sm:mt-4"
                        >
                            Next: {{ status.nextBooking.purpose }} at
                            {{ formatTime(status.nextBooking.start_time) }}
                        </div>
                    </div>

                    <!-- Available - No More Bookings -->
                    <div v-else class="mt-4 sm:mt-8">
                        <div
                            class="text-xl sm:text-2xl lg:text-3xl font-semibold"
                        >
                            No more bookings today
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Schedule -->
            <div
                class=" mt-4 space-y-2 sm:space-y-3 overflow-y-auto pr-1 sm:pr-2 max-h-[50vh]"
            >
                <h2
                    class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-800 mb-3 sm:mb-6 flex items-center gap-2"
                >
                    <Calendar class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600" />
                    Today's Schedule
                </h2>

                <div
                    class="space-y-2 sm:space-y-3 overflow-y-auto flex-1 pr-1 sm:pr-2"
                >
                    <div
                        v-for="booking in bookings"
                        :key="booking.id"
                        :class="[
                            'p-3 sm:p-4 rounded-lg sm:rounded-xl border-2 transition-all',
                            currentBookingId === booking.id
                                ? 'bg-red-50 border-red-400 shadow-lg scale-100 sm:scale-105'
                                : isPast(booking)
                                ? 'bg-gray-50 border-gray-200 opacity-60'
                                : 'bg-blue-50 border-blue-200 hover:shadow-md',
                        ]"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <div
                                    class="flex items-center gap-2 sm:gap-3 mb-1 sm:mb-2 flex-wrap"
                                >
                                    <Clock
                                        :class="[
                                            'w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0',
                                            currentBookingId === booking.id
                                                ? 'text-red-600'
                                                : 'text-blue-600',
                                        ]"
                                    />
                                    <span
                                        class="font-bold text-sm sm:text-base lg:text-lg text-gray-800"
                                    >
                                        {{ formatTime(booking.start_time) }} -
                                        {{ formatTime(booking.end_time) }}
                                    </span>

                                    <span
                                        v-if="currentBookingId === booking.id"
                                        class="bg-red-500 text-white text-[10px] sm:text-xs font-bold px-2 sm:px-3 py-0.5 sm:py-1 rounded-full animate-pulse"
                                    >
                                        IN PROGRESS
                                    </span>

                                    <span
                                        v-else-if="isPast(booking)"
                                        class="bg-gray-400 text-white text-[10px] sm:text-xs font-bold px-2 sm:px-3 py-0.5 sm:py-1 rounded-full"
                                    >
                                        COMPLETED
                                    </span>
                                </div>

                                <div
                                    class="text-base sm:text-lg lg:text-xl font-semibold text-gray-800 mb-1 truncate"
                                >
                                    {{ booking.purpose }}
                                </div>
                                <div class="text-xs sm:text-sm text-gray-600">
                                    {{ booking.user_name }} ({{
                                        booking.user_id
                                    }})
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="bookings.length === 0"
                        class="text-center py-8 sm:py-12 text-gray-400"
                    >
                        <Calendar
                            class="w-12 h-12 sm:w-16 sm:h-16 mx-auto mb-3 sm:mb-4 opacity-30"
                        />
                        <p class="text-base sm:text-xl">
                            No bookings for today
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom scrollbar for schedule */
.overflow-y-auto::-webkit-scrollbar {
    width: 8px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Ensure proper height for mobile */
@media (max-width: 640px) {
    .min-h-screen {
        min-height: 100vh;
        min-height: -webkit-fill-available;
    }
}
</style>
