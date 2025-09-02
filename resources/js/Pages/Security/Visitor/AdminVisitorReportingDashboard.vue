<script setup lang="ts">
import AdminAuthenticatedLayout from "@/Layouts/AdminAuthenticatedLayout.vue";
import { Card, CardContent } from "@/components/ui/card";
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
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from "@/components/ui/carousel";
import { DonutChart } from "@/components/ui/chart-donut";
import { BarChart } from "@/components/ui/chart-bar";
import type { DateValue } from "@internationalized/date";
import {
    DateFormatter,
    getLocalTimeZone,
    today,
} from "@internationalized/date";
import { CalendarIcon } from "lucide-vue-next";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { cn } from "@/lib/utils";
import axios from "axios";

const df = new DateFormatter("en-US", {
    dateStyle: "long",
});
import { Button } from "@/components/ui/button";
import { Calendar } from "@/components/ui/calendar";
import { date } from "zod";

const items = [
    { value: 0, label: "Today" },
    { value: 1, label: "Tomorrow" },
    { value: 3, label: "In 3 days" },
    { value: 7, label: "In a week" },
];

// Statistics
const totalVisitors = ref(0);
const totalDriver = ref(0);
const totalContractor = ref(0);
const total = ref(0);

// Mock data for different sites
const site1Data = ref([
    { name: "Visitor", total: 45 },
    { name: "Driver Outbound", total: 55 },
    { name: "Contractor", total: 55 },
    { name: "Driver Inbound", total: 55 },
]);

const site2Data = ref([
    { name: "Visitor", total: 45 },
    { name: "Driver Outbound", total: 55 },
    { name: "Contractor", total: 55 },
    { name: "Driver Inbound", total: 55 },
]);

const site3Data = ref([
    { name: "Visitor", total: 45 },
    { name: "Driver Outbound", total: 55 },
    { name: "Contractor", total: 55 },
    { name: "Driver Inbound", total: 55 },
]);

const site4Data = ref([
    { name: "Visitor", total: 45 },
    { name: "Driver Outbound", total: 55 },
    { name: "Contractor", total: 55 },
    { name: "Driver Inbound", total: 55 },
]);

const currentTime = ref(new Date());
const value = ref<any>(today(getLocalTimeZone())); // quick fix
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

function formatDateLocal(dateValue: DateValue) {
    const d = dateValue.toDate(getLocalTimeZone());
    return d.toLocaleDateString("en-CA"); // gives YYYY-MM-DD in local timezone
}

async function fetchStatisticAllSites() {
    try {
        const res = await axios.get("/admin/visitor/get-statistic-all-sites", {
            params: {
                date: value.value ? formatDateLocal(value.value) : null,
            },
        });
        console.log(res.data);
        console.log("Success to fetch statistic all sites");

        totalVisitors.value = res.data.total_visitor;
        totalDriver.value = res.data.total_driver;
        totalContractor.value = res.data.total_contractor;
        total.value = res.data.total_all;
    } catch (e) {
        console.log("Failed to fetch statistic all sites", e);
    }
}

function mapVisitorType(type: string): string {
    if (type.startsWith("inbound")) return "Driver Inbound";
    if (type.startsWith("outbound")) return "Driver Outbound";
    if (type === "visitor") return "Visitor";
    if (type === "contractor") return "Contractor";
    return type; // fallback
}

async function fetchStatisticBySites() {
    try {
        const res = await axios.get("/admin/visitor/get-statistic-by-sites", {
            params: {
                date: value.value ? formatDateLocal(value.value) : null,
            },
        });

        site1Data.value = res.data.site1.map((item: any) => ({
            name: mapVisitorType(item.visitor_type),
            total: item.total,
        }));

        site2Data.value = res.data.site2.map((item: any) => ({
            name: mapVisitorType(item.visitor_type),
            total: item.total,
        }));

        site3Data.value = res.data.site3.map((item: any) => ({
            name: mapVisitorType(item.visitor_type),
            total: item.total,
        }));

        site4Data.value = res.data.site4.map((item: any) => ({
            name: mapVisitorType(item.visitor_type),
            total: item.total,
        }));

        console.log("Mapped site2Data:", site2Data.value);
    } catch (e) {
        console.error("Failed to fetch statistic by sites", e);
    }
}

watch(value, () => {
    fetchStatisticAllSites();
    fetchStatisticBySites();
});

