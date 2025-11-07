<script setup lang="ts">
import { ref, watch } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {
    Breadcrumb,
    BreadcrumbPage,
    BreadcrumbSeparator,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
} from "@/components/ui/breadcrumb";
import { Card } from "@/components/ui/card";
import { computed, onMounted } from "vue";
import axios from "axios";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Button } from "@/components/ui/button";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";
import {
    DateFormatter,
    getLocalTimeZone,
    DateValue,
    today,
} from "@internationalized/date";
import { CalendarIcon } from "lucide-vue-next";
import { cn } from "@/lib/utils";
import { Calendar } from "@/components/ui/calendar";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import BookingFormModal from "@/Components/ManageRoomReservation/BookingFormModal.vue";
import BookingDetailsModal from "@/Components/ManageRoomReservation/BookingDetailsModal.vue";

const currentTime = ref(new Date());
let intervalId;
const createBookingStatusMessage = ref();
const createBookingStatusType = ref();
const cancelBookingStatusMessage = ref();
const cancelBookingStatusType = ref();
const roomList = ref<Room[]>([]);
const bookingList = ref<Booking[]>([]);
const selectedSite = ref("1");
const selectedDate = ref(today(getLocalTimeZone()));
const showBookingFormModal = ref(false);
const showBookingDetailsModal = ref(false);
const selectedBooking = ref(null);
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

interface Booking {
    id: number;
    room_id: number; // Changed from roomId to room_id
    user_name: string;
    user_id: string;
    start_time: string; // Changed from startTime to start_time
    end_time: string; // Changed from endTime to end_time
    date: string;
    purpose: string; // Changed from title to purpose
    status: "available" | "booked" | "unavailable";
    room: {
        id: number;
        site_id: number;
        name: string;
        capacity: number;
        location: string;
        status: string;
    };
}
interface Room {
    id: number;
    name: string;
    capacity: number;
}

const SLOT_HEIGHT = 60;
const START_HOUR = 8;
const GAP = 4; // space above & below each booking

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

const df = new DateFormatter("en-US", {
    dateStyle: "long",
});

async function fetchRooms(site_id = selectedSite.value) {
    const res = await axios.get("/rooms", {
        params: {
            site_id,
        },
    });
    console.log(res.data.data);
    roomList.value = res.data.data; // assign the data

    console.log(roomList.value); // log the actual array
}

async function fetchRoomReservations(
    site_id = selectedSite.value,
    date = selectedDate.value
) {
    const res = await axios.get("/room-reservations", {
        params: {
            site_id,
            date,
        },
    });
    console.log(res.data.data);
    bookingList.value = res.data.data; // assign the data

    console.log(bookingList.value); // log the actual array
}

function timeToMinutes(time: string): number {
    const [hours, minutes] = time.split(":").map(Number);
    return hours * 60 + minutes;
}

