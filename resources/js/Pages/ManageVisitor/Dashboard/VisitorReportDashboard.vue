<script setup lang="ts">
import AdminAuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
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
import { DonutChart } from "@/components/ui/chart-donut";
import { BarChart } from "@/components/ui/chart-bar";
import { RangeCalendar } from "@/components/ui/range-calendar";
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
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { cn } from "@/lib/utils";
import axios from "axios";
import type { DateRange } from "reka-ui";
import type { Ref } from "vue";
import { Button } from "@/components/ui/button";
import { Link } from "@inertiajs/vue3";

// Statistics
const totalVisitors = ref(0);
const totalDriver = ref(0);
const totalContractor = ref(0);
const totalNewVisitor = ref(0);
const totalExisitingVisitor = ref(0);
const total = ref(0);
const selectedSite = ref("all");
const selectedSite2 = ref("all");
const selectedYear = ref("2025");
const purposeData = ref([]);
const driverData = ref([]);
const companyData = ref([]);
const currentTime = ref(new Date());
const visitorByWeek = ref([]);
const now = today(getLocalTimeZone());
let intervalId;

const df = new DateFormatter("en-US", {
    dateStyle: "medium",
});

const value = ref({
    start: now,
    end: now,
}) as Ref<DateRange>;

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

async function fetchReport() {
    try {
        const res = await axios.get("/visitor/get-visitor-report-data", {
            params: {
                start_date: value.value?.start
                    ? formatDateLocal(value.value.start)
                    : null,
                end_date: value.value?.end
                    ? formatDateLocal(value.value.end)
                    : null,
                site: selectedSite.value,
            },
        });

        console.log("API Response:", res.data);

        totalVisitors.value = res.data.total_visitor;
        totalDriver.value = res.data.total_driver;
        totalContractor.value = res.data.total_contractor;
        totalNewVisitor.value = res.data.new_visitor;
        totalExisitingVisitor.value = res.data.existing_visitor;
        total.value = res.data.total_all;
        let purpose = res.data.purpose;
        let driver = res.data.driver;
        let company = res.data.company;

        purposeData.value = purpose.map((p) => ({
            name: p.purpose,
            total: p.total,
        }));

        driverData.value = driver.map((d) => ({
            name: d.purpose,
            total: d.total,
        }));

        companyData.value = company.map((c) => ({
            name: c.visitor_company,
            total: c.total,
        }));
    } catch (e) {
        console.error("Failed to fetch statistic all sites", e);
    }
}

async function getVisitorByWeek() {
    try {
        const res = await axios.get("/visitor/get-visitor-data-by-week", {
            params: {
                year: selectedYear.value,
                site: selectedSite2.value,
            },
        });
        let visitor = res.data.data;

        visitorByWeek.value = visitor.map((v) => ({
            name: v.name,
            contractor: v.contractor,
            "driver-inbound": v["driver-inbound"],
            "driver-outbound": v["driver-outbound"],
            visitor: v.visitor,
        }));
    } catch (e) {
        console.error("Failed to fetch visitor by week:", e);
    }
}

watch(value, () => {
    fetchReport();
});

watch(selectedSite, () => {
    fetchReport();
});

watch(selectedYear, () => {
    getVisitorByWeek();
});

watch(selectedSite2, () => {
    getVisitorByWeek();
});

