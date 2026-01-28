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
                        class="bg-white p-6 rounded-lg shadow w-[95%] max-w-5xl max-h-[90vh] overflow-y-auto"
                    >
                        <div class="flex justify-between items-center mb-6 pb-4 border-b">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">
                                    Upload Loading Photos
                                </h2>
                                <p class="text-sm text-gray-600 mt-1">
                                    Upload photos of the container/truck loading process. Photos are optional but recommended for documentation.
                                </p>
                            </div>
                            <button
                                @click="emit('close')"
                                class="text-gray-500 hover:text-gray-700 text-2xl font-bold w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors"
                            >
                                ×
                            </button>
                        </div>

            <div class="space-y-6">
                <!-- Step Progress Bar -->
                <div class="flex justify-center">
                    <div class="flex items-center space-x-2 overflow-x-auto pb-2">
                        <div
                            v-for="(photoType, index) in photoTypes"
                            :key="photoType.key"
                            class="flex flex-col items-center"
                            :class="{ 'cursor-pointer': canNavigateToStep(index), 'cursor-not-allowed opacity-50': !canNavigateToStep(index) }"
                            @click="canNavigateToStep(index) && setCurrentStep(index)"
                        >
                            <div
                                :class="[
                                    'w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium border-2 transition-all duration-200',
                                    getStepClass(index)
                                ]"
                            >
                                {{ index + 1 }}
                            </div>
                            <div class="text-xs text-center mt-1 max-w-16 truncate">
                                {{ photoType.label }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Photo Upload Section -->
                <div class="max-w-md mx-auto">
                    <div class="space-y-4">
                        <div class="text-center">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Step {{ currentStep + 1 }}: {{ photoTypes[currentStep]?.label }}
                            </h3>
                            <p class="text-sm text-gray-600 mt-1">
                                Upload the {{ photoTypes[currentStep]?.label?.toLowerCase() }} photo
                            </p>
                        </div>

                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
                            <input
                                :ref="(el) => setFileInputRef(el, photoTypes[currentStep]?.key)"
                                type="file"
                                accept="image/*"
                                @change="handleFileSelect(photoTypes[currentStep]?.key, $event)"
                                class="hidden"
                            />
                            <div v-if="!photos[photoTypes[currentStep]?.key]" class="space-y-4">
                                <div class="flex gap-3 justify-center">
                                    <button
                                        type="button"
                                        @click="takePhoto(photoTypes[currentStep]?.key)"
                                        :disabled="uploadingPhoto"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Take Photo
                                    </button>
                                    <button
                                        type="button"
                                        @click="triggerFileInput(photoTypes[currentStep]?.key)"
                                        :disabled="uploadingPhoto"
                                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Upload from Gallery
                                    </button>
                                </div>
                                <p class="text-xs text-gray-600">{{ photoTypes[currentStep]?.label?.toLowerCase() }}</p>
                            </div>
                            <div v-else class="relative">
                                <img :src="photos[photoTypes[currentStep]?.key]?.preview" class="max-h-32 mx-auto rounded" />
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    class="absolute -top-2 -right-2 h-6 w-6"
                                    @click="removePhoto(photoTypes[currentStep]?.key)"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </div>

                            <!-- Individual Photo Upload Progress -->
                            <div v-if="uploadingPhoto && photos[photoTypes[currentStep]?.key]" class="mt-4 space-y-2">
                                <div class="flex items-center justify-between text-xs text-gray-600">
                                    <span>Uploading...</span>
                                    <span>{{ photoUploadProgress }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1">
                                    <div
                                        class="bg-blue-600 h-1 rounded-full transition-all duration-300"
                                        :style="{ width: photoUploadProgress + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="flex justify-between items-center">
                            <button
                                type="button"
                                @click="previousStep"
                                :disabled="currentStep === 0"
                                class="px-4 py-2 bg-gray-500 hover:bg-gray-600 disabled:bg-gray-300 text-white text-sm font-medium rounded-md transition-colors"
                            >
                                Previous
                            </button>
                            <span class="text-sm text-gray-600">
                                {{ currentStep + 1 }} of {{ photoTypes.length }}
                            </span>
                            <button
                                type="button"
                                @click="nextStep"
                                :disabled="!canGoToNextStep"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Progress Indicator -->
                <div v-if="isUploading" class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span>{{ currentUploadingLabel || 'Uploading photos...' }}</span>
                        <span v-if="photosToUpload.length > 0">{{ currentUploadingIndex + 1 }}/{{ photosToUpload.length }} ({{ uploadProgress }}%)</span>
                        <span v-else>{{ uploadProgress }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div
                            class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                            :style="{ width: uploadProgress + '%' }"
                        ></div>
                    </div>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-md">
                    <p class="text-red-800 text-sm">{{ error }}</p>
                </div>

                <!-- Success Message -->
                <div v-if="success" class="p-4 bg-green-50 border border-green-200 rounded-md">
                    <p class="text-green-800 text-sm">{{ success }}</p>
                </div>
            </div>

                        <!-- Submit Section -->
                        <div class="flex flex-row justify-between items-center pt-6 mt-6 border-t">
                            <div class="text-sm text-gray-600">
                                Complete all {{ photoTypes.length }} photos to create the record and submit for approval.
                            </div>
                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    @click="emit('close')"
                                    :disabled="isUploading"
                                    class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 h-10 px-6 py-2"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>

    <!-- Camera Capture Modal -->
    <CameraCaptureModal
        :show="showCameraModal"
        @close="closeCameraModal"
        @capture="handleCameraCapture"
    />
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { X } from 'lucide-vue-next'
import CameraCaptureModal from '@/Components/ManageContainer/CameraCaptureModal.vue'
import { Button } from '@/components/ui/button'

interface Props {
    show: boolean
    id: number
}

const props = defineProps<Props>()
const emit = defineEmits(['close'])

const photos = ref({
    pallet_condition_photo: null,
    pallet_label_photo: null,
    gps_photo_before_installation: null,
    container_truck_photo: null,
    empty_container_photo: null,
    inside_gps_photo: null,
    half_loaded_photo: null,
    one_side_door_closed_with_container_number_photo: null,
    complete_loaded_photo: null,
    outside_gps_photo: null,
    fork_seal_photo:null,
    security_seal_photo: null,
    container_full_seal_photo: null,
})

const photoTypes = ref([])

const isUploading = ref(false)
const uploadProgress = ref(0)
const currentUploadingIndex = ref(-1)
const currentUploadingLabel = ref('')
const error = ref('')
const success = ref('')
const showCameraModal = ref(false)
const currentPhotoType = ref('')
const currentStep = ref(0)
const uploadingPhoto = ref(false)
const photoUploadProgress = ref(0)

// File input refs
const fileInputRefs = ref<Record<string, HTMLInputElement>>({})

const photosToUpload = computed(() => {
    return photoTypes.value.filter(type => photos.value[type.key] !== null)
})

const canGoToNextStep = computed(() => {
    // Can go to next step only if current step is completed and not at the last step
    if (currentStep.value >= photoTypes.value.length - 1) {
        return false
    }
    const currentPhotoType = photoTypes.value[currentStep.value]
    return photos.value[currentPhotoType.key] !== null
})

// Track photos uploaded in current modal session
const photosUploadedInSession = ref(0)

const canSubmit = computed(() => {
    // Allow creating record even with no photos
    return !isUploading.value
})

const allPhotosComplete = computed(() => {
    return photoTypes.value.every(type => photos.value[type.key] !== null)
})

async function handleFileSelect(type: string, event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0]
    if (file) {
        // Validate file type
        if (!file.type.startsWith('image/')) {
            error.value = 'Please select a valid image file.'
            return
        }

        // Validate file size (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            error.value = 'File size must be less than 5MB.'
            return
        }

        // Create preview URL
        const preview = URL.createObjectURL(file)

        photos.value[type] = {
            file,
            preview
        }

        error.value = ''

        // Track that user uploaded a photo in this session
        photosUploadedInSession.value++

        // Upload photo immediately
        await uploadSinglePhoto(type, file)
    }
}

function removePhoto(type: string) {
    if (photos.value[type]?.preview) {
        URL.revokeObjectURL(photos.value[type].preview)
    }
    photos.value[type] = null
}

function takePhoto(type: string) {
    currentPhotoType.value = type
    showCameraModal.value = true
}

async function handleCameraCapture(imageData: string) {
    if (currentPhotoType.value) {
        // Convert base64 to File object
        const byteString = atob(imageData.split(',')[1])
        const mimeString = imageData.split(',')[0].split(':')[1].split(';')[0]
        const ab = new ArrayBuffer(byteString.length)
        const ia = new Uint8Array(ab)
        for (let i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i)
        }
        const blob = new Blob([ab], { type: mimeString })
        const file = new File([blob], `${currentPhotoType.value}_capture.jpg`, { type: mimeString })
        const preview = URL.createObjectURL(file)

        photos.value[currentPhotoType.value] = {
            file,
            preview
        }

        // Track that user uploaded a photo in this session
        photosUploadedInSession.value++

        // Upload the camera photo immediately
        await uploadSinglePhoto(currentPhotoType.value, file)
    }
    showCameraModal.value = false
    currentPhotoType.value = ''
}

