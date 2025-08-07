<script setup lang="ts">
import { DonutChart } from "@/components/ui/chart-bar/chart-donut";
import { AreaChart } from "@/components/ui/chart-area";
import { Card } from "@/components/ui/card";
import { computed } from "vue";

interface VisitorForm2 {
    visitor_type: string;
    total?: number;
}

interface VisitorInByHour {
    hour: string;
    total_in: number;
}

interface VisitorOutByHour {
    hour: string;
    total_out: number;
}

const props = defineProps<{
    visitorIn: VisitorForm2[];
    visitorOut: VisitorForm2[];
    visitorInByHour: VisitorInByHour[];
    visitorOutByHour: VisitorOutByHour[];
}>();

const emit = defineEmits(["donutClick"]);

const visitorInsidePieChart = computed(() => {
    const data = props.visitorIn.map((item) => ({
        name: item.visitor_type || "Unknown",
        total: item.total || 0,
    }));
    return data.length ? data : [{ name: "No Data", total: 0 }];
});

const visitorOutsidePieChart = computed(() => {
    const data = props.visitorOut.map((item) => ({
        name: item.visitor_type || "Unknown",
        total: item.total || 0,
    }));
    return data.length ? data : [{ name: "No Data", total: 0 }];
});

const visitorInOutByHourData = computed(() => {
    const inMap = new Map(
        props.visitorInByHour.map((item) => [Number(item.hour), item.total_in])
    );

    const outMap = new Map(
        props.visitorOutByHour.map((item) => [
            Number(item.hour),
            item.total_out,
        ])
    );

    return Array.from({ length: 24 }, (_, hour) => ({
        name: hour.toString().padStart(2, "0"),
        total_in: inMap.get(hour) ?? 0,
        total_out: outMap.get(hour) ?? 0,
    }));
});

function handleDonutClick(chartType: string) {
    emit("donutClick", chartType);
}
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-8 gap-2 mb-4">
        <!-- Donut Card with Fixed Height -->
        <div class="relative lg:col-span-4">
            <!-- Overlay Badge -->
            <div class="absolute -top-3 left-2 z-10">
                <span
                    class="bg-gray-100 text-black px-2 py-1 rounded-full text-xs font-small border shadow-md"
                >
                    Visitor Status
                </span>
            </div>

            <Card class="shadow-2xl shadow-opacity-60 h-[280px]">
                <div
                    class="flex flex-wrap justify-center items-center gap-4 p-4 h-full"
                >
                    <!-- Donut Chart In -->
                    <div class="flex-1 h-full flex justify-center">
                        <div class="w-full max-w-[250px] h-[185px]">
                            <DonutChart
                                index="name"
                                :category="'total'"
                                :data="visitorInsidePieChart"
                                :type="'donut'"
                                :colors="[
                                    visitorInsidePieChart.length === 1 &&
                                    visitorInsidePieChart[0].name === 'No Data'
                                        ? 'gray'
                                        : 'hsl(0, 100%, 70%)',
                                    'hsl(0, 85%, 60%)',
                                    'hsl(0, 75%, 50%)',
                                    'hsl(0, 65%, 40%)',
                                ]"
                                centralSubLabel="In"
                                class="w-4/5 h-4/5"
                                @central-click="() => handleDonutClick('in')"
                            />
                        </div>
                    </div>

                    <!-- Donut Chart Out -->
                    <div class="flex-1 h-full flex justify-center">
                        <div class="w-full max-w-[250px] h-[185px]">
                            <DonutChart
                                index="name"
                                :category="'total'"
                                :data="visitorOutsidePieChart"
                                :type="'donut'"
                                :colors="[
                                    visitorOutsidePieChart.length === 1 &&
                                    visitorOutsidePieChart[0].name === 'No Data'
                                        ? 'gray'
                                        : 'hsl(120, 50%, 75%)',
                                    'hsl(120, 50%, 55%)',
                                    'hsl(120, 50%, 35%)',
                                    'hsl(120, 50%, 20%)',
                                ]"
                                central-sub-label="Out"
                                class="w-4/5 h-4/5"
                            />
                        </div>
                    </div>
                </div>
            </Card>
        </div>

        <div class="relative lg:col-span-4">
            <!-- Overlay Badge -->
            <div class="absolute -top-3 left-2 z-10">
                <span
                    class="bg-gray-100 text-black px-2 py-1 rounded-full text-xs font-small border shadow-md"
                >
                    Visitor In/Out by Hour
                </span>
            </div>
            <!-- Area Chart Card with Fixed Height -->
            <Card
                class="p-5 shadow-2xl shadow-opacity-60 lg:col-span-2 h-[280px]"
            >
                <div class="h-full">
                    <AreaChart
                        :data="visitorInOutByHourData"
                        index="name"
                        :categories="['total_in', 'total_out']"
                        :colors="['red', 'green']"
                        class="w-full h-3/4"
                    />
                </div>
            </Card>
        </div>
    </div>
</template>
