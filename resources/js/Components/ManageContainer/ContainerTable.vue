<script setup lang="ts">
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Plus } from "lucide-vue-next";
import { Card } from "@/components/ui/card";
import CustomTooltip from "../CustomTooltip.vue";
import { onMounted, ref } from "vue";
import axios from "axios";
import { Button } from "@/components/ui/button";

const emit = defineEmits([
    "openCreateContainerModal",
    "openContainerInspectionModal",
]);
const containers = ref([]);

async function fetchContainers() {
    // Fetch container data from API
    const res = await axios.get("/containers");
    console.log("Fetched containers:", res.data.data);
    containers.value = res.data.data;
}

async function createInspection(containerId) {
    console.log("Create Inspection clicked", containerId);

    const res = await axios.post("/containers/create-inspection", {
        shipment_transport_id: containerId,
    });

    emit("openContainerInspectionModal", containerId);
}

onMounted(() => {
    fetchContainers();
});
</script>

<template>
    <div class="relative">
        <!-- Badge -->
        <div class="absolute -top-3 left-2 z-10">
            <span
                class="bg-gray-100 text-black px-2 py-1 rounded-full text-xs font-semibold border shadow-md"
            >
                Visitor List
            </span>
        </div>

        <Card class="p-3 shadow-2xl max-h-[700px] shadow-opacity-60">
            <div class="flex space-x-4 justify-between mb-2">
                <div class="flex items-center gap-4">
                    <div class="flex flex-row space-x-2">
                        <input
                            class="w-400 bg-gray-300 text-black placeholder:text-black border-none rounded-lg text-sm"
                            placeholder="Search..."
                        />
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div>
                        <Select>
                            <SelectTrigger>
                                <SelectValue placeholder="Select limit" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="50">50</SelectItem>
                                    <SelectItem value="100">100</SelectItem>
                                    <SelectItem value="200">200</SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <CustomTooltip text="New" position="top">
                            <Plus
                                class="w-9 h-9 cursor-pointer text-black"
                                @click="$emit('openCreateContainerModal')"
                            />
                        </CustomTooltip>
                    </div>
                </div>
            </div>

            <div
                class="flex-1 overflow-y-auto max-h-[420px] border border-gray-300"
            >
                <table class="min-w-full">
                    <thead
                        class="sticky top-0 bg-gray-100 z-40 border border-b-gray-300"
                    >
                        <tr
                            class="border border-gray-300 font-black divide-x divide-gray-300 text-black"
                        >
                            <th
                                class="font-black text-black text-center bg-gray-100 p-3 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                No
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Transport Type
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Transport Number
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Container/Truck Checking
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Container/Truck Report
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Container Status
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="border border-gray-300 divide-x divide-gray-300"
                    >
                        <tr
                            v-for="(container, index) in containers"
                            :key="container.id"
                            class="text-center text-sm border border-gray-300 divide-x divide-gray-300 p-2"
                        >
                            <td class="p-2">{{ index + 1 }}</td>
                            <td class="p-2">{{ container.transport_type }}</td>
                            <td class="p-2">
                                {{ container.transport_number }}
                            </td>
                            <td class="p-2">
                                <Button
                                    v-if="
                                        container.inspection === null ||
                                        container.inspection.status ===
                                            'pending'
                                    "
                                    variant="outline"
                                    class="bg-blue-600 text-white"
                                    @click="createInspection(container.id)"
                                >
                                    Start Inspection
                                </Button>

                                <Button
                                    v-else-if="
                                        container.inspection &&
                                        ['passed', 'failed'].includes(
                                            container.inspection.status
                                        )
                                    "
                                    variant="outline"
                                    class="bg-yellow-500 text-white"
                                >
                                    View Inspection
                                </Button>
                            </td>

                            <td class="p-2">
                                <Button
                                    variant="outline"
                                    class="bg-green-600 text-white"
                                    >Create Record</Button
                                >
                            </td>
                            <td class="p-2">{{ container.status }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