function getBookingStyle(booking: Booking) {
    // Extract time from ISO string (e.g., "2025-11-03T01:00:00.000000Z" -> "01:00")
    const extractTime = (isoString: string) => {
        const date = new Date(isoString);
        return `${date.getHours().toString().padStart(2, "0")}:${date
            .getMinutes()
            .toString()
            .padStart(2, "0")}`;
    };

    const startTime = extractTime(booking.start_time);
    const endTime = extractTime(booking.end_time);

    const startMinutes = timeToMinutes(startTime);
    const endMinutes = timeToMinutes(endTime);
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

function getBookingForRoom(roomId: number): Booking[] {
    return bookingList.value.filter((b) => b.room_id === roomId);
}

function setToday() {
    selectedDate.value = today(getLocalTimeZone());
}

function prevDay() {
    selectedDate.value = selectedDate.value.subtract({ days: 1 });
}

function nextDay() {
    selectedDate.value = selectedDate.value.add({ days: 1 });
}

const submitBooking = async (formData: any) => {
    createBookingStatusMessage.value = ""; // reset first

    try {
        const res = await axios.post("/room-reservations", formData);

        createBookingStatusMessage.value = res.data.message; // <-- show success here
        createBookingStatusType.value = "success";
        fetchRoomReservations();

        // Optionally close modal after delay
        setTimeout(() => {
            showBookingFormModal.value = false;
            createBookingStatusMessage.value = "";
            createBookingStatusType.value = "";
        }, 1500);
    } catch (error: any) {
        createBookingStatusType.value = "failed";

        createBookingStatusMessage.value =
            error.response?.data?.message || "Failed to create reservation";
    }
};

function openBookingDetails(booking) {
    selectedBooking.value = booking;
    showBookingDetailsModal.value = true;
}

const cancelBooking = async (id) => {
    try {
        const res = await axios.post(`/room-reservations/${id}/cancel`);
        cancelBookingStatusMessage.value = res.data.message;
        cancelBookingStatusType.value = "success";
        fetchRoomReservations();

        setTimeout(() => {
            cancelBookingStatusMessage.value = "";
            cancelBookingStatusType.value = "";
            showBookingDetailsModal.value = false;
        }, 1500);
    } catch (error: any) {
        cancelBookingStatusMessage.value = "failed";
        cancelBookingStatusMessage.value = "Failed to cancel booking";
    }
};

watch([selectedDate, selectedSite], (newVal) => {
    fetchRooms();
    fetchRoomReservations();
});

onMounted(() => {
    intervalId = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);

    fetchRooms();
    fetchRoomReservations(); // Add this line
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
                <div>Room Reservation Management System</div>
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

        <Card class="p-2">
            <!--Add calendar or date filterinngggggggg-->
            <div class="flex space-x-4 justify-between">
                <div class="flex flex-row gap-4">
                    <Button variant="outline" @click="setToday">Today</Button>
                    <Button variant="outline" @click="prevDay">
                        <ChevronLeft class="w-4 h-4" />
                    </Button>
                    <Button variant="outline" @click="nextDay">
                        <ChevronRight class="w-4 h-4" />
                    </Button>
                    <Popover>
                        <PopoverTrigger as-child>
                            <Button
                                variant="outline"
                                :class="
                                    cn(
                                        'w-[280px] justify-start text-left font-normal',
                                        !selectedDate && 'text-muted-foreground'
                                    )
                                "
                            >
                                <CalendarIcon class="mr-2 h-4 w-4" />
                                {{
                                    selectedDate
                                        ? df.format(
                                              selectedDate.toDate(
                                                  getLocalTimeZone()
                                              )
                                          )
                                        : "Pick a date"
                                }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0">
                            <Calendar v-model="selectedDate" initial-focus />
                        </PopoverContent>
                    </Popover>
                </div>
                <Button @click="showBookingFormModal = true">Book Now</Button>
            </div>
        </Card>

        <!-- Horizontal Scroll Wrapper -->
        <div class="overflow-x-auto mt-4" v-if="roomList.length > 0">
            <div
                class="grid relative"
                :style="`grid-template-columns: 6rem repeat(${roomList.length}, minmax(180px, 1fr));`"
            >
                <!-- Sticky Header Row -->
                <div class="sticky left-0 z-30 bg-gray-50 border-r"></div>
                <div
                    v-for="room in roomList"
                    :key="room.id"
                    class="p-2 border-b border-r bg-gray-300 sticky top-0 z-20"
                >
                    <div class="flex flex-row gap-4">
                        <img
                            src="/assets/meeting.jpeg"
                            class="h-12 w-12"
                            alt=""
                        />
                        <div>
                            <div
                                class="font-semibold text-gray-800 whitespace-nowrap"
                            >
                                {{ room.name }}
                            </div>
                            <div class="text-xs text-gray-500">
                                Capacity: {{ room.capacity }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sticky Left Time Column -->
                <div class="sticky left-0 z-30 bg-gray-300 border-r">
                    <div
                        v-for="time in timeSlots"
                        :key="time"
                        class="h-[60px] flex items-center justify-end pr-3 text-sm border-t"
                    >
                        {{ time }}
                    </div>
                </div>

                <div
                    v-for="room in roomList"
                    :key="room.id"
                    class="relative border-r min-w-[180px] overflow-hidden"
                >
                    <!-- Background hour grid -->
                    <div
                        class="absolute inset-0 pointer-events-none bg-[linear-gradient(to_bottom,_transparent_59px,_#e5e7eb_60px)] bg-[length:100%_60px]"
                    ></div>

                    <!-- Booking -->
                    <div
                        v-for="booking in getBookingForRoom(room.id)"
                        :key="booking.id"
                        :style="getBookingStyle(booking)"
                        @click="openBookingDetails(booking)"
                        class="p-2 absolute left-2 right-2 rounded-lg cursor-pointer transition-all hover:shadow-lg z-10 flex flex-col items-center justify-center text-center bg-indigo-500 border-2 border-indigo-600 text-white hover:bg-indigo-600"
                    >
                        <div
                            class="flex flex-row items-center justify-between w-full text-xs mt-1"
                        >
                            <div>
                                {{
                                    new Date(
                                        booking.start_time
                                    ).toLocaleTimeString([], {
                                        hour: "2-digit",
                                        minute: "2-digit",
                                    })
                                }}
                                -
                                {{
                                    new Date(
                                        booking.end_time
                                    ).toLocaleTimeString([], {
                                        hour: "2-digit",
                                        minute: "2-digit",
                                    })
                                }}
                            </div>
                            <div>
                                {{ booking.user_name }} ({{ booking.user_id }})
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Room Columns -->
            </div>
        </div>

        <div v-else class="py-10 text-center text-gray-500 italic">
            No rooms found for this site.
        </div>

        <!-- Legend -->
        <div class="mt-2 bg-white rounded-lg shadow-lg p-2">
            <h3 class="font-semibold text-gray-800 mb-2 text-sm">
                Status Legend
            </h3>
            <div class="flex gap-6 flex-wrap">
                <div class="flex items-center gap-2">
                    <div
                        class="w-8 h-6 bg-emerald-100 border-2 border-emerald-400 rounded"
                    ></div>
                    <span class="text-xs text-gray-700">Available</span>
                </div>
                <div class="flex items-center gap-2">
                    <div
                        class="w-8 h-6 bg-indigo-500 border-2 border-indigo-600 rounded"
                    ></div>
                    <span class="text-xs text-gray-700">Booked</span>
                </div>
                <div class="flex items-center gap-2">
                    <div
                        class="w-8 h-6 bg-gray-200 border-2 border-gray-400 rounded"
                    ></div>
                    <span class="text-xs text-gray-700">Unavailable</span>
                </div>
            </div>
        </div>

        <BookingFormModal
            :show="showBookingFormModal"
            :site="selectedSite"
            :rooms="roomList"
            :statusMessage="createBookingStatusMessage"
            :statusType="createBookingStatusType"
            @save="submitBooking"
            @close="showBookingFormModal = false"
        />

        <BookingDetailsModal
            :show="showBookingDetailsModal"
            :booking="selectedBooking"
            :statusMessage="cancelBookingStatusMessage"
            :statusType="cancelBookingStatusType"
            @close="showBookingDetailsModal = false"
            @cancel="cancelBooking"
        />
    </AuthenticatedLayout>
</template>
