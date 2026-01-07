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
                        class="bg-white p-6 rounded-lg shadow-[0_4px_20px_rgba(255,255,255,0.6)] w-[90%] max-w-2xl max-h-[90vh] overflow-y-auto"
                    >
                        <div
                            class="flex justify-between items-center mb-6 pb-4 border-b"
                        >
                            <div>
                                <h2 class="text-2xl font-bold text-blue-700">
                                    Container Details
                                </h2>
                                <p class="text-sm text-gray-600 mt-1">
                                    Transport Number:
                                    {{ container?.transport_number }}
                                </p>
                            </div>
                            <button
                                @click="emit('close')"
                                class="text-gray-500 hover:text-gray-700 text-2xl font-bold w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors"
                            >
                                ×
                            </button>
                        </div>

                        <div v-if="container" class="space-y-4">
                            <!-- All Information in Single Column -->
                            <div
                                class="bg-white border border-gray-300 rounded-lg overflow-hidden"
                            >
                                <div class="divide-y divide-gray-300">
                                    <!-- Transport Type -->
                                    <div class="flex justify-between p-4">
                                        <span class="text-gray-700 text-sm"
                                            >Transport Type:</span
                                        >
                                        <span
                                            class="text-gray-900 text-sm font-semibold"
                                            >{{
                                                container.transport_type
                                            }}</span
                                        >
                                    </div>

                                    <!-- Transport Number -->
                                    <div class="flex justify-between p-4">
                                        <span class="text-gray-700 text-sm"
                                            >Transport Number:</span
                                        >
                                        <span
                                            class="text-gray-900 text-sm font-semibold"
                                            >{{
                                                container.transport_number
                                            }}</span
                                        >
                                    </div>

                                    <!-- Status -->
                                    <div
                                        class="flex justify-between items-center p-4"
                                    >
                                        <span class="text-gray-700 text-sm"
                                            >Status:</span
                                        >
                                        <span
                                            class="text-sm font-semibold"
                                            :class="{
                                                'text-blue-700':
                                                    container.status ===
                                                    'in_progress',
                                                'text-red-700':
                                                    container.status ===
                                                    'failed',
                                                'text-green-700':
                                                    container.status ===
                                                    'completed',
                                            }"
                                        >
                                            {{
                                                container.status
                                                    .replace(/_/g, " ")
                                                    .toUpperCase()
                                            }}
                                        </span>
                                    </div>

                                    <!-- Stage -->
                                    <div class="flex justify-between p-4">
                                        <span class="text-gray-700 text-sm"
                                            >Stage:</span
                                        >
                                        <span
                                            class="text-gray-900 text-sm font-semibold"
                                            >{{
                                                container.stage
                                                    .replace(/_/g, " ")
                                                    .toUpperCase()
                                            }}</span
                                        >
                                    </div>

                                    <!-- Created Date -->
                                    <div class="flex justify-between p-4">
                                        <span class="text-gray-700 text-sm"
                                            >Created:</span
                                        >
                                        <span
                                            class="text-gray-900 text-sm font-semibold"
                                            >{{
                                                formatDate(container.created_at)
                                            }}</span
                                        >
                                    </div>

                                    <!-- Shipment Date -->
                                    <div
                                        v-if="container.shipment_date"
                                        class="flex justify-between p-4"
                                    >
                                        <span class="text-gray-700 text-sm"
                                            >Shipment Date:</span
                                        >
                                        <span
                                            class="text-gray-900 text-sm font-semibold"
                                            >{{
                                                formatDate(
                                                    container.shipment_date
                                                )
                                            }}</span
                                        >
                                    </div>

                                    <!-- Last Updated -->
                                    <div
                                        v-if="container.updated_at"
                                        class="flex justify-between p-4"
                                    >
                                        <span class="text-gray-700 text-sm"
                                            >Last Updated:</span
                                        >
                                        <span
                                            class="text-gray-900 text-sm font-semibold"
                                            >{{
                                                formatDate(container.updated_at)
                                            }}</span
                                        >
                                    </div>

                                    <!-- SKP Site -->
                                    <div
                                        v-if="container.skp_site"
                                        class="flex justify-between p-4"
                                    >
                                        <span class="text-gray-700 text-sm"
                                            >SKP Site:</span
                                        >
                                        <span
                                            class="text-gray-900 text-sm font-semibold"
                                            >{{ container.skp_site }}</span
                                        >
                                    </div>

                                    <!-- Country -->
                                    <div
                                        v-if="container.country"
                                        class="flex justify-between p-4"
                                    >
                                        <span class="text-gray-700 text-sm"
                                            >Country:</span
                                        >
                                        <span
                                            class="text-gray-900 text-sm font-semibold"
                                            >{{ container.country }}</span
                                        >
                                    </div>

                                    <!-- Forwarder -->
                                    <div
                                        v-if="container.forwarder"
                                        class="flex justify-between p-4"
                                    >
                                        <span class="text-gray-700 text-sm"
                                            >Forwarder:</span
                                        >
                                        <span
                                            class="text-gray-900 text-sm font-semibold"
                                            >{{ container.forwarder }}</span
                                        >
                                    </div>

                                    <!-- Hauler -->
                                    <div
                                        v-if="container.hauler"
                                        class="flex justify-between p-4"
                                    >
                                        <span class="text-gray-700 text-sm"
                                            >Hauler:</span
                                        >
                                        <span
                                            class="text-gray-900 text-sm font-semibold"
                                            >{{ container.hauler }}</span
                                        >
                                    </div>



                                    <!-- SKU Number -->
                                    <div
                                        v-if="container.sku_number"
                                        class="flex justify-between p-4"
                                    >
                                        <span class="text-gray-700 text-sm"
                                            >SKU Number:</span
                                        >
                                        <span
                                            class="text-gray-900 text-sm font-semibold"
                                            >{{ container.sku_number }}</span
                                        >
                                    </div>

                                    <!-- Container Type -->
                                    <div
                                        v-if="container.container_type"
                                        class="flex justify-between p-4"
                                    >
                                        <span class="text-gray-700 text-sm"
                                            >Container Type:</span
                                        >
                                        <span
                                            class="text-gray-900 text-sm font-semibold"
                                            >{{
                                                container.container_type
                                            }}</span
                                        >
                                    </div>

                                    <!-- Container Number -->
                                    <div
                                        v-if="container.container_number"
                                        class="flex justify-between p-4"
                                    >
                                        <span class="text-gray-700 text-sm"
                                            >Container Number:</span
                                        >
                                        <span
                                            class="text-gray-900 text-sm font-semibold"
                                            >{{
                                                container.container_number
                                            }}</span
                                        >
                                    </div>

                                    <!-- Linked Driver Information -->
                                    <div
                                        v-if="container.linked_driver"
                                        class="p-4 bg-blue-50 border-l-4 border-blue-400"
                                    >
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <span class="text-blue-800 text-sm font-semibold"
                                                    >👤 LINKED DRIVER INFORMATION</span
                                                >
                                                <div class="mt-2 space-y-1">
                                                    <p class="text-blue-700 text-sm">
                                                        <strong>Name:</strong> {{ container.linked_driver.driver_name || 'Not Available' }}
                                                    </p>
                                                    <p class="text-blue-700 text-sm">
                                                        <strong>ID:</strong> {{ container.linked_driver.driver_id || 'Not Available' }}
                                                    </p>
                                                    <p class="text-blue-700 text-sm">
                                                        <strong>Vehicle:</strong> {{ container.linked_driver.vehicle_number || 'Not Available' }}
                                                    </p>
                                                    <p class="text-blue-700 text-sm">
                                                        <strong>Visitor Type:</strong> {{ container.linked_driver.visitor_type || 'Not Available' }}
                                                    </p>
                                                    <p class="text-blue-700 text-sm">
                                                        <strong>Registration Date:</strong> {{ container.linked_driver.created_at ? formatDate(container.linked_driver.created_at) : 'Not Available' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Container Size -->
                                    <div
                                        v-if="container.container_size"
                                        class="flex justify-between p-4"
                                    >
                                        <span class="text-gray-700 text-sm"
                                            >Container Size:</span
                                        >
                                        <span
                                            class="text-gray-900 text-sm font-semibold"
                                            >{{
                                                container.container_size
                                            }}</span
                                        >
                                    </div>

                                    <!-- Model -->
                                    <div
                                        v-if="container.model"
                                        class="flex justify-between p-4"
                                    >
                                        <span class="text-gray-700 text-sm"
                                            >Model:</span
                                        >
                                        <span
                                            class="text-gray-900 text-sm font-semibold"
                                            >{{ container.model }}</span
                                        >
                                    </div>

                                    <!-- Work Order -->
                                    <div
                                        v-if="container.work_order"
                                        class="flex justify-between p-4"
                                    >
                                        <span class="text-gray-700 text-sm"
                                            >Work Order:</span
                                        >
                                        <span
                                            class="text-gray-900 text-sm font-semibold"
                                            >{{ container.work_order }}</span
                                        >
                                    </div>

                                    <!-- High Security -->
                                    <div
                                        class="flex justify-between items-center p-4"
                                    >
                                        <span class="text-gray-700 text-sm"
                                            >High Security:</span
                                        >
                                        <span
                                            class="text-sm font-semibold"
                                            :class="
                                                container.high_security_seal
                                                    ? 'text-red-700'
                                                    : 'text-gray-900'
                                            "
                                        >
                                            {{
                                                container.high_security_seal
                                                    ? "YES"
                                                    : "NO"
                                            }}
                                        </span>
                                    </div>

                                    <!-- Inspection Status -->
                                    <div
                                        v-if="container.inspection"
                                        class="flex justify-between items-center p-4"
                                    >
                                        <span class="text-gray-700 text-sm"
                                            >Inspection Status:</span
                                        >
                                        <span
                                            class="text-sm font-semibold"
                                            :class="{
                                                'text-yellow-700':
                                                    container.status ===
                                                    'pending',
                                                'text-green-700':
                                                    container.status ===
                                                    'passed',
                                                'text-red-700':
                                                    container.status ===
                                                    'failed',
                                            }"
                                        >
                                            {{ container.status.toUpperCase() }}
                                        </span>
                                    </div>

                                    <!-- Hold Status -->
                                    <div
                                        v-if="container.is_on_hold"
                                        class="p-4 bg-orange-50 border-l-4 border-orange-400"
                                    >
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <span class="text-orange-800 text-sm font-semibold"
                                                    >🚫 CONTAINER ON HOLD</span
                                                >
                                                <p class="text-orange-700 text-sm mt-1">
                                                    <strong>Reason:</strong> {{ container.hold_reason }}
                                                </p>
                                                <p v-if="container.hold_at" class="text-orange-600 text-xs mt-1">
                                                    Held on: {{ formatDate(container.hold_at) }}
                                                </p>
                                                <p v-if="container.hold_by_name" class="text-orange-600 text-xs">
                                                    Held by: {{ container.hold_by_name }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-8">
                            <p class="text-gray-500">
                                No container details available.
                            </p>
                        </div>

                        <!-- Footer -->
                        <div class="flex justify-end mt-6 pt-4 border-t">
                            <button
                                @click="emit('close')"
                                class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-md text-sm font-medium transition-colors"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
interface Container {
    id: number;
    transport_type: string;
    transport_number: string;
    skp_site?: string;
    container_type?: string;
    container_number?: string;
    shipment_date?: string;
    country?: string;
    forwarder?: string;
    hauler?: string;
    driver_name?: string;
    driver_id?: string;
    sku_number?: string;
    container_size?: string;
    model?: string;
    work_order?: string;
    high_security_seal?: boolean;
    inspection?: {
        id: number;
        status: string;
    } | null;
    photo?: any[];
    status: string;
    stage: string;
    created_at?: string;
    updated_at?: string;
    is_on_hold?: boolean;
    hold_reason?: string;
    hold_at?: string;
    hold_by_name?: string;
    linked_driver?: {
        driver_name?: string;
        driver_id?: string;
        vehicle_number?: string;
        visitor_type?: string;
        created_at?: string;
    };
}

const props = defineProps<{
    show: boolean;
    container: Container | null;
}>();

const emit = defineEmits(["close"]);

function formatDate(dateString: string | undefined): string {
    if (!dateString) return "N/A";
    return new Date(dateString).toLocaleString();
}
</script>

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
