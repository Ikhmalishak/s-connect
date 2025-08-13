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
    ScanQrCode,
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

interface VisitorForm {
    id: number;
    pass_number: string;
    visitor_name: string;
    visitor_type: string;
    vehicle_number: string;
    date: string;
    time_register: string;
    time_in: string;
    time_out: string;
    visitor_company: string;
    other_reasons: string;
    purpose: string;
    ic_number: string;
    passport: string;
    phone_number: string;
    gate_pass?: {
        pass_number: string;
    };
}

const props = defineProps<{
    visitors: VisitorForm[];
    limit: string;
    count: number;
    totalVisitorToday: number;
}>();

const emit = defineEmits([
    "update:limit",
    "search",
    "checkIn",
    "checkOut",
    "openGatePassModal",
    "openCheckoutModal",
    "openDetailsModal",
]);

const searchQuery = ref("");

// Track which visitors have unmasked values
const unmasked = ref<Record<number, boolean>>({});

watch(searchQuery, (newVal) => {
    emit("search", newVal);
});

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

function getRowClass(visitor: VisitorForm) {
    if (visitor.time_out) {
        return "bg-green-100 hover:bg-green-100 data-[state=hover]:bg-green-100";
    }
    if (visitor.time_in && !visitor.time_out) {
        return "text-white bg-red-400 hover:bg-red-300 data-[state=hover]:bg-red-300";
    }
    return "bg-white hover:bg-white data-[state=hover]:bg-white";
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
                            :model-value="limit"
                            @update:model-value="emit('update:limit', $event)"
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

                    <div>
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger>
                                    <button @click="emit('openGatePassModal')">
                                        <NotificationBadge :count="count">
                                            <IdCardLanyard class="w-9 h-9" />
                                        </NotificationBadge>
                                    </button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>Gate Pass Available</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>

                    <div>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <CustomTooltip text="Add User" position="top">
                                    <UserRoundPlus
                                        class="w-9 h-9 cursor-pointer text-black"
                                    />
                                </CustomTooltip>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-56">
                                <DropdownMenuLabel
                                    >Select Site</DropdownMenuLabel
                                >
                                <DropdownMenuSeparator />
                                <DropdownMenuRadioGroup>
                                    <DropdownMenuRadioItem value="s1">
                                        <Link
                                            href="/visitor/form/s1"
                                            class="block w-full"
                                            >Site 1</Link
                                        >
                                    </DropdownMenuRadioItem>

                                    <DropdownMenuRadioItem value="s2">
                                        <Link
                                            href="/visitor/form/s2"
                                            class="block w-full"
                                            >Site 2</Link
                                        >
                                    </DropdownMenuRadioItem>

                                    <DropdownMenuRadioItem value="s3">
                                        <Link
                                            href="/visitor/form/s3"
                                            class="block w-full"
                                            >Site 3</Link
                                        >
                                    </DropdownMenuRadioItem>

                                    <DropdownMenuRadioItem value="s4">
                                        <Link
                                            href="/visitor/form/s4"
                                            class="block w-full"
                                            >Site 4</Link
                                        >
                                    </DropdownMenuRadioItem>
                                </DropdownMenuRadioGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>
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
                            v-for="(visitor, index) in visitors"
                            :key="visitor.id"
                            class="text-sm"
                            :class="[
                                getRowClass(visitor),
                                'border border-gray-300 divide-x divide-gray-300',
                            ]"
                        >
                            <td class="text-center p-2">
                                {{ totalVisitorToday - index }}
                            </td>
                            <td class="text-center p-2">
                                {{ visitor.gate_pass?.pass_number || "-" }}
                            </td>
                            <td
                                class="text-center p-2 cursor-pointer hover:underline"
                                @click="emit('openDetailsModal', visitor.id)"
                            >
                                {{ visitor.visitor_name.toUpperCase() }}
                            </td>
                            <td class="text-center p-2">
                                {{ visitor.vehicle_number }}
                            </td>
                            <td class="text-center p-2">
                                {{
                                    new Date(visitor.date).toLocaleDateString(
                                        "en-GB"
                                    )
                                }}
                            </td>
                            <td class="text-center p-2">
                                {{ trimToHourMinute(visitor.time_register) }}
                            </td>
                            <td class="text-center p-2">
                                <template v-if="visitor.time_in">
                                    {{ trimToHourMinute(visitor.time_in) }}
                                </template>
                                <template v-else>
                                    <!-- <button
                                        @click="emit('checkIn', visitor.id)"
                                        class="h-6 px-2 text-xs leading-none bg-black text-white rounded"
                                    >
                                        Check In
                                    </button> -->
                                    -- : --
                                </template>
                            </td>
                            <td class="text-center p-2">
                                <template v-if="visitor.time_out">
                                    {{ trimToHourMinute(visitor.time_out) }}
                                </template>
                                <template v-else>
                                    <!-- <button
                                        @click="emit('checkOut', visitor.id)"
                                        class="h-6 px-2 text-xs leading-none bg-black text-white rounded"
                                        :disabled="!visitor.time_in"
                                    >
                                        Check Out
                                    </button> -->
                                    -- : --
                                </template>
                            </td>
                            <td class="text-center p-2">
                                {{ visitor.visitor_company.toUpperCase() }}
                            </td>
                            <td class="text-center p-2">
                                {{
                                    visitor.purpose === "Other"
                                        ? `Other - ${visitor.other_reasons}`
                                        : visitor.purpose
                                }}
                            </td>
                            <td
                                class="text-center p-2"
                                :class="{
                                    'text-black font-bold':
                                        unmasked[visitor.id],
                                }"
                            >
                                {{
                                    unmasked[visitor.id]
                                        ? visitor.ic_number
                                        : maskValue(visitor.ic_number)
                                }}
                            </td>
                            <td
                                class="text-center p-2"
                                :class="{
                                    'text-black font-bold':
                                        unmasked[visitor.id],
                                }"
                            >
                                {{
                                    unmasked[visitor.id]
                                        ? visitor.passport
                                        : maskValue(visitor.passport)
                                }}
                            </td>
                            <td
                                class="text-center p-2"
                                :class="{
                                    'text-black font-bold':
                                        unmasked[visitor.id],
                                }"
                            >
                                {{
                                    unmasked[visitor.id]
                                        ? visitor.phone_number
                                        : maskValue(visitor.phone_number)
                                }}
                            </td>
                            <td class="text-center p-2">
                                {{ trimVisitorType(visitor.visitor_type) }}
                            </td>
                            <td class="text-center p-2">
                                <button
                                    v-if="visitor.time_in"
                                    class="p-1 rounded cursor-not-allowed"
                                    title="Cannot unmask after check-in"
                                >
                                    <EyeOff class="w-5 h-5 text-black" />
                                </button>

                                <button
                                    v-else
                                    class="p-1 rounded hover:bg-gray-200"
                                    @mouseenter="setRowMask(visitor.id, true)"
                                    @mouseleave="setRowMask(visitor.id, false)"
                                >
                                    <component
                                        :is="
                                            unmasked[visitor.id]
                                                ? Eye
                                                : EyeClosed
                                        "
                                        class="w-5 h-5"
                                        :class="{
                                            'text-black': visitor.time_out,
                                            'text-white':
                                                visitor.time_in &&
                                                !visitor.time_out,
                                            'text-gray-600': !visitor.time_in,
                                        }"
                                    />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
