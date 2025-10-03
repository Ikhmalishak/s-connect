<script setup lang="ts">
import { ref, watch } from "vue";
import { Input } from "@/components/ui/input";
import type { DateRange } from "reka-ui";
import type { Ref } from "vue";
import { RangeCalendar } from "@/components/ui/range-calendar";
import {
    CalendarDate,
    today,
    DateFormatter,
    getLocalTimeZone,
} from "@internationalized/date";
import { CalendarIcon } from "lucide-vue-next";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import axios from "axios";

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits(["close", "updateRemarks"]);
const df = new DateFormatter("en-US", {
    dateStyle: "medium",
});

const value = ref({
    start: today(getLocalTimeZone()), // set today as start
    end: today(getLocalTimeZone()), // optionally set end as today too
});

const visitor_company = ref(null);
const visitor_type = ref<string[]>([]);
const isLoading = ref(false);

const submitForm = async () => {
    try {
        isLoading.value = true; // ✅ start loading

        const params = {
            dateRange: value.value,
            visitor_type: visitor_type.value.length
                ? visitor_type.value
                : "all",
            visitor_company: visitor_company.value || "all",
        };

        const response = await axios.post(
            "/admin/visitor/generate-report",
            params,
            {
                responseType: "blob",
            }
        );

        const blob = new Blob([response.data], {
            type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });
        const fileURL = window.URL.createObjectURL(blob);

        const fileLink = document.createElement("a");
        fileLink.href = fileURL;
        fileLink.setAttribute("download", "visitors-report.xlsx");
        document.body.appendChild(fileLink);
        fileLink.click();
        fileLink.remove();
        window.URL.revokeObjectURL(fileURL);
    } catch (error) {
        console.error("Export failed:", error);
    } finally {
        isLoading.value = false; // ✅ stop loading

        // reset filters
        value.value = {
            start: today(getLocalTimeZone()),
            end: today(getLocalTimeZone()),
        };
        visitor_type.value = [];
        visitor_company.value = null;
    }
};
</script>

<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div
                v-if="show"
                class="fixed inset-0 bg-black/50 backdrop-blur-md flex items-center justify-center z-[9999]"
                @click.self="emit('close')"
            >
                <Transition name="modal-scale" appear>
                    <div
                        v-if="show"
                        class="bg-white p-6 rounded-lg shadow-lg w-[80%] max-w-2xl max-h-[80vh] overflow-y-auto"
                    >
                        <!-- Header -->
                        <div
                            class="flex justify-between items-center mb-6 border-b pb-3"
                        >
                            <h2 class="text-xl font-bold text-red-700">
                                Export Report
                            </h2>
                            <button
                                @click="emit('close')"
                                class="text-gray-500 hover:text-gray-700 text-2xl leading-none"
                            >
                                ×
                            </button>
                        </div>
                        <!-- Form -->
                        <form
                            class="grid grid-cols-[150px_1fr] gap-y-5 gap-x-4 items-center"
                            @submit.prevent="submitForm"
                        >
                            <!-- Date -->
                            <label class="text-right font-medium text-gray-700">
                                Pick the date:
                            </label>
                            <Popover>
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        :class="
                                            cn(
                                                'w-[280px] justify-start text-left font-normal',
                                                !value &&
                                                    'text-muted-foreground'
                                            )
                                        "
                                    >
                                        <CalendarIcon class="mr-2 h-4 w-4" />
                                        <template v-if="value.start">
                                            <template v-if="value.end">
                                                {{
                                                    df.format(
                                                        value.start.toDate(
                                                            getLocalTimeZone()
                                                        )
                                                    )
                                                }}
                                                -
                                                {{
                                                    df.format(
                                                        value.end.toDate(
                                                            getLocalTimeZone()
                                                        )
                                                    )
                                                }}
                                            </template>
                                            <template v-else>
                                                {{
                                                    df.format(
                                                        value.start.toDate(
                                                            getLocalTimeZone()
                                                        )
                                                    )
                                                }}
                                            </template>
                                        </template>
                                        <template v-else>
                                            Pick a date
                                        </template>
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent class="w-auto p-0 z-[10000]">
                                    <RangeCalendar
                                        v-model="value"
                                        initial-focus
                                        :number-of-months="2"
                                        @update:start-value="
                                            (startDate) =>
                                                (value.start = startDate)
                                        "
                                    />
                                </PopoverContent>
                            </Popover>

                            <!-- Visitor Type -->
                            <label class="text-right font-medium text-gray-700">
                                Visitor Type:
                            </label>
                            <div
                                class="flex flex-col gap-2 w-[280px] border rounded-md p-2"
                            >
                                <label class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        value="visitor"
                                        v-model="visitor_type"
                                    />
                                    Visitor
                                </label>
                                <label class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        value="inbound-shipment/transfer"
                                        v-model="visitor_type"
                                    />
                                    Inbound-Shipment/Transfer
                                </label>
                                <label class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        value="outbound-shipment/transfer"
                                        v-model="visitor_type"
                                    />
                                    Outbound-Shipment/Transfer
                                </label>
                                <label class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        value="contractor"
                                        v-model="visitor_type"
                                    />
                                    Contractor
                                </label>
                            </div>

                            <!-- Visitor Type -->
                            <label class="text-right font-medium text-gray-700">
                                Visitor Company:
                            </label>
                            <Input
                                v-model="visitor_company"
                                placeholder="Enter the company name"
                                class="w-[280px]"
                            />
                            <div class="col-span-2 flex justify-end mt-4">
                                <div class="col-span-2 flex justify-end mt-4">
                                    <button
                                        type="submit"
                                        class="bg-red-700 text-white px-4 py-2 rounded disabled:opacity-50"
                                        :disabled="isLoading"
                                    >
                                        <span v-if="isLoading"
                                            >⏳ Exporting...</span
                                        >
                                        <span v-else>Export</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.modal-scale-enter-active,
.modal-scale-leave-active {
    transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.modal-scale-enter-from,
.modal-scale-leave-to {
    opacity: 0;
    transform: perspective(1000px) rotateX(-90deg) scale(0.3) translateY(-200px);
}
</style>
