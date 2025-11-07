<script setup lang="ts">
import { ref, onMounted, computed, onUnmounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";
import dayjs from "dayjs";

const page = usePage();
const roomId = page.props.roomId;
const backgroundImage = "/assets/room-bg2.jpg";
const roomName = ref();
const capacity = ref();
const minutesAvailable = ref();
const timeLeftHours = ref(0);
const timeLeftMinutes = ref(0);
const roomLocation = ref();

// STATE: Room status
const roomStatus = ref("available");
const currentReservation = ref(null);
const roomSchedule = ref([]);
const currentTime = ref("");
const progress = ref(0);

const updateTime = () => {
    currentTime.value = new Date().toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
    });
};

async function fetchRoomDetails(id = roomId) {
    try {
        const res = await axios.get(`/rooms/${id}`);
        console.log(res.data);
        roomName.value = res.data.data.name;
        capacity.value = res.data.data.capacity;
        roomLocation.value = res.data.data.location;
    } catch (error: any) {
        console.log("failllll");
    }
}

const fetchRoomStatus = async () => {
    const res = await axios.get(`/room-reservations/${roomId}/status`);
    console.log(res.data);
    roomStatus.value = res.data.status;
    currentReservation.value = res.data.current_reservation;
    roomSchedule.value = res.data.room_schedule;
    minutesAvailable.value = res.data.minutes_left ?? 0;
};

function updateCountdown() {
    if (!currentReservation.value?.end_time) return;

    const now = new Date();
    const end = new Date(currentReservation.value.end_time);
    let diff = Math.floor((end.getTime() - now.getTime()) / 1000);

    if (diff < 0) diff = 0;

    const hoursLeft = Math.floor(diff / 3600); // total hours
    const minutesLeft = Math.floor((diff % 3600) / 60); // remaining minutes

    timeLeftHours.value = hoursLeft;
    timeLeftMinutes.value = minutesLeft;
}

const formattedStart = computed(() =>
    currentReservation.value
        ? dayjs(currentReservation.value.start_time).format("hh:mm A")
        : ""
);

const formattedEnd = computed(() =>
    currentReservation.value
        ? dayjs(currentReservation.value.end_time).format("hh:mm A")
        : ""
);

function scheduleNextFetch() {
    const now = new Date();
    const minutes = now.getMinutes();
    const seconds = now.getSeconds();

    // Determine next trigger time (:00 or :30)
    let nextMinute = minutes < 30 ? 30 : 60;

    // Calculate how many milliseconds to wait
    const msUntilNext =
        ((nextMinute - minutes - 1) * 60 + (60 - seconds)) * 1000;

    console.log("Next fetch in", msUntilNext / 1000, "seconds");

    // Schedule the fetch
    setTimeout(() => {
        fetchRoomStatus(); // <-- your API call
        scheduleNextFetch(); // schedule next cycle
    }, msUntilNext);
}

scheduleNextFetch();

function updateProgress() {
    if (!currentReservation.value) {
        progress.value = 100;
        return;
    }

    const now = new Date();
    const start = new Date(currentReservation.value.start_time);
    const end = new Date(currentReservation.value.end_time);

    const total = end.getTime() - start.getTime(); // total ms
    const elapsed = now.getTime() - start.getTime(); // used ms

    let ratio = (elapsed / total) * 100;

    // Clamp so it doesn't go below 0 or above 100
    ratio = Math.min(Math.max(ratio, 0), 100);

    progress.value = ratio;
}

onMounted(() => {
    updateTime();
    fetchRoomDetails();
    fetchRoomStatus();

    setInterval(updateTime, 1000); // clock tick
    setInterval(updateProgress, 1000);
    setInterval(updateCountdown, 1000); // <--- LIVE countdown

    if (window.Echo) {
        // Existing listener
        window.Echo.channel("rooms")
            .listen(".room.reserved", (e) => {
                console.log("New RoomReservationCreated event received:", e);
                fetchRoomDetails();
                fetchRoomStatus();
            })
            .error((error) => {
                console.error("WebSocket error on visitors channel:", error);
            });

        console.log("Listening for RoomReservation Created via Reverb.");
    } else {
        console.error(
            "Laravel Echo is not initialized. Please check resources/js/app.js."
        );
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave("room-reservation");
        console.log(
            'Stopped listening for VisitorRegistered events on "room-reservation" channel.'
        );
    }
});
</script>