onMounted(() => {
    fetchReport();
    getVisitorByWeek();

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

const firstDonutData = computed(() => [
    {
        name: "New Visitor",
        total: totalNewVisitor.value,
    },
    {
        name: "Existing Visitor",
        total: totalExisitingVisitor.value,
    },
]);

const categoryColors = [
    { key: "visitor", color: "#2563EB" }, // blue-600
    { key: "driver-inbound", color: "#3B82F6" }, // blue-500
    { key: "driver-outbound", color: "#60A5FA" }, // blue-400
    { key: "contractor", color: "#93C5FD" }, // blue-300
];
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
                        <BreadcrumbPage>Visitor Report Dashboard</BreadcrumbPage>
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
                    <div class="flex flex-row items-center gap-4">
                        <div>
                            <Select v-model="selectedSite">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Site" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>Site</SelectLabel>
                                        <SelectItem value="all">
                                            All
                                        </SelectItem>
                                        <SelectItem value="s1">
                                            Site 1
                                        </SelectItem>
                                        <SelectItem value="s2">
                                            Site 2
                                        </SelectItem>
                                        <SelectItem value="s3">
                                            Site 3
                                        </SelectItem>
                                        <SelectItem value="s4">
                                            Site 4
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>

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
                                        <template v-if="value.start">
                                            <template v-if="value.end">
                                                {{
                                                    df.format(
                                                        value.start.toDate(
                                                            getLocalTimeZone()
                                                        )
                                                    )
                                                }}
                                                -
                                                {{
                                                    df.format(
                                                        value.end.toDate(
                                                            getLocalTimeZone()
                                                        )
                                                    )
                                                }}
                                            </template>
                                            <template v-else>
                                                {{
                                                    df.format(
                                                        value.start.toDate(
                                                            getLocalTimeZone()
                                                        )
                                                    )
                                                }}
                                            </template>
                                        </template>
                                        <template v-else>
                                            Pick a date
                                        </template>
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent
                                    class="w-auto p-2 flex flex-col gap-2"
                                >
                                    <!-- Quick filters -->
                                    <div class="flex gap-2">
                                        <Button
                                            size="sm"
                                            variant="secondary"
                                            @click="
                                                () => {
                                                    const now = today(
                                                        getLocalTimeZone()
                                                    );
                                                    value.start = now.set({
                                                        day: 1,
                                                    });
                                                    value.end = now.set({
                                                        day: now
                                                            .toDate(
                                                                getLocalTimeZone()
                                                            )
                                                            .getDate(),
                                                    });
                                                }
                                            "
                                        >
                                            This Month
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="secondary"
                                            @click="
                                                () => {
                                                    const now = today(
                                                        getLocalTimeZone()
                                                    );
                                                    const firstDay = now
                                                        .subtract({ months: 1 })
                                                        .set({ day: 1 });
                                                    const lastDay = now.set({
                                                        day: 0,
                                                    }); // last day of previous month
                                                    value.start = firstDay;
                                                    value.end = lastDay;
                                                }
                                            "
                                        >
                                            Last Month
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="secondary"
                                            @click="
                                                () => {
                                                    const now = today(
                                                        getLocalTimeZone()
                                                    );
                                                    value.start = now;
                                                    value.end = now;
                                                }
                                            "
                                        >
                                            Today
                                        </Button>
                                    </div>

                                    <!-- Calendar for custom range -->
                                    <RangeCalendar
                                        v-model="value"
                                        initial-focus
                                        :number-of-months="1"
                                        @update:start-value="
                                            (startDate) =>
                                                (value.start = startDate)
                                        "
                                    />
                                </PopoverContent>
                            </Popover>
                        </div>
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
                >
                    <Card
                        class="p-6 text-center h-32 flex flex-col justify-center bg-gradient-to-br from-blue-500 to-blue-600 border-0 shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 cursor-pointer group"
                    >
                        <div
                            class="text-3xl font-bold text-white mb-1 group-hover:scale-110 transition-transform"
                        >
                            {{ totalVisitors }}
                        </div>
                        <div class="text-sm text-blue-100 font-medium">
                            Total Visitors
                        </div>
                    </Card>
                    <Card
                        class="p-6 text-center h-32 flex flex-col justify-center bg-gradient-to-br from-emerald-500 to-green-600 border-0 shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 cursor-pointer group"
                    >
                        <div
                            class="text-3xl font-bold text-white mb-1 group-hover:scale-110 transition-transform"
                        >
                            {{ totalDriver }}
                        </div>
                        <div class="text-sm text-emerald-100 font-medium">
                            Total Drivers
                        </div>
                    </Card>
                    <Card
                        class="p-6 text-center h-32 flex flex-col justify-center bg-gradient-to-br from-amber-500 to-orange-600 border-0 shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 cursor-pointer group"
                    >
                        <div
                            class="text-3xl font-bold text-white mb-1 group-hover:scale-110 transition-transform"
                        >
                            {{ totalContractor }}
                        </div>
                        <div class="text-sm text-amber-100 font-medium">
                            Total Contractors
                        </div>
                    </Card>
                    <Card
                        class="p-6 text-center h-32 flex flex-col justify-center bg-gradient-to-br from-purple-500 to-indigo-600 border-0 shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 cursor-pointer group"
                    >
                        <div
                            class="text-3xl font-bold text-white mb-1 group-hover:scale-110 transition-transform"
                        >
                            {{ total }}
                        </div>
                        <div class="text-sm text-purple-100 font-medium">
                            Total Overall
                        </div>
                    </Card>
                </div>
            </Card>
        </div>

        <!-- Statistics Overview - Fixed Width/Height -->
        <div class="mt-4">
            <Card class="p-4">
                <div class="items-center mb-4">
                    <div class="text-center mb-4">
                        <label class="text-lg font-semibold"
                            >Statistics Overview</label
                        >
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
                >
                    <Card
                        class="p-6 text-center flex flex-col justify-center bg-gradient-to-br from-slate-50 to-blue-50 border border-blue-200 shadow-md hover:shadow-lg transition-all duration-300"
                    >
                        <DonutChart
                            index="name"
                            :category="'total'"
                            :data="firstDonutData"
                            :type="'pie'"
                            :colors="[
                                '#DC143C', // gray-900
                                '#F7CAC9', // gray-700
                            ]"
                        />
                        <div class="mt-3 text-sm font-medium text-gray-700">
                            New and Existing Visitor
                        </div>
                    </Card>
                    <Card
                        class="p-6 text-center flex flex-col justify-center bg-gradient-to-br from-slate-50 to-green-50 border border-green-200 shadow-md hover:shadow-lg transition-all duration-300"
                    >
                        <DonutChart
                            index="name"
                            :category="'total'"
                            :data="purposeData"
                            :type="'pie'"
                            :colors="[
                                '#134686', // red-500
                                '#ED3F27', // blue-500
                                '#FEB21A', // green-500
                                '#FDF4E3', // amber-500
                                '#E73879', // violet-500
                            ]"
                        />
                        <div class="mt-3 text-sm font-medium text-gray-700">
                            Visit By Purpose
                        </div>
                    </Card>

                    <Card
                        class="p-6 text-center flex flex-col justify-center bg-gradient-to-br from-slate-50 to-orange-50 border border-orange-200 shadow-md hover:shadow-lg transition-all duration-300"
                    >
                        <DonutChart
                            index="name"
                            :category="'total'"
                            :data="driverData"
                            :type="'pie'"
                            :colors="[
                                '#3B0270', // orange-500
                                '#E9B3FB', // orange-400
                            ]"
                        />
                        <div class="mt-3 text-sm font-medium text-gray-700">
                            Driver Incoming/Outcoming
                        </div>
                    </Card>

                    <Card
                        class="p-6 text-center flex flex-col justify-center bg-gradient-to-br from-slate-50 to-purple-50 border border-purple-200 shadow-md hover:shadow-lg transition-all duration-300"
                    >
                        <DonutChart
                            index="name"
                            :category="'total'"
                            :data="companyData"
                            :type="'pie'"
                            :colors="[
                                '#059669', // emerald-600
                                '#10B981', // emerald-500
                                '#14B8A6', // teal-500
                                '#2DD4BF', // teal-400
                                '#99F6E4', // teal-200
                            ]"
                        />
                        <div class="mt-3 text-sm font-medium text-gray-700">
                            Highest Visitor Company
                        </div>
                    </Card>
                </div>
            </Card>
        </div>

        <!-- Analytics Chart -->
        <div class="mt-4">
            <Card class="p-4">
                <div class="flex flex-row justify-between">
                    <div></div>
                    <div class="text-lg font-semibold mb-4 text-center">
                        Visitor Analytics - All Sites
                    </div>
                    <div class="flex flex-row gap-4">
                        <div>
                            <Select v-model="selectedSite2">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Site" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>Site</SelectLabel>
                                        <SelectItem value="all">
                                            All
                                        </SelectItem>
                                        <SelectItem value="s1">
                                            Site 1
                                        </SelectItem>
                                        <SelectItem value="s2">
                                            Site 2
                                        </SelectItem>
                                        <SelectItem value="s3">
                                            Site 3
                                        </SelectItem>
                                        <SelectItem value="s4">
                                            Site 4
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Select v-model="selectedYear">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Year" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>Site</SelectLabel>
                                        <SelectItem value="2025">
                                            2025
                                        </SelectItem>
                                        <SelectItem value="2024">
                                            2024
                                        </SelectItem>
                                        <SelectItem value="2023">
                                            2023
                                        </SelectItem>
                                        <SelectItem value="2022">
                                            2022
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <BarChart
                    :data="visitorByWeek"
                    index="name"
                    :categories="categoryColors.map((c) => c.key)"
                    :colors="categoryColors.map((c) => c.color)"
                    :y-formatter="
                        (tick, i) => {
                            return typeof tick === 'number'
                                ? `${new Intl.NumberFormat('us').format(tick)}`
                                : '';
                        }
                    "
                    :type="'stacked'"
                />
            </Card>
        </div>
    </AdminAuthenticatedLayout>
</template>
