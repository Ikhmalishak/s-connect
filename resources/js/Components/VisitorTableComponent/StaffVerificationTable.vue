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
import { Link } from "@inertiajs/vue3";
import NotificationBadge from "@/Components/NotificationBadge.vue";
import {
    IdCardLanyard,
    UserRoundPlus,
    Eye,
    EyeClosed,
} from "lucide-vue-next";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { ref, watch } from "vue";
import { Card } from "@/components/ui/card";
import CustomTooltip from "../CustomTooltip.vue";
import { EyeOff } from "lucide-vue-next";
import { ArrowRightFromLine } from "lucide-vue-next";
import ExportReportModal from "./ExportReportModal.vue";

const searchQuery = ref("");
const showExportReportModal = ref(false);

// Track which visitors have unmasked values
const unmasked = ref<Record<number, boolean>>({});


function maskValue(value: string) {
    if (!value) return "";
    const visiblePart = value.slice(-4);
    const maskedPart = "*".repeat(Math.max(0, value.length - 4));
    return `${maskedPart}${visiblePart}`;
}

function trimToHourMinute(timeString: string) {
    if (!timeString) return "-";
    return timeString.slice(0, 5);
}

function trimVisitorType(visitorType: string) {
    if (visitorType === "visitor") {
        return "V";
    } else if (visitorType === "contractor") {
        return "C";
    } else {
        return "D";
    }
}

function setRowMask(visitorId: number, state: boolean) {
    unmasked.value[visitorId] = state;
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

                <div class="flex items-center gap-4">
                    <div>
                        <Select
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
                                Vehicle #
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Date
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Time Reg
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Time In
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Time Out
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Company
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Purposes
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                IC #
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Passport #
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Phone #
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                VCode
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-2 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                DU
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="border border-gray-300 divide-x divide-gray-300"
                    >
                        <tr
                        >
                            <td class="text-center p-2">
                            </td>
                            <td class="text-center p-2">
                            </td>
                            <td
                                class="text-center p-2 cursor-pointer hover:underline"
                            >
                            </td>
                            <td class="text-center p-2">
                            </td>
                            <td class="text-center p-2">
                            </td>
                            <td class="text-center p-2">
                            </td>
                            <td class="text-center p-2">
                            </td>
                            <td class="text-center p-2">
                            </td>
                            <td class="text-center p-2">
                            </td>
                            <td class="text-center p-2">
                            </td>
                            <td
                            >
                            </td>
                            <td>
                            </td>
                            <td>
                            </td>
                            <td class="text-center p-2">
                            </td>
                            <td class="text-center p-2">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
    <ExportReportModal
        :show="showExportReportModal"
        @close="showExportReportModal = false"
    ></ExportReportModal>
</template>