<template>
    <div class="w-screen h-screen text-white relative overflow-hidden">
        <!-- Background -->
        <div
            class="absolute inset-0 bg-cover bg-center"
            :style="`background-image: url(${backgroundImage});`"
        ></div>
        <div class="absolute inset-0 bg-black/10">
            <img
                src="/assets/skpLogo.png"
                alt=""
                class="max-w-[150px] h-auto my-7 mx-7"
            />
        </div>
        <div class="relative flex h-full">
            <!-- Left Section -->
            <div class="flex-1 flex flex-col justify-center">
                <div class="bg-black/70 p-6">
                    <div class="flex flex-row items-center gap-10">
                        <!-- Status Text -->
                        <p class="text-3xl font-light">
                            <template v-if="roomStatus === 'available'">
                                <span
                                    class="text-green-600 text-8xl font-bold text-shadow-xl"
                                >
                                    AVAILABLE
                                </span>
                            </template>
                            <template v-else>
                                <span
                                    class="text-red-500 text-8xl font-bold text-shadow-xl"
                                >
                                    IN USE
                                </span>
                            </template>
                        </p>

                        <p
                            v-if="roomStatus === 'in_use'"
                            class="text-4xl font-bold text-shadow-xl"
                        >
                            {{ timeLeftHours }} hour<span
                                v-if="timeLeftHours !== 1"
                                >s</span
                            >
                            and
                            {{ timeLeftMinutes }} minute<span
                                v-if="timeLeftMinutes !== 1"
                                >s</span
                            >
                            left
                        </p>
                    </div>

                    <!-- Room Name -->
                    <h1 class="text-6xl font-bold mt-4">
                        {{ roomName }}
                    </h1>

                    <!-- <div class="mt-6">Capacity: {{ capacity }} people</div>

                    <div>Location: {{ roomLocation }}</div> -->

                    <div v-if="currentReservation" class="mt-4">
                        <p>Reserve By: {{ currentReservation.user_name }}</p>
                        <p>Purpose: {{ currentReservation.purpose }}</p>
                        <p>Time: {{ formattedStart }} - {{ formattedEnd }}</p>
                    </div>

                    <div v-else>No active reservation.</div>
                </div>
            </div>

            <!-- Right Schedule Panel (Floating) -->
            <div
                :class="[
                    'absolute right-7 top-7 bottom-14 w-96 backdrop-blur-md rounded-3xl p-6 flex flex-col ',
                    roomStatus === 'in_use' ? 'bg-red-700' : 'bg-green-700',
                ]"
            >
                <div class="flex justify-between">
                    <div class="text-2xl font-light">Room Schedule</div>
                    <div class="text-2xl font-light">{{ currentTime }}</div>
                </div>

                <div class="mt-6 flex-1 overflow-y-auto space-y-4">
                    <div
                        v-if="roomSchedule.length > 0"
                        v-for="event in roomSchedule"
                        :key="event.id"
                        class="relative p-3 rounded-xl bg-white/20 shadow-xl"
                    >
                        <!-- Completed Stamp -->
                        <div
                            v-if="event.status === 'completed'"
                            class="absolute top-2 right-2 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded-lg shadow-xl"
                        >
                            COMPLETED
                        </div>

                        <div class="text-sm opacity-80">
                            {{
                                new Date(event.start_time).toLocaleTimeString(
                                    [],
                                    { hour: "2-digit", minute: "2-digit" }
                                )
                            }}
                            -
                            {{
                                new Date(event.end_time).toLocaleTimeString(
                                    [],
                                    { hour: "2-digit", minute: "2-digit" }
                                )
                            }}
                        </div>

                        <div class="text-base font-semibold">
                            {{ event.purpose }}
                        </div>
                        <div class="text-xs opacity-50">
                            {{ event.user_name }}
                        </div>
                    </div>
                    <div v-else>No Active Reservation Today</div>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div
            class="absolute bottom-0 left-0 w-full h-10"
            :class="roomStatus === 'in_use' ? 'bg-red-700' : 'bg-white/20'"
        >
            <div
                class="h-full"
                :class="
                    roomStatus === 'in_use' ? 'bg-green-600' : 'bg-green-500'
                "
                :style="`width: ${progress}%; transition: width 1s linear;`"
            >
                <p
                    v-if="roomStatus === 'in_use'"
                    class="text-2xl font-bold text-shadow-xl"
                >
                    {{ timeLeftHours }} hour<span v-if="timeLeftHours !== 1"
                        >s</span
                    >
                    and
                    {{ timeLeftMinutes }} minute<span
                        v-if="timeLeftMinutes !== 1"
                        >s</span
                    >
                    left
                </p>
            </div>
        </div>
    </div>
</template>
