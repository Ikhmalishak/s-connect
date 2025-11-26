<script setup lang="ts">
import { DonutChart } from '@/components/ui/chart-donut';
import { Card } from "@/components/ui/card";

const visitorInsidePieChart = [
    { name: "Employee", total: 120 },
    { name: "Contractor", total: 80 },
    { name: "Guest", total: 40 },
]

const visitorOutsidePieChart = [
    { name: "Employee", total: 120 },
    { name: "Contractor", total: 80 },
    { name: "Guest", total: 40 },
]
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-8 gap-2 mb-4">
        <!-- Donut Card with Fixed Height -->
        <div class="relative lg:col-span-8">
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
                                class="w-4/5 h-4/5"
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
                                class="w-4/5 h-4/5"
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
                                class="w-4/5 h-4/5"
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
                                class="w-4/5 h-4/5"
                            />
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    </div>
</template>
