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

interface GatePass {
    id: number;
    pass_number: string;
    state: string;
}

const props = defineProps<{
    show: boolean;
    gatePasses: GatePass[];
}>();

const emit = defineEmits(['close']);
</script>

<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div
                v-if="show"
                class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[9999]"
                @click.self="emit('close')"
            >
                <Transition name="slide-fade" appear>
                    <div
                        v-if="show"
                        class="bg-white p-6 rounded-lg shadow-xl w-[80%] max-w-2xl max-h-[80vh] overflow-y-auto"
                    >
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold">
                                Gate Pass Details
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
                                    >A list of gate pass.</TableCaption
                                >
                                <TableHeader>
                                    <TableRow
                                        class="border border-gray-300 font-black divide-x divide-gray-300 text-black text-center bg-gray-100"
                                    >
                                        <TableHead
                                            class="w-[100px] text-center text-black font-black"
                                        >
                                            Pass Number
                                        </TableHead>
                                        <TableHead
                                            class="text-center text-black font-black"
                                        >
                                            Status</TableHead
                                        >
                                    </TableRow>
                                </TableHeader>
                                <TableBody
                                class="border border-gray-300 divide-x divide-gray-300"
                                >
                                    <TableRow
                                        v-for="
                                            pass in gatePasses"
                                        :key="pass.id"
                                        :class="[
                                            pass.state === 'free'
                                                ? 'bg-green-500'
                                                : 'bg-red-300',
                                            'border border-gray-300 divide-x divide-gray-300',
                                        ]"
                                    >
                                        <TableCell class="font-medium text-center">
                                            {{
                                                pass.pass_number
                                            }}
                                        </TableCell>
                                        <TableCell class="text-center">{{
                                            pass.state
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
.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.8s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateX(20px);
    opacity: 0;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}
</style>