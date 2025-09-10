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
import { ref, watch } from "vue";
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
                            v-model="searchQuery"
                            class="w-400 bg-gray-300 text-black placeholder:text-black border-none rounded-lg text-sm"
                            placeholder="Search..."
                        />
                    </div>
                </div>
                <div class="flex flex-row gap-2">
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
                                        <SelectItem value="25">25</SelectItem>
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
                                Acknowledge By
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Acknowledge At
                            </th>
                            <!-- <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Acknowledge By Security
                            </th> -->
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Acknowledge At Security
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
                                :class="getAckRowClass(ack)"
                            >
                                <td
                                    class="border border-gray-300 text-center p-2"
                                >
                                    {{ ack.ack_number }}
                                </td>
                                <td
                                    class="border border-gray-300 text-center p-2"
                                >
                                    {{ v.gate_pass.pass_number }}
                                </td>
                                <td
                                    class="border border-gray-300 text-center p-2"
                                >
                                    {{ v.visitor_name }}
                                </td>
                                <td
                                    class="border border-gray-300 text-center p-2"
                                >
                                    {{ v.date }}
                                </td>
                                <td
                                    class="border border-gray-300 text-center p-2"
                                >
                                    {{ v.visitor_company }}
                                </td>
                                <td
                                    class="border border-gray-300 text-center p-2"
                                >
                                    {{ ack.acknowledged_by }}
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
                                <!-- <td
                                    class="border border-gray-300 text-center p-2"
                                >
                                    {{ ack.acknowledged_by_security ?? "N/A" }}
                                </td> -->
                                <td
                                    class="border border-gray-300 text-center p-2"
                                >
                                    {{
                                        ack.acknowledged_at
                                            ? new Date(
                                                  ack.acknowledged_at.replace(
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