function closeCameraModal() {
    showCameraModal.value = false
    currentPhotoType.value = ''
}

async function uploadSinglePhoto(type: string, file: File) {
    uploadingPhoto.value = true
    photoUploadProgress.value = 0

    try {
        const formData = new FormData()
        formData.append('shipment_transport_id', props.id.toString())
        formData.append(type, file)

        await axios.post('/containers/create-photo', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total) {
                    photoUploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
                }
            }
        })

        // Photo uploaded successfully - mark step as completed
        photoUploadProgress.value = 100

        // Small delay to show 100% before hiding
        setTimeout(() => {
            uploadingPhoto.value = false
            photoUploadProgress.value = 0
        }, 500)

    } catch (err) {
        uploadingPhoto.value = false
        photoUploadProgress.value = 0
        error.value = `Failed to upload ${type.replace(/_/g, ' ')}: ${err.response?.data?.message || 'Please try again.'}`
        // Remove the photo from local state if upload failed
        if (photos.value[type]?.preview) {
            URL.revokeObjectURL(photos.value[type].preview)
        }
        photos.value[type] = null
        console.error('Upload error:', err)
    } finally {
        // Reset the file input to allow selecting the same or different files again
        if (fileInputRefs.value[type]) {
            fileInputRefs.value[type].value = ''
        }
    }
}