onMounted(() => {
    fetchStatisticAllSites();
    fetchStatisticBySites();

    intervalId = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);

    if (window.Echo) {
        // Existing listener
        window.Echo.channel("visitors")
            .listen(".visitor.registered", (e) => {
                console.log("New VisitorRegistered event received:", e);
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
                }
            })
            .error((error) => {
                console.error("WebSocket error on guard channel:", error);
            });

        console.log(
            "Listening for VisitorRegistered and NotifyGuard events via Reverb."
        );
    } else {
        console.error(
            "Laravel Echo is not initialized. Please check resources/js/app.js."
        );
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

function getRandom(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

const analyticsData = ref(
    Array.from({ length: 52 }, (_, i) => ({
        name: `Week ${i + 1}`,
        site1: getRandom(20, 70),
        site2: getRandom(10, 50),
        site3: getRandom(10, 40),
        site4: getRandom(5, 30),
    }))
);
</script>

<template>
    <AdminAuthenticatedLayout>
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
                        <BreadcrumbPage>AdminDashboard</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>
        </template>

        <!-- Header Card -->
        <Card
            class="shadow-lg shadow-opacity-30 p-4 mb-4 text-2xl font-bold flex items-center justify-between bg-gray-100"
        >
            <div class="flex flex-row items-center space-x-3">
                <img src="/assets/ss1.png" class="h-12 w-12" alt="" />
                <div>Visitor Management System</div>
            </div>
            <div
                class="flex flex-row space-x-4 text-base font-normal text-gray-600 text-right"
            >
                <div>{{ formattedDate }}</div>
                <div>{{ formattedTime }}</div>
            </div>
        </Card>
        <!-- Statistics Overview - Fixed Width/Height -->
        <div class="mt-4">
            <Card class="p-4">
                <div class="flex flex-row justify-between items-center mb-4">
                    <div class="text-center mb-4">
                        <label class="text-lg font-semibold"
                            >Statistics Overview</label
                        >
                    </div>
                    <div>
                        <div>
                            <Popover>
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        :class="
                                            cn(
                                                'w-[280px] justify-start text-left font-normal',
                                                !value &&
                                                    'text-muted-foreground'
                                            )
                                        "
                                    >
                                        <CalendarIcon class="mr-2 h-4 w-4" />
                                        {{
                                            value
                                                ? df.format(
                                                      value.toDate(
                                                          getLocalTimeZone()
                                                      )
                                                  )
                                                : "Pick a date"
                                        }}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent
                                    class="flex w-auto flex-col gap-y-2 p-2"
                                >
                                    <Select
                                        @update:model-value="
                                            (v) => {
                                                if (!v) return;
                                                value = today(
                                                    getLocalTimeZone()
                                                ).add({ days: Number(v) });
                                            }
                                        "
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="item in items"
                                                :key="item.value"
                                                :value="item.value.toString()"
                                            >
                                                {{ item.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Calendar v-model="value" />
                                </PopoverContent>
                            </Popover>
                        </div>
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
                >
                    <Card
                        class="p-4 text-center h-24 flex flex-col justify-center bg-blue-50 border-blue-200"
                    >
                        <div class="text-2xl font-bold text-blue-600">
                            {{ totalVisitors }}
                        </div>
                        <div class="text-sm text-gray-600">Total Visitor</div>
                    </Card>
                    <Card
                        class="p-4 text-center h-24 flex flex-col justify-center bg-green-50 border-green-200"
                    >
                        <div class="text-2xl font-bold text-green-600">
                            {{ totalDriver }}
                        </div>
                        <div class="text-sm text-gray-600">Total Driver</div>
                    </Card>
                    <Card
                        class="p-4 text-center h-24 flex flex-col justify-center bg-orange-50 border-orange-200"
                    >
                        <div class="text-2xl font-bold text-orange-600">
                            {{ totalContractor }}
                        </div>
                        <div class="text-sm text-gray-600">
                            Total Contractor
                        </div>
                    </Card>
                    <Card
                        class="p-4 text-center h-24 flex flex-col justify-center bg-purple-50 border-purple-200"
                    >
                        <div class="text-2xl font-bold text-purple-600">
                            {{ total }}
                        </div>
                        <div class="text-sm text-gray-600">Total</div>
                    </Card>
                </div>
            </Card>
        </div>

        <!-- Site Monitoring Cards -->
        <div class="mt-4">
            <Card class="p-4">
                <div class="text-center mb-4">
                    <label class="text-lg font-semibold"
                        >Statistics Overview</label
                    >
                </div>
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 mt-4"
                >
                    <Card class="p-4 text-center">
                        <div class="text-lg font-semibold mb-2">Site 1</div>
                        <DonutChart
                            v-if="site1Data.length > 0"
                            index="name"
                            :category="'total'"
                            :data="site1Data"
                            :type="'pie'"
                            :colors="[
                                'hsl(120, 50%, 75%)',
                                'hsl(120, 50%, 55%)',
                                'hsl(120, 50%, 35%)',
                                'hsl(120, 50%, 20%)',
                            ]"
                        />
                        <div v-else class="text-center text-gray-500 p-4">
                            No data available
                        </div>
                    </Card>

                    <Card class="p-4 text-center">
                        <div class="text-lg font-semibold mb-2">Site 2</div>
                        <DonutChart
                            v-if="site2Data.length > 0"
                            index="name"
                            :category="'total'"
                            :data="site2Data"
                            :type="'pie'"
                            :colors="[
                                'hsl(120, 50%, 75%)',
                                'hsl(120, 50%, 55%)',
                                'hsl(120, 50%, 35%)',
                                'hsl(120, 50%, 20%)',
                            ]"
                        />
                        <div v-else class="text-center text-gray-500 p-4">
                            No data available
                        </div>
                    </Card>
                    <Card class="p-4 text-center">
                        <div class="text-lg font-semibold mb-2">Site 3</div>
                        <DonutChart
                            v-if="site3Data.length > 0"
                            index="name"
                            :category="'total'"
                            :data="site3Data"
                            :type="'pie'"
                            :colors="[
                                'hsl(120, 50%, 75%)',
                                'hsl(120, 50%, 55%)',
                                'hsl(120, 50%, 35%)',
                                'hsl(120, 50%, 20%)',
                            ]"
                        />

                        <div v-else class="text-center text-gray-500 p-4">
                            No data available
                        </div>
                    </Card>
                    <Card class="p-4 text-center">
                        <div class="text-lg font-semibold mb-2">Site 4</div>
                        <DonutChart
                            v-if="site4Data.length > 0"
                            index="name"
                            :category="'total'"
                            :data="site4Data"
                            :type="'pie'"
                            :colors="[
                                'hsl(120, 50%, 75%)',
                                'hsl(120, 50%, 55%)',
                                'hsl(120, 50%, 35%)',
                                'hsl(120, 50%, 20%)',
                            ]"
                        />

                        <div v-else class="text-center text-gray-500 p-4">
                            No data available
                        </div>
                    </Card>
                </div>
            </Card>
        </div>

        <!-- Analytics Chart -->
        <div class="mt-4">
            <Card class="p-4">
                <div class="text-lg font-semibold mb-4 text-center">
                    Visitor Analytics - All Sites
                </div>
                <BarChart
                    :data="analyticsData"
                    index="name"
                    :categories="['site1', 'site2', 'site3', 'site4']"
                    :y-formatter="
                        (tick, i) => {
                            return typeof tick === 'number'
                                ? `${new Intl.NumberFormat('us')
                                      .format(tick)
                                      .toString()}`
                                : '';
                        }
                    "
                    :type="'stacked'"
                />
            </Card>
        </div>

        <!-- Site Monitoring Cards -->
        <div class="mt-4">
            <Card class="p-4">
                <div class="text-center mb-4">
                    <label class="text-lg font-semibold"
                        >Statistics Overview</label
                    >
                </div>
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 gap-4 mb-6 mt-4"
                >
                    <Carousel class="relative w-full max-w-xs mx-auto">
                        <CarouselContent>
                            <CarouselItem v-for="(_, index) in 5" :key="index">
                                <div class="p-1">
                                    <Card>
                                        <CardContent
                                            class="flex aspect-square items-center justify-center p-6"
                                        >
                                            <span
                                                class="text-4xl font-semibold"
                                                >{{ index + 1 }}</span
                                            >
                                        </CardContent>
                                    </Card>
                                </div>
                            </CarouselItem>
                        </CarouselContent>
                        <CarouselPrevious />
                        <CarouselNext />
                    </Carousel>
                    <Carousel class="relative w-full max-w-xs mx-auto">
                        <CarouselContent>
                            <CarouselItem v-for="(_, index) in 5" :key="index">
                                <div class="p-1">
                                    <Card>
                                        <CardContent
                                            class="flex aspect-square items-center justify-center p-6"
                                        >
                                            <span
                                                class="text-4xl font-semibold"
                                                >{{ index + 1 }}</span
                                            >
                                        </CardContent>
                                    </Card>
                                </div>
                            </CarouselItem>
                        </CarouselContent>
                        <CarouselPrevious />
                        <CarouselNext />
                    </Carousel>
                </div>
            </Card>
        </div>
    </AdminAuthenticatedLayout>
</template>
