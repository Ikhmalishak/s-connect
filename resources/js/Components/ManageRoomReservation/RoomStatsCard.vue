<script setup lang="ts">
import { BarChart } from "@/components/ui/chart-bar";
import { Card } from "@/components/ui/card";
import DonutChart from "@/components/ui/chart-donut/DonutChart.vue";

const data = [
    {
        name: "Jan",
        total: Math.floor(Math.random() * 2000) + 500,
        predicted: Math.floor(Math.random() * 2000) + 500,
    },
    {
        name: "Feb",
        total: Math.floor(Math.random() * 2000) + 500,
        predicted: Math.floor(Math.random() * 2000) + 500,
    },
];
const data2 = [
    {
        name: "Jan",
        total: Math.floor(Math.random() * 2000) + 500,
        predicted: Math.floor(Math.random() * 2000) + 500,
    },
    {
        name: "Feb",
        total: Math.floor(Math.random() * 2000) + 500,
        predicted: Math.floor(Math.random() * 2000) + 500,
    },
    {
        name: "Mar",
        total: Math.floor(Math.random() * 2000) + 500,
        predicted: Math.floor(Math.random() * 2000) + 500,
    },
    {
        name: "Apr",
        total: Math.floor(Math.random() * 2000) + 500,
        predicted: Math.floor(Math.random() * 2000) + 500,
    },
    {
        name: "May",
        total: Math.floor(Math.random() * 2000) + 500,
        predicted: Math.floor(Math.random() * 2000) + 500,
    },
    {
        name: "Jun",
        total: Math.floor(Math.random() * 2000) + 500,
        predicted: Math.floor(Math.random() * 2000) + 500,
    },
    {
        name: "Jul",
        total: Math.floor(Math.random() * 2000) + 500,
        predicted: Math.floor(Math.random() * 2000) + 500,
    },
];
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <!-- LEFT (Donut) -->
        <div class="relative">
            <div class="absolute -top-3 left-2 z-10">
                <span
                    class="bg-gray-100 text-black px-2 py-1 rounded-full text-xs font-medium border shadow-sm"
                >
                    Room Status
                </span>
            </div>

            <Card class="shadow-lg p-4">
                <div
                    class="h-72 w-full grid grid-cols-2 place-items-center overflow-hidden"
                >
                    <DonutChart
                        index="name"
                        :category="'total'"
                        :data="data"
                    />
                    <DonutChart
                        index="name"
                        :category="'total'"
                        :data="data"
                    />
                </div>
            </Card>
        </div>

        <!-- RIGHT (Bar / Area) -->
        <div class="relative">
            <div class="absolute -top-3 left-2 z-10">
                <span
                    class="bg-gray-100 text-black px-2 py-1 rounded-full text-xs font-medium border shadow-sm"
                >
                    Room Reservation by Hour
                </span>
            </div>

            <Card class="shadow-lg p-4">
                <!-- FIX HERE -->
                <div class="h-72 w-full overflow-hidden">
                    <BarChart
                        index="name"
                        :data="data2"
                        :categories="['total', 'predicted']"
                        :type="'stacked'"
                        :y-formatter="
                            (tick) =>
                                typeof tick === 'number'
                                    ? `$ ${new Intl.NumberFormat('us').format(
                                          tick
                                      )}`
                                    : ''
                        "
                    />
                </div>
            </Card>
        </div>
    </div>
</template>
