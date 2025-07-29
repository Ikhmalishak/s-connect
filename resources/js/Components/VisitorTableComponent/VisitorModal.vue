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

interface Visitor {
    id: number;
    visitor_name: string;
    purpose: string;
    time_in: string;
    gate_pass: {
        pass_number: string;
    };
}

const props = defineProps<{
    show: boolean;
    visitors: Visitor[];
    currentTime: Date;
}>();

const emit = defineEmits(['close']);

const duration = (time_in) => {
    const now = new Date(props.currentTime);
    const today = now.toISOString().split("T")[0];
    const fullTimeIn = new Date(`${today}T${time_in}`);
    const diffMs = now.getTime() - fullTimeIn.getTime();
    const diffMins = Math.floor(diffMs / (1000 * 60));
    return diffMins;
};
</script>

<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div
                v-if="show"
                class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[9999]"
                @click.self="emit('close')"
            >
                <Transition name="modal-scale" appear>
                    <div
                        v-if="show"
                        class="bg-white p-6 rounded-lg shadow-xl w-[80%] max-w-2xl max-h-[80vh] overflow-y-auto"
                    >
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold">
                                Visitor Details
                            </h2>
                            <button
                                @click="emit('close')"
                                class="text-gray-500 hover:text-gray-700 text-xl font-bold"
                            >
                                ×
                            </button>
                        </div>

                        <div class="space-y-4">
                            <Table>
                                <TableCaption
                                    >A list of visitor inside.</TableCaption
                                >
                                <TableHeader>
                                    <TableRow
                                        class="border border-gray-300 font-black divide-x divide-gray-300 text-black text-center bg-gray-100"
                                    >
                                        <TableHead
                                            class="w-[100px] text-center text-black font-black"
                                        >
                                            Gate Pass ID
                                        </TableHead>
                                        <TableHead
                                            class="text-center text-black font-black"
                                        >
                                            Visitor Name</TableHead
                                        >
                                        <TableHead
                                            class="text-center text-black font-black"
                                        >
                                            Reason</TableHead
                                        >
                                        <TableHead
                                            class="text-center text-black font-black"
                                        >
                                            Duration</TableHead
                                        >
                                    </TableRow>
                                </TableHeader>
                                <TableBody
                                class="border border-gray-300 divide-x divide-gray-300"
                                >
                                    <TableRow
                                        v-for="(
                                            visitor, index
                                        ) in visitors"
                                        :key="visitor.id"
                                        :class="[
                                            index % 2 === 1
                                                ? 'bg-gray-100'
                                                : '',
                                            'border border-gray-300 divide-x divide-gray-300',
                                        ]"
                                    >
                                        <TableCell class="font-medium">
                                            {{
                                                visitor.gate_pass
                                                    .pass_number
                                            }}
                                        </TableCell>
                                        <TableCell>{{
                                            visitor.visitor_name
                                        }}</TableCell>
                                        <TableCell>{{
                                            visitor.purpose
                                        }}</TableCell>
                                        <TableCell
                                            >{{
                                                duration(visitor.time_in)
                                            }}
                                            minutes</TableCell
                                        >
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
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