function setCurrentStep(step: number) {
    currentStep.value = step
}

function canNavigateToStep(stepIndex: number) {
    // Can navigate to current step or any completed step
    if (stepIndex <= currentStep.value) {
        return true
    }

    // Can navigate to next step only if current step is completed
    if (stepIndex === currentStep.value + 1) {
        const currentPhotoType = photoTypes.value[currentStep.value]
        return photos.value[currentPhotoType?.key] !== null
    }

    return false
}

function getStepClass(index: number) {
    const photoType = photoTypes.value[index]
    const hasPhoto = photos.value[photoType?.key] !== null

    if (index === currentStep.value) {
        return 'bg-blue-600 text-white border-blue-600'
    } else if (hasPhoto) {
        return 'bg-green-600 text-white border-green-600'
    } else {
        return 'bg-gray-200 text-gray-600 border-gray-300'
    }
}

function setFileInputRef(el: any, key: string) {
    if (el) {
        fileInputRefs.value[key] = el as HTMLInputElement
    }
}

function triggerFileInput(key: string) {
    if (fileInputRefs.value[key]) {
        fileInputRefs.value[key].click()
    }
}

function previousStep() {
    if (currentStep.value > 0) {
        currentStep.value--
    }
}

function nextStep() {
    if (currentStep.value < photoTypes.value.length - 1) {
        currentStep.value++
    }
}

