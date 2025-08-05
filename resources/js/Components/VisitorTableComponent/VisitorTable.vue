<script setup lang="ts">
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
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
    EyeOff,
} from "lucide-vue-next";
import { ref, watch } from "vue";
import { Card } from "@/components/ui/card";

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
}>();

const emit = defineEmits([
    "update:limit",
    "search",
    "checkIn",
    "checkOut",
    "openGatePassModal",
    "openCheckoutModal",
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
                            placeholder="Search by name..."
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
                                    <SelectItem value="10">10</SelectItem>
                                    <SelectItem value="25">25</SelectItem>
                                    <SelectItem value="30">30</SelectItem>
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
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger>
                                    <Link href="/visitor/form">
                                        <button>
                                            <UserRoundPlus class="w-9 h-9" />
                                        </button>
                                    </Link>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>Add User</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto max-h-[420px]">
                <Table class="min-w-full">
                    <TableHeader>
                        <TableRow
                            class="border border-gray-300 font-black divide-x divide-gray-300 text-black"
                        >
                            <TableHead
                                class="font-black text-black text-center bg-gray-100"
                                >No</TableHead
                            >
                            <TableHead
                                class="font-black text-black text-center bg-gray-100"
                                >Pass Number</TableHead
                            >
                            <TableHead
                                class="font-black text-black text-center bg-gray-100"
                                >Visitor Name</TableHead
                            >
                            <TableHead
                                class="font-black text-black text-center bg-gray-100"
                                >Vehicle Number</TableHead
                            >
                            <TableHead
                                class="font-black text-black text-center bg-gray-100"
                                >Date</TableHead
                            >
                            <TableHead
                                class="font-black text-black text-center bg-gray-100"
                                >Time Register</TableHead
                            >
                            <TableHead
                                class="font-black text-black text-center bg-gray-100"
                                >Time In</TableHead
                            >
                            <TableHead
                                class="font-black text-black text-center bg-gray-100"
                                >Time Out</TableHead
                            >
                            <TableHead
                                class="font-black text-black text-center bg-gray-100"
                                >Company</TableHead
                            >
                            <TableHead
                                class="font-black text-black text-center bg-gray-100"
                                >Reasons</TableHead
                            >
                            <TableHead
                                class="font-black text-black text-center bg-gray-100"
                                >IC Number</TableHead
                            >
                            <TableHead
                                class="font-black text-black text-center bg-gray-100"
                                >Passport</TableHead
                            >
                            <TableHead
                                class="font-black text-black text-center bg-gray-100"
                                >Phone Number</TableHead
                            >
                            <TableHead
                                class="font-black text-black text-center bg-gray-100"
                                >Visitor Code</TableHead
                            >
                            <TableHead
                                class="font-black text-black text-center bg-gray-100"
                                >DU</TableHead
                            >
                        </TableRow>
                    </TableHeader>
                    <TableBody
                        class="border border-gray-300 divide-x divide-gray-300"
                    >
                        <TableRow
                            v-for="(visitor, index) in visitors"
                            :key="visitor.id"
                            :class="[
                                getRowClass(visitor),
                                'border border-gray-300 divide-x divide-gray-300',
                            ]"
                        >
                            <TableCell class="text-center">
                                {{ index + 1 }}
                            </TableCell>
                            <TableCell class="text-center">{{
                                visitor.gate_pass?.pass_number || "-"
                            }}</TableCell>
                            <TableCell class="text-center">{{
                                visitor.visitor_name
                            }}</TableCell>
                            <TableCell class="text-center">{{
                                visitor.vehicle_number
                            }}</TableCell>
                            <TableCell class="text-center">{{
                                visitor.date
                            }}</TableCell>
                            <TableCell class="text-center">{{
                                trimToHourMinute(visitor.time_register)
                            }}</TableCell>
                            <TableCell class="text-center">
                                <template v-if="visitor.time_in">
                                    {{ trimToHourMinute(visitor.time_in) }}
                                </template>
                                <template v-else>
                                    <Button
                                        @click="emit('checkIn', visitor.id)"
                                        class="h-6 px-2 text-xs leading-none"
                                    >
                                        Check In
                                    </Button>
                                </template>
                            </TableCell>
                            <TableCell class="text-center">
                                <template v-if="visitor.time_out">
                                    {{ trimToHourMinute(visitor.time_out) }}
                                </template>
                                <template v-else>
                                    <Button
                                        @click="emit('checkOut', visitor.id)"
                                        class="h-6 px-2 text-xs leading-none"
                                        :disabled="!visitor.time_in"
                                    >
                                        Check Out
                                    </Button>
                                </template>
                            </TableCell>
                            <TableCell class="text-center">{{
                                visitor.visitor_company
                            }}</TableCell>
                            <TableCell class="text-center">{{
                                visitor.purpose
                            }}</TableCell>
                            <TableCell
                                class="text-center"
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
                            </TableCell>

                            <TableCell
                                class="text-center"
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
                            </TableCell>

                            <TableCell
                                class="text-center"
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
                            </TableCell>
                            <TableCell class="text-center">{{
                                trimVisitorType(visitor.visitor_type)
                            }}</TableCell>
                            <TableCell class="text-center">
                                <button
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
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </Card>
    </div>
</template>
