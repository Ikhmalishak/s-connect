<script setup lang="ts">
import { DonutChart } from "@/components/ui/chart-donut";
import { Card } from "@/components/ui/card";
import { ref, onMounted } from "vue";
import axios from "axios";

const containerStats = ref({
    totalContainers: 0,
    containerTypes: [],
    statusStats: [],
    stageStats: [],
    inspectionStats: []
});

const loading = ref(true);

const fetchStats = async () => {
    try {
        const response = await axios.get('/container/stats');
        containerStats.value = response.data;
    } catch (error) {
        console.error('Error fetching container stats:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchStats();
});
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
                    Container Statistics
                </span>
            </div>

            <Card class="shadow-2xl shadow-opacity-60 h-[280px]">
                <div
                    class="flex flex-wrap justify-center items-center gap-4 p-4 h-full"
                >
                    <!-- Container Types Donut -->
                    <div class="flex-1 h-full flex justify-center">
                        <div class="w-full max-w-[250px] h-[185px]">
                            <DonutChart
                                index="name"
                                title="Container Types"
                                :category="'total'"
                                :data="containerStats.containerTypes"
                                :type="'donut'"
                                :colors="[
                                    'hsl(0, 100%, 70%)',
                                    'hsl(0, 85%, 60%)',
                                    'hsl(0, 75%, 50%)',
                                    'hsl(0, 65%, 40%)',
                                ]"
                                class="w-4/5 h-4/5"
                            />
                        </div>
                    </div>

                    <!-- Status Donut -->
                    <div class="flex-1 h-full flex justify-center">
                        <div class="w-full max-w-[250px] h-[185px]">
                            <DonutChart
                                index="name"
                                title="Status Overview"
                                :category="'total'"
                                :data="containerStats.statusStats"
                                class="w-4/5 h-4/5"
                            />
                        </div>
                    </div>

                    <!-- Inspection Status Donut -->
                    <div class="flex-1 h-full flex justify-center">
                        <div class="w-full max-w-[250px] h-[185px]">
                            <DonutChart
                                index="name"
                                title="Inspection Status"
                                :category="'total'"
                                :data="containerStats.inspectionStats"
                                :type="'donut'"
                                class="w-4/5 h-4/5"
                            />
                        </div>
                    </div>

                    <!-- Stage Progress Donut -->
                    <div class="flex-1 h-full flex justify-center">
                        <div class="w-full max-w-[250px] h-[185px]">
                            <DonutChart
                                index="name"
                                title="Stage Progress"
                                :category="'total'"
                                :data="containerStats.stageStats"
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
