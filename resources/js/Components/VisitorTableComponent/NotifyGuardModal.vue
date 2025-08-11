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
}>();

const emit = defineEmits(["close"]);

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
                                There are still visitor Inside
                            </h2>

                            <button
                                @click="emit('close')"
                                class="text-gray-500 hover:text-gray-700 text-xl font-bold"
                            >
                                ×
                            </button>
                        </div>
                        <p>
                            Please ensure all the visitor checked out!!
                        </p>

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
                                            class="text-center text-black font-black"
                                        >
                                            No.</TableHead
                                        >
                                        <!-- <TableHead
                                            class="w-[100px] text-center text-black font-black"
                                        >
                                            GPass ID
                                        </TableHead> -->
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
                                    </TableRow>
                                </TableHeader>
                                <TableBody
                                    class="border border-gray-300 divide-x divide-gray-300"
                                >
                                    <TableRow
                                        v-for="(visitor, index) in visitors"
                                        class="text-center"
                                        :key="visitor.id"
                                        :class="[
                                            index % 2 === 1
                                                ? 'bg-gray-100'
                                                : '',
                                            'border border-gray-300 divide-x divide-gray-300',
                                        ]"
                                    >
                                        <TableCell class="font-medium">
                                            {{ index + 1 }}
                                        </TableCell>
                                        <!-- <TableCell class="font-medium">
                                            {{ visitor.gate_pass.pass_number }}
                                        </TableCell> -->
                                        <TableCell>{{
                                            visitor.visitor_name
                                        }}</TableCell>
                                        <TableCell>{{
                                            visitor.purpose
                                        }}</TableCell>
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