async function submitPhotos() {
    if (!canSubmit.value) return

    isUploading.value = true
    uploadProgress.value = 0
    currentUploadingIndex.value = -1
    currentUploadingLabel.value = ''
    error.value = ''
    success.value = ''

    try {
        // Create the final record with all uploaded photos
        currentUploadingLabel.value = 'Creating record...'
        uploadProgress.value = 50

        const formData = new FormData()
        formData.append('shipment_transport_id', props.id.toString())
        formData.append('create_record', 'true') // Flag to trigger approval workflow

        await axios.post('/containers/create-photo', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            }
        })

        uploadProgress.value = 100

        success.value = 'Record created successfully! Notifications sent to department representatives.'

        // Clear photos after successful upload
        photosToUpload.value.forEach(type => {
            if (photos.value[type.key]?.preview) {
                URL.revokeObjectURL(photos.value[type.key].preview)
            }
        })

        photos.value = {
            pallet_condition_photo: null,
            pallet_label_photo: null,
            gps_photo_before_installation: null,
            container_truck_photo: null,
            empty_container_photo: null,
            inside_gps_photo: null,
            half_loaded_photo: null,
            one_side_door_closed_with_container_number_photo: null,
            complete_loaded_photo: null,
            outside_gps_photo: null,
            fork_seal_photo:null,
            security_seal_photo: null,
            container_full_seal_photo: null,
        }

        // Close modal after a short delay
        setTimeout(() => {
            emit('close')
        }, 2000)

    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to create record. Please try again.'
        console.error('Upload error:', err)
    } finally {
        isUploading.value = false
        currentUploadingIndex.value = -1
        currentUploadingLabel.value = ''
    }
}

// Auto-submit when all photos are complete AND user uploaded photos in this session
watch(allPhotosComplete, async (isComplete) => {
    if (isComplete && !isUploading.value && photosUploadedInSession.value > 0) {
        console.log('All photos complete and user uploaded in session, checking for auto-submit...')
        // Wait a bit longer to ensure photo upload is fully complete
        setTimeout(async () => {
            console.log('Checking auto-submit conditions:', {
                allPhotosComplete: allPhotosComplete.value,
                isUploading: isUploading.value,
                uploadingPhoto: uploadingPhoto.value,
                photosUploadedInSession: photosUploadedInSession.value
            })
            if (allPhotosComplete.value && !isUploading.value && !uploadingPhoto.value && photosUploadedInSession.value > 0) {
                console.log('Auto-submitting photos...')
                await submitPhotos()
            }
        }, 1500) // Increased delay to be safe
    }
})

// Also watch for individual photo uploads to trigger auto-submit
watch(uploadingPhoto, async (isUploadingPhoto, wasUploadingPhoto) => {
    // When photo upload completes (changes from true to false)
    if (wasUploadingPhoto && !isUploadingPhoto && allPhotosComplete.value && !isUploading.value && photosUploadedInSession.value > 0) {
        console.log('Photo upload completed, checking if all photos are done...')
        setTimeout(async () => {
            if (allPhotosComplete.value && !isUploading.value && !uploadingPhoto.value && photosUploadedInSession.value > 0) {
                console.log('All photos uploaded in session, auto-submitting...')
                await submitPhotos()
            }
        }, 500)
    }
})

