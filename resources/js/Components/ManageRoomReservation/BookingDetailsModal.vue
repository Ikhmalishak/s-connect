<script setup lang="ts">
const props = defineProps<{
    show: boolean;
    booking: any;
    statusMessage?: string;
    statusType?: string;
}>();

const emit = defineEmits(["close", "cancel"]);
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
                        class="bg-white p-6 rounded-lg shadow w-[80%] max-w-2xl max-h-[80vh] overflow-y-auto"
                    >
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold text-red-700">
                                Room Reservation Details
                            </h2>

                            <button
                                @click="emit('close')"
                                class="text-gray-500 hover:text-gray-700 text-xl font-bold"
                            >
                                ×
                            </button>
                        </div>

                        <!-- Display info, not form -->
                        <div class="space-y-4 text-gray-800">
                            <div>
                                <p class="text-sm text-gray-500">Name</p>
                                <p class="font-medium">
                                    {{ booking.user_name }}
                                </p>
                            </div>

                            <div>
                                <p class="text-sm text-gray-500">User ID</p>
                                <p class="font-medium">{{ booking.user_id }}</p>
                            </div>

                            <div>
                                <p class="text-sm text-gray-500">Room</p>
                                <p class="font-medium">
                                    {{ booking.room.name }} (Capacity:
                                    {{ booking.room.capacity }})
                                </p>
                            </div>

                            <div>
                                <p class="text-sm text-gray-500">Date</p>
                                <p class="font-medium">{{ booking.date }}</p>
                            </div>

                            <div>
                                <p class="text-sm text-gray-500">Time</p>
                                <p class="font-medium">
                                    {{ booking.start_time.slice(11, 16) }} -
                                    {{ booking.end_time.slice(11, 16) }}
                                </p>
                            </div>

                            <div>
                                <p class="text-sm text-gray-500">Purpose</p>
                                <p class="font-medium">{{ booking.purpose }}</p>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="flex flex-row justify-between gap-3 mt-6">
                            <button
                                @click="emit('cancel', booking.id)"
                                class="px-4 py-2 rounded-md bg-red-600 text-white hover:bg-red-700"
                            >
                                Cancel Booking
                            </button>
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
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
