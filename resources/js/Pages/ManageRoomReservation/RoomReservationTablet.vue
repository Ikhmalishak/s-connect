<script setup lang="ts">
import { ref, onMounted, computed, onUnmounted, watch, nextTick } from "vue";
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

// Countdown state
const countdownReady = ref(false);
const showFlipCountdown = ref(false);

// Interval references for cleanup
const intervals = ref<NodeJS.Timeout[]>([]);

const updateTime = () => {
    currentTime.value = new Date().toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
    });
};

// Safe watch for reservation changes
watch(
    currentReservation,
    async (newVal) => {
        if (newVal && newVal.end_time) {
            // Hide first, then show after next tick to ensure clean DOM
            showFlipCountdown.value = false;
            await nextTick();
            showFlipCountdown.value = true;
        } else {
            showFlipCountdown.value = false;
        }
    },
    { immediate: false }
);

async function fetchRoomDetails(id = roomId) {
    try {
        const res = await axios.get(`/room-reservation/get-room-by-id/${id}`);
        console.log(res.data);
        roomName.value = res.data.data.name;
        capacity.value = res.data.data.capacity;
        roomLocation.value = res.data.data.location;
    } catch (error: any) {
        console.log("Failed to fetch room details");
    }
}

// Function to calculate event status
const calculateEventStatus = (event) => {
    const now = new Date();
    const startTime = new Date(event.start_time);
    const endTime = new Date(event.end_time);

    if (now < startTime) {
        return "upcoming";
    } else if (now >= startTime && now <= endTime) {
        return "in_progress";
    } else {
        return "completed";
    }
};

const fetchRoomStatus = async () => {
    try {
        const res = await axios.get(`/room-reservation/${roomId}/status`);
        console.log(res.data);
        roomStatus.value = res.data.status;
        currentReservation.value = res.data.current_reservation;

        // Calculate status for each room schedule event
        roomSchedule.value = res.data.room_schedule.map((event) => ({
            ...event,
            status: calculateEventStatus(event),
        }));

        minutesAvailable.value = res.data.minutes_left ?? 0;
    } catch (error: any) {
        console.log("Failed to fetch room status");
    }
};