// Load existing photos and set correct starting step when modal opens
watch(() => props.show, async (newVal) => {
    if (newVal) {
        // Reset session counter for new modal session
        photosUploadedInSession.value = 0
        // Load required photos configuration and existing photos for this container
        await loadRequiredPhotos()
        await loadExistingPhotos()
    } else {
        // Clear any existing previews when closing
        Object.keys(photos.value).forEach(type => {
            if (photos.value[type]?.preview) {
                URL.revokeObjectURL(photos.value[type].preview)
            }
        })
        photos.value = {
            pallet_condition_photo: null,
            pallet_label_photo: null,
            gps_photo_before_installation: null,
            container_truck_photo: null,
            empty_container_photo: null,
            inside_gps_photo: null,
            half_loaded_photo: null,
            one_side_door_closed_with_container_number_photo: null,
            complete_loaded_photo: null,
            outside_gps_photo: null,
            fork_seal_photo:null,
            security_seal_photo: null,
            container_full_seal_photo: null,
        }
        photoTypes.value = []
        currentStep.value = 0
        error.value = ''
        success.value = ''
        uploadProgress.value = 0
        photosUploadedInSession.value = 0
    }
})

async function loadExistingPhotos() {
    try {
        console.log('Loading existing photos for container:', props.id)
        const response = await axios.get(`/containers/${props.id}/photos`)
        const existingPhotos = response.data.data || []
        console.log('Existing photos from API:', existingPhotos)

        // Clear current photos first
        photos.value = {
            pallet_condition_photo: null,
            pallet_label_photo: null,
            gps_photo_before_installation: null,
            container_truck_photo: null,
            empty_container_photo: null,
            inside_gps_photo: null,
            half_loaded_photo: null,
            one_side_door_closed_with_container_number_photo: null,
            complete_loaded_photo: null,
            outside_gps_photo: null,
            fork_seal_photo:null,
            security_seal_photo: null,
            container_full_seal_photo: null,
        }

        // Group photos by label and take the most recent one for each type
        const photosByLabel = {}
        existingPhotos.forEach((photo: any) => {
            if (photo.photo_path) {
                console.log('Processing photo:', photo.label, photo.photo_path)
                // If we already have a photo for this label, keep the most recent one
                if (!photosByLabel[photo.label] || new Date(photo.created_at) > new Date(photosByLabel[photo.label].created_at)) {
                    photosByLabel[photo.label] = photo
                }
            }
        })

        console.log('Photos by label:', photosByLabel)

        // Load the most recent photo for each label
        Object.values(photosByLabel).forEach((photo: any) => {
            const previewUrl = `/storage/${photo.photo_path}`
            console.log('Setting photo for', photo.label, 'with preview:', previewUrl)
            photos.value[photo.label] = {
                file: null, // No file object for existing photos
                preview: previewUrl,
                existing: true
            }
        })

        console.log('Final photos state:', photos.value)

        // Set current step to the first incomplete step
        setCurrentStepToFirstIncomplete()

    } catch (error) {
        console.error('Error loading existing photos:', error)
        // If loading fails, start from step 0
        currentStep.value = 0
    }
}

async function loadRequiredPhotos() {
    try {
        console.log('Loading required photos for container:', props.id)
        const response = await axios.get(`/containers/${props.id}/required-photos`)
        photoTypes.value = response.data.data || []
        console.log('Required photo types:', photoTypes.value)
    } catch (error) {
        console.error('Error loading required photos:', error)
        // Fallback to all photos if loading fails
        photoTypes.value = [
            { key: 'pallet_condition_photo', label: 'Pallet Condition' },
            { key: 'pallet_label_photo', label: 'Pallet Label' },
            { key: 'container_truck_photo', label: 'Container Truck' },
            { key: 'empty_container_photo', label: 'Empty Container' },
            { key: 'half_loaded_photo', label: 'Half Loaded' },
            { key: 'one_side_door_closed_with_container_number_photo', label: 'Door Closed' },
            { key: 'complete_loaded_photo', label: 'Complete Loaded' },
            { key: 'container_full_seal', label: 'Container Full Seal' },
        ]
    }
}

function setCurrentStepToFirstIncomplete() {
    // Find the first step that doesn't have a photo
    for (let i = 0; i < photoTypes.value.length; i++) {
        const photoType = photoTypes.value[i]
        if (!photos.value[photoType.key]) {
            currentStep.value = i
            return
        }
    }
    // If all photos are complete, stay on the last step
    currentStep.value = photoTypes.value.length - 1
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
