<script setup lang="ts">
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { ref, watch, computed } from "vue";
import { Card } from "@/components/ui/card";
import { ScanQrCode } from "lucide-vue-next";

const searchQuery = ref("");

const emit = defineEmits(["update:limit", "search", "openCheckoutModal"]);

watch(searchQuery, (newVal) => {
    emit("search", newVal);
});

const props = defineProps<{
    visitors: any;
    limit: string;
    totalVisitors: number;
}>();

function trimVisitorType(visitorType: string) {
    if (visitorType === "visitor") {
        return "V";
    } else if (visitorType === "contractor") {
        return "C";
    } else {
        return "D";
    }
}

function getAckRowClass(ack: any) {
    // both staff + security acknowledged → green
    if (ack.acknowledged_by && ack.acknowledged_by_security) {
        return "bg-green-100 hover:bg-green-200";
    }
    // staff acknowledged but security not yet → red
    if (ack.acknowledged_by && !ack.acknowledged_by_security) {
        return "bg-red-400 hover:bg-red-300";
    }
    // default
    return "bg-white hover:bg-gray-100";
}

function globalIndex(ackIndex: number, index: number) {
    const offset = props.visitors
        .slice(0, ackIndex)
        .reduce((sum, a) => sum + a.visitors.length, 0);

    // Ascending value
    const current = offset + index + 1;

    // Convert to descending
    return props.totalVisitors - current + 1;
}

// Enhanced group detection and styling
const groupInfo = computed(() => {
    const ackCounts = {};
    const colors = [
        "border-l-blue-500 bg-blue-50",
        "border-l-purple-500 bg-purple-50",
        "border-l-orange-500 bg-orange-50",
        "border-l-pink-500 bg-pink-50",
        "border-l-indigo-500 bg-indigo-50",
        "border-l-teal-500 bg-teal-50",
        "border-l-yellow-500 bg-yellow-50",
        "border-l-emerald-500 bg-emerald-50",
    ];

    // Count visitors per acknowledgment number
    props.visitors.forEach((ack) => {
        ackCounts[ack.ack_number] =
            (ackCounts[ack.ack_number] || 0) + ack.visitors.length;
    });

    // Assign colors to groups with multiple visitors
    const groupColors = {};
    let colorIndex = 0;
    Object.keys(ackCounts).forEach((ackNumber) => {
        if (ackCounts[ackNumber] > 1) {
            groupColors[ackNumber] = colors[colorIndex % colors.length];
            colorIndex++;
        }
    });

    return { ackCounts, groupColors };
});

// function getGroupHighlighting(ackNumber: string) {
//     const { ackCounts, groupColors } = groupInfo.value;

//     if (ackCounts[ackNumber] > 1) {
//         return groupColors[ackNumber] || "border-l-gray-500 bg-gray-50";
//     }
//     return "";
// }

function isGroupMember(ackNumber: string) {
    return groupInfo.value.ackCounts[ackNumber] > 1;
}

function getGroupSize(ackNumber: string) {
    return groupInfo.value.ackCounts[ackNumber] || 1;
}

// ✅ compute completed vs pending counts
const completedCount = computed(() =>
    props.visitors.reduce(
        (sum, ack) =>
            sum +
            ack.visitors.filter((v) => ack.acknowledged_by_security !== null)
                .length,
        0
    )
);

const pendingCount = computed(() =>
    props.visitors.reduce(
        (sum, ack) =>
            sum +
            ack.visitors.filter((v) => ack.acknowledged_by_security === null)
                .length,
        0
    )
);

const totalCount = computed(() => completedCount.value + pendingCount.value);

