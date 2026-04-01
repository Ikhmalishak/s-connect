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
import { cn } from "@/lib/utils";
import axios from "axios";
import type { DateRange } from "reka-ui";
import type { Ref } from "vue";
import { Button } from "@/components/ui/button";

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
    }),
);

const formattedTime = computed(() =>
    currentTime.value.toLocaleTimeString("en-GB"),
);

async function fetchReport() {
    try {
        const res = await axios.get("/visitor/get-visitor-report-data");

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

watch(value, () => {
    fetchReport();
});

watch(selectedSite, () => {
    fetchReport();
});

onMounted(() => {
    fetchReport();

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
            "Listening for VisitorRegistered and NotifyGuard events via Reverb.",
        );
    } else {
        console.error(
            "Laravel Echo is not initialized. Please check resources/js/app.js.",
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
            'Stopped listening for VisitorRegistered events on "visitors" channel and "guard".',
        );
    }
});
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
                        <BreadcrumbPage
                            >Visitor Report Dashboard</BreadcrumbPage
                        >
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
                </div>

                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
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
                            Site 1
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
                            Site 2
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
                            Site 3
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
                            Site 4
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
                            SKP BM
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
                            SKP BP
                        </div>
                    </Card>
                </div>
            </Card>
        </div>
    </AdminAuthenticatedLayout>
</template>
