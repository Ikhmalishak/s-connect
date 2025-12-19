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
                        class="bg-white p-6 rounded-lg shadow-[0_4px_20px_rgba(255,255,255,0.6)] w-[95%] max-w-5xl max-h-[90vh] overflow-y-auto"
                    >
                        <div
                            class="flex justify-between items-center mb-6 pb-4 border-b"
                        >
                            <div>
                                <h2 class="text-2xl font-bold text-red-700">
                                    Loading Photos
                                </h2>
                                <p class="text-sm text-gray-600 mt-1">
                                    Container: {{ details?.container?.transport_number }}
                                </p>
                            </div>
                            <button
                                @click="emit('close')"
                                class="text-gray-500 hover:text-gray-700 text-2xl font-bold w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors"
                            >
                                ×
                            </button>
                        </div>

                        <div v-if="loading" class="flex justify-center py-12">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        </div>

                        <div v-else-if="details" class="space-y-6">
                            <!-- Container Info -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Container Information</h3>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <span class="font-medium text-gray-700">Transport Number:</span>
                                        <p class="text-gray-900">{{ details.container.transport_number }}</p>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">SKU Number:</span>
                                        <p class="text-gray-900">{{ details.container.sku_number }}</p>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">Forwarder:</span>
                                        <p class="text-gray-900">{{ details.container.forwarder }}</p>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">Transport Type:</span>
                                        <p class="text-gray-900">{{ details.container.transport_type }}</p>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">Status:</span>
                                        <p class="text-gray-900">{{ details.container.status }}</p>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">Stage:</span>
                                        <p class="text-gray-900">{{ details.container.stage.replace(/_/g, ' ') }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Loading Photos -->
                            <div v-if="details.loading_photos && details.loading_photos.length > 0" class="bg-white border border-gray-200 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Loading Report Photos</h3>

                                <!-- Photo Count Summary -->
                                <div class="mb-4 p-3 bg-blue-50 rounded-lg">
                                    <p class="text-sm text-gray-700">
                                        <span class="font-medium">Total Photos:</span> {{ details.loading_photos.length }}
                                    </p>
                                </div>

                                <!-- Photo Grid (4x3 layout) -->
                                <div class="grid grid-cols-4 gap-4">
                                    <div
                                        v-for="photo in details.loading_photos"
                                        :key="photo.id"
                                        class="border border-gray-200 rounded-lg p-3 bg-gray-50 hover:shadow-md transition-shadow"
                                    >
                                        <div class="mb-2">
                                            <h4 class="font-medium text-gray-900 text-xs mb-1 capitalize text-center">
                                                {{ photo.label.replace(/_/g, ' ') }}
                                            </h4>
                                            <img
                                                :src="'/storage/' + photo.photo_path"
                                                :alt="photo.label"
                                                class="w-full h-24 object-cover border border-gray-300 rounded cursor-pointer hover:opacity-80 transition-opacity"
                                                @click="openImageModal('/storage/' + photo.photo_path, photo.label)"
                                            />
                                        </div>
                                        <div class="text-xs text-gray-600 space-y-1 text-center">
                                            <p>{{ formatDate(photo.created_at) }}</p>
                                            <p v-if="photo.taken_by">User #{{ photo.taken_by }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center py-8 bg-gray-50 rounded-lg">
                                <p class="text-gray-500">No loading photos available for this container.</p>
                            </div>

                            <!-- Approval Information -->
                            <div class="bg-white border border-gray-200 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Approval Information</h3>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <span class="font-medium text-gray-700">Department:</span>
                                        <p class="text-gray-900 capitalize">{{ details.approval.department }}</p>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">Status:</span>
                                        <p class="text-gray-900 capitalize">{{ details.approval.approval_status }}</p>
                                    </div>
                                    <div v-if="details.approval.approved_by_name">
                                        <span class="font-medium text-gray-700">Approved By:</span>
                                        <p class="text-gray-900">{{ details.approval.approved_by_name }}</p>
                                    </div>
                                    <div v-if="details.approval.approved_at">
                                        <span class="font-medium text-gray-700">Approved At:</span>
                                        <p class="text-gray-900">{{ formatDate(details.approval.approved_at) }}</p>
                                    </div>
                                    <div v-if="details.approval.remarks">
                                        <span class="font-medium text-gray-700">Remarks:</span>
                                        <p class="text-gray-900">{{ details.approval.remarks }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-8">
                            <p class="text-gray-500">No loading photos available.</p>
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

    <!-- Image Modal -->
    <Dialog :open="imageModalOpen" @open-change="imageModalOpen = false">
        <DialogContent class="max-w-5xl">
            <DialogHeader>
                <DialogTitle>{{ selectedPhotoLabel ? selectedPhotoLabel.replace(/_/g, ' ').toUpperCase() : 'Loading Photo' }}</DialogTitle>
            </DialogHeader>
            <div class="flex justify-center">
                <img
                    v-if="selectedImage"
                    :src="selectedImage"
                    :alt="selectedPhotoLabel"
                    class="max-w-full max-h-[70vh] object-contain"
                />
            </div>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import axios from 'axios'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'

const props = defineProps<{
    show: boolean
    approvalId: number | null
}>()

const emit = defineEmits(['close'])

const loading = ref(false)
const details = ref(null)
const imageModalOpen = ref(false)
const selectedImage = ref('')
const selectedPhotoLabel = ref('')

const fetchDetails = async () => {
    if (!props.approvalId) return

    loading.value = true
    try {
        const response = await axios.get(`/container-approvals/${props.approvalId}/details`)
        details.value = response.data
    } catch (error) {
        console.error('Error fetching approval details:', error)
    } finally {
        loading.value = false
    }
}

const openImageModal = (imageSrc: string, label: string) => {
    selectedImage.value = imageSrc
    selectedPhotoLabel.value = label
    imageModalOpen.value = true
}

const formatDate = (dateString: string | null): string => {
    if (!dateString) return 'N/A'
    return new Date(dateString).toLocaleString()
}

// Watch for modal open and fetch details
watch(() => props.show, (newVal) => {
    if (newVal && props.approvalId) {
        fetchDetails()
    } else {
        details.value = null
    }
})
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