// ✅ percentage widths
const completedPct = computed(() =>
    totalCount.value ? (completedCount.value / totalCount.value) * 100 : 0
);
const pendingPct = computed(() =>
    totalCount.value ? (pendingCount.value / totalCount.value) * 100 : 0
);
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
            <div class="flex space-x-4 justify-between mb-4">
                <div class="flex items-center gap-4">
                    <div class="flex flex-row space-x-2">
                        <input
                            v-model="searchQuery"
                            class="w-400 bg-gray-300 text-black placeholder:text-black border-none rounded-lg text-sm"
                            placeholder="Search..."
                        />
                    </div>
                </div>
                <div class="flex flex-row gap-6 max-w-lg w-full">
                    <div class="max-w-md w-full">
                        <div
                            class="flex items-center justify-between mb-1 text-sm"
                        >
                            <span class="font-semibold text-green-600">
                                Completed: {{ completedCount }}
                                <!-- ({{
                                    completedPct.toFixed(1)
                                }}%) -->
                            </span>
                            <span class="font-semibold text-red-600">
                                Pending: {{ pendingCount }}
                                <!-- ({{
                                    pendingPct.toFixed(1)
                                }}%) -->
                            </span>
                            <!-- <span class="font-semibold"
                                >Total: {{ totalCount }}</span
                            > -->
                        </div>

                        <!-- stacked progress bar -->
                        <div
                            class="w-full h-4 rounded bg-gray-200 overflow-hidden flex"
                        >
                            <div
                                class="bg-green-500 h-full"
                                :style="{ width: completedPct + '%' }"
                            ></div>
                            <div
                                class="bg-red-500 h-full"
                                :style="{ width: pendingPct + '%' }"
                            ></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div>
                            <Select
                                :model-value="limit"
                                @update:model-value="
                                    emit('update:limit', $event)
                                "
                            >
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
                    </div>
                    <div>
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger>
                                    <button @click="emit('openCheckoutModal')">
                                        <ScanQrCode class="w-9 h-9" />
                                    </button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>Checkout By Scanner</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>
            </div>

            <div
                class="flex-1 overflow-y-auto max-h-[550px] border border-gray-300"
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
                                class="font-black text-black text-center bg-gray-100 p-3 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                SV-ID
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Pass #
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Visitor Name
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Date
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Company
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Host Acknowledgement
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Time Scan
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Security Exit Clearance
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Purposes
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                VCode
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="border border-gray-300 divide-x divide-gray-300"
                    >
                        <template
                            v-for="(ack, ackIndex) in visitors"
                            :key="ack.id"
                        >
                            <tr
                                v-for="(v, index) in ack.visitors"
                                :key="v.id"
                                :class="[
                                    getAckRowClass(ack),
                                ]"
                            >
                                <td
                                    class="border border-gray-300 text-center p-2"
                                >
                                    {{ globalIndex(ackIndex, index) }}
                                </td>
                                <td
                                    class="border border-gray-300 text-center p-2 relative"
                                >
                                    <div
                                        class="flex items-center justify-center gap-1"
                                    >
                                        <span>{{ ack.ack_number }}</span>
                                        <span
                                            v-if="isGroupMember(ack.ack_number)"
                                            class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-blue-600 rounded-full"
                                        >
                                            {{ getGroupSize(ack.ack_number) }}
                                        </span>
                                    </div>
                                </td>
                                <td
                                    class="border border-gray-300 text-center p-2"
                                >
                                    {{ v.gate_pass.pass_number }}
                                </td>
                                <td
                                    class="border border-gray-300 text-center p-2"
                                >
                                    {{ v.visitor_name.toUpperCase() }}
                                </td>
                                <td
                                    class="border border-gray-300 text-center p-2"
                                >
                                    {{ v.date }}
                                </td>
                                <td
                                    class="border border-gray-300 text-center p-2"
                                >
                                    {{ v.visitor_company.toUpperCase() }}
                                </td>
                                <td
                                    class="border border-gray-300 text-center p-2"
                                >
                                    {{ ack.acknowledged_by.toUpperCase() }}
                                </td>
                                <td
                                    class="border border-gray-300 text-center p-2"
                                >
                                    {{
                                        new Date(
                                            ack.acknowledged_at
                                        ).toLocaleTimeString("en-GB", {
                                            hour: "2-digit",
                                            minute: "2-digit",
                                        })
                                    }}
                                </td>
                                <td
                                    class="border border-gray-300 text-center p-2"
                                >
                                    {{
                                        ack.acknowledged_at_security
                                            ? new Date(
                                                  ack.acknowledged_at_security.replace(
                                                      " ",
                                                      "T"
                                                  )
                                              ).toLocaleTimeString("en-GB", {
                                                  hour: "2-digit",
                                                  minute: "2-digit",
                                              })
                                            : "N / A"
                                    }}
                                </td>
                                <td
                                    class="border border-gray-300 text-center p-2"
                                >
                                    {{ v.purpose }}
                                </td>
                                <td
                                    class="border border-gray-300 text-center p-2"
                                >
                                    {{ trimVisitorType(v.visitor_type) }}
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
