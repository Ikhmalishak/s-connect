<script setup lang="ts">
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/zod";
import * as z from "zod";
import { Button } from "@/components/ui/button";
import {
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from "@/components/ui/form";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Input } from "@/components/ui/input";

const timeRegex = /^([01]\d|2[0-3]):(00|30)$/;
const timeSlots = [];
const startHour = 8; // 8:00 AM
const endHour = 18; // 6:00 PM

const formSchema = toTypedSchema(
    z
        .object({
            room_id: z.string().transform(Number),
            user_name: z.string().min(2).max(50),
            user_id: z.string().min(3).max(20),
            start_time: z
                .string()
                .regex(timeRegex, "Time must be 00 or 30 only"),
            end_time: z.string().regex(timeRegex, "Time must be 00 or 30 only"),
            date: z
                .string()
                .refine((val) => !isNaN(Date.parse(val)), "Invalid date"),
            purpose: z.string(),
        })
        .refine((data) => data.start_time < data.end_time, {
            message: "End time must be later than start time",
            path: ["end_time"],
        })
);

const form = useForm({
    validationSchema: formSchema,
});

const onSubmit = form.handleSubmit((values) => {
    emit("save", values); // send to parent
});

const props = defineProps<{
    show: boolean;
    rooms: any;
    site: string;
    statusMessage?: string;
    statusType?: string;
}>();

const emit = defineEmits(["close", "save"]);

function getSite(siteId: string | number) {
    const siteMap: Record<string, string> = {
        "1": "Site 1",
        "2": "Site 2",
        "3": "Site 3",
        "4": "Site 4",
    };

    return siteMap[String(siteId)] ?? "Unknown Site";
}

for (let hour = startHour; hour <= endHour; hour++) {
    timeSlots.push(`${hour.toString().padStart(2, "0")}:00`);
    timeSlots.push(`${hour.toString().padStart(2, "0")}:30`);
}
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
                        class="bg-white p-6 rounded-lg shadow-[0_4px_20px_rgba(255,255,255,0.6)] w-[80%] max-w-2xl max-h-[80vh] overflow-y-auto"
                    >
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold text-red-700">
                                Reserve Room for {{ getSite(site) }}
                            </h2>

                            <button
                                @click="emit('close')"
                                class="text-gray-500 hover:text-gray-700 text-xl font-bold"
                            >
                                ×
                            </button>
                        </div>

                        <form @submit="onSubmit">
                            <FormField
                                v-slot="{ componentField }"
                                name="user_name"
                            >
                                <!--User Name-->
                                <FormItem>
                                    <FormLabel>Name</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            placeholder="Muhammad Ikhmal Bin Ishak"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-slot="{ componentField }"
                                name="user_id"
                            >
                                <!--User Name-->
                                <FormItem>
                                    <FormLabel>ID Number</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            placeholder="509260"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-slot="{ componentField }"
                                name="room_id"
                            >
                                <FormItem>
                                    <FormLabel>Room</FormLabel>

                                    <Select v-bind="componentField">
                                        <FormControl>
                                            <SelectTrigger>
                                                <SelectValue
                                                    placeholder="Select a meeting room"
                                                />
                                            </SelectTrigger>
                                        </FormControl>
                                        <SelectContent class="z-[10001]">
                                            <SelectGroup>
                                                <SelectItem
                                                    v-for="room in rooms"
                                                    :key="room.id"
                                                    :value="String(room.id)"
                                                >
                                                    {{ room.name }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField v-slot="{ componentField }" name="date">
                                <FormItem class="flex flex-col">
                                    <FormLabel>Date</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="date"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-slot="{ componentField }"
                                name="start_time"
                            >
                                <FormItem>
                                    <FormLabel>Start Time</FormLabel>
                                    <Select v-bind="componentField">
                                        <FormControl>
                                            <SelectTrigger>
                                                <SelectValue
                                                    placeholder="Select time"
                                                />
                                            </SelectTrigger>
                                        </FormControl>
                                        <SelectContent class="z-[10001]">
                                            <SelectGroup>
                                                <SelectItem
                                                    v-for="t in timeSlots"
                                                    :key="t"
                                                    :value="t"
                                                >
                                                    {{ t }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-slot="{ componentField }"
                                name="end_time"
                            >
                                <FormItem>
                                    <FormLabel>End Time</FormLabel>
                                    <Select v-bind="componentField">
                                        <FormControl>
                                            <SelectTrigger>
                                                <SelectValue
                                                    placeholder="Select time"
                                                />
                                            </SelectTrigger>
                                        </FormControl>
                                        <SelectContent class="z-[10001]">
                                            <SelectGroup>
                                                <SelectItem
                                                    v-for="t in timeSlots"
                                                    :key="t"
                                                    :value="t"
                                                >
                                                    {{ t }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-slot="{ componentField }"
                                name="purpose"
                            >
                                <!--User Name-->
                                <FormItem>
                                    <FormLabel>Purpose</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            placeholder="Interview"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>
                            <div class="flex flex-row justify-between items-center">
                                <Button class="mt-4" type="submit">
                                    Submit
                                </Button>
                                <p
                                    v-if="statusMessage"
                                    class="text-sm mt-2"
                                    :class="
                                        statusType === 'success'
                                            ? 'text-green-600'
                                            : 'text-red-600'
                                    "
                                >
                                    {{ statusMessage }}
                                </p>
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