function updateCountdown() {
    if (!currentReservation.value?.end_time) return;

    const now = new Date();
    const end = new Date(currentReservation.value.end_time);
    let diff = Math.floor((end.getTime() - now.getTime()) / 1000);

    if (diff < 0) diff = 0;

    const hoursLeft = Math.floor(diff / 3600);
    const minutesLeft = Math.floor((diff % 3600) / 60);

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

// Format deadline for flip countdown
const flipDeadline = computed(() => {
    if (!currentReservation.value?.end_time) return "";
    return dayjs(currentReservation.value.end_time).format(
        "YYYY-MM-DD HH:mm:ss"
    );
});

function updateProgress() {
    if (!currentReservation.value) {
        progress.value = 100;
        return;
    }

    try {
        const now = new Date();
        const start = new Date(currentReservation.value.start_time);
        const end = new Date(currentReservation.value.end_time);

        const total = end.getTime() - start.getTime();
        const elapsed = now.getTime() - start.getTime();

        let ratio = (elapsed / total) * 100;
        ratio = Math.min(Math.max(ratio, 0), 100);

        progress.value = ratio;
    } catch (error) {
        console.log("Progress update error:", error);
    }
}

// Function to update schedule statuses in real-time
function updateScheduleStatuses() {
    roomSchedule.value = roomSchedule.value.map((event) => ({
        ...event,
        status: calculateEventStatus(event),
    }));
}

function scheduleNextHalfHourRefresh() {
    console.log("30 minutes");
    const now = new Date();
    const msUntilNextHalfHour =
        (30 - (now.getMinutes() % 30)) * 60 * 1000 - now.getSeconds() * 1000;
    setTimeout(async () => {
        await fetchRoomStatus();
        await fetchRoomDetails();
        scheduleNextHalfHourRefresh(); // schedule next one
    }, msUntilNextHalfHour);
}

onMounted(async () => {
    updateTime();
    await fetchRoomDetails();
    await fetchRoomStatus();
    scheduleNextHalfHourRefresh();

    // Store interval references for cleanup
    intervals.value.push(setInterval(updateTime, 1000));
    intervals.value.push(setInterval(updateProgress, 1000));
    intervals.value.push(setInterval(updateCountdown, 1000));
    intervals.value.push(setInterval(updateScheduleStatuses, 30000)); // Update status every 30 seconds

    if (window.Echo) {
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
    // Clear all intervals
    intervals.value.forEach((interval) => clearInterval(interval));
    intervals.value = [];

    if (window.Echo) {
        window.Echo.leave("rooms");
        console.log('Stopped listening for events on "rooms" channel.');
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
                    <div class="flex flex-row items-center gap-80">
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

                        <!-- Flip Countdown with Safe Wrapper -->
                        <div
                            v-if="showFlipCountdown && currentReservation"
                            class="flip-countdown-container"
                        >
                            <vue3-flip-countdown
                                :key="`flip-${currentReservation.id}`"
                                :deadline="flipDeadline"
                                mainColor="white"
                                secondFlipColor="white"
                                mainFlipBackgroundColor="rgba(0,0,0,0.8)"
                                secondFlipBackgroundColor="rgba(0,0,0,0.8)"
                                labelColor="white"
                                :showDays="false"
                                :showHours="true"
                                :showMinutes="true"
                                :showSeconds="true"
                                countdownSize="4rem"
                            />
                        </div>

                        <!-- Fallback Simple Countdown -->
                        <div
                            v-else-if="
                                roomStatus === 'in_use' && currentReservation
                            "
                            class="countdown-simple"
                        >
                            <div class="countdown-section">
                                <div class="countdown-value">
                                    {{
                                        timeLeftHours
                                            .toString()
                                            .padStart(2, "0")
                                    }}
                                </div>
                                <div class="countdown-label">HOURS</div>
                            </div>
                            <div class="countdown-separator">:</div>
                            <div class="countdown-section">
                                <div class="countdown-value">
                                    {{
                                        timeLeftMinutes
                                            .toString()
                                            .padStart(2, "0")
                                    }}
                                </div>
                                <div class="countdown-label">MINUTES</div>
                            </div>
                        </div>
                    </div>

                    <!-- Room Name -->
                    <h1 class="text-6xl font-bold mt-4">
                        {{ roomName }}
                    </h1>

                    <div v-if="currentReservation" class="mt-4 text-xl">
                        <p>Reserved By: {{ currentReservation.user_name }}</p>
                        <p>Purpose: {{ currentReservation.purpose }}</p>
                        <p>Time: {{ formattedStart }} - {{ formattedEnd }}</p>
                    </div>

                    <div v-else class="text-xl">No active reservation.</div>
                </div>
            </div>

            <!-- Right Schedule Panel (Floating) -->
            <div
                :class="[
                    'absolute right-7 top-7 bottom-14 w-1/4 backdrop-blur-md rounded-3xl p-6 flex flex-col ',
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
                            class="absolute top-2 right-2 text-white text-xs font-bold px-2 py-1 rounded-lg shadow-xl"
                            :class="
                                roomStatus === 'in_use'
                                    ? 'bg-red-600'
                                    : 'bg-green-600'
                            "
                        >
                            COMPLETED
                        </div>

                        <!-- In Progress Stamp -->
                        <div
                            v-else-if="event.status === 'in_progress'"
                            class="absolute top-2 right-2 bg-green-600 text-white text-xs font-bold px-2 py-1 rounded-lg shadow-xl"
                        >
                            IN PROGRESS
                        </div>

                        <!-- Upcoming Stamp -->
                        <div
                            v-else-if="event.status === 'upcoming'"
                            class="absolute top-2 right-2 bg-yellow-300 text-black text-xs font-bold px-2 py-1 rounded-lg shadow-xl"
                        >
                            UPCOMING
                        </div>

                        <!-- Active Stamp (backward compatibility) -->
                        <div
                            v-else-if="event.status === 'active'"
                            class="absolute top-2 right-2 bg-green-600 text-white text-xs font-bold px-2 py-1 rounded-lg shadow-xl"
                        >
                            IN PROGRESS
                        </div>

                        <div class="text-2xl">
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

                        <div class="text-2xl font-semibold">
                            {{ event.purpose }}
                        </div>
                        <div class="text-xl">
                            {{ event.user_name }}
                        </div>
                    </div>
                    <div v-else class="text-center py-4">
                        No Active Reservation Today
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div
            class="absolute bottom-0 left-0 w-full h-10"
            :class="roomStatus === 'in_use' ? 'bg-red-700' : 'bg-white/20'"
        >
            <!-- Wrapper (your second div) -->
            <div class="h-full flex items-center relative">
                <!-- TEXT (inside second div, but floating, not affecting layout) -->
                <p
                    v-if="roomStatus === 'in_use'"
                    class="absolute left-3 z-10 text-white font-bold text-xl"
                >
                    {{ timeLeftHours }}h {{ timeLeftMinutes }}m left
                </p>

                <!-- Green Progress (your third div) -->
                <div
                    class="h-full transition-all duration-1000"
                    :class="
                        roomStatus === 'in_use'
                            ? 'bg-green-600'
                            : 'bg-green-500'
                    "
                    :style="`width: ${progress}%;`"
                ></div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.flip-countdown-container {
    min-height: 120px;
    display: flex ;
    align-items: center;
    justify-content: center;
}

.countdown-simple {
    display: flex;
    align-items: center;
    gap: 15px;
    font-family: "Courier New", monospace;
}

.countdown-section {
    text-align: center;
    min-width: 100px;
}

.countdown-value {
    font-size: 3rem;
    font-weight: bold;
    background: rgba(0, 0, 0, 0.7);
    padding: 15px;
    border-radius: 10px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    min-width: 80px;
}

.countdown-label {
    font-size: 0.8rem;
    margin-top: 5px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.countdown-separator {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 20px;
}

.text-shadow {
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
}

.text-shadow-xl {
    text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.8);
}

/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 3px;
}

/* Flip countdown custom styles */
:deep(.flip-card) {
    border-radius: 8px !important;
}

:deep(.flip-clock) {
    font-family: "Courier New", monospace !important;
}

:deep(.flip-unit) {
    font-size: 0.8rem !important;
    color: white !important;
}
</style>
