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
                <!-- Photo Upload Sections -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Pallet Condition Photo -->
                    <div class="space-y-2">
                        <Label class="text-sm font-medium">Pallet Condition</Label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-3 text-center hover:border-gray-400 transition-colors">
                            <input
                                type="file"
                                ref="palletConditionRef"
                                accept="image/*"
                                @change="handleFileSelect('pallet_condition_photo', $event)"
                                class="hidden"
                            />
                            <div v-if="!photos.pallet_condition_photo" class="space-y-2">
                                <div class="flex gap-2 justify-center">
                                    <button
                                        type="button"
                                        @click="takePhoto('pallet_condition_photo')"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Take Photo
                                    </button>
                                    <button
                                        type="button"
                                        @click="palletConditionRef.click()"
                                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Upload from Gallery
                                    </button>
                                </div>
                                <p class="text-xs text-gray-600">Pallet condition</p>
                            </div>
                            <div v-else class="relative">
                                <img :src="photos.pallet_condition_photo.preview" class="max-h-20 mx-auto rounded" />
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    class="absolute -top-2 -right-2 h-6 w-6"
                                    @click="removePhoto('pallet_condition_photo')"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Pallet Label Photo -->
                    <div class="space-y-2">
                        <Label class="text-sm font-medium">Pallet Label</Label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-3 text-center hover:border-gray-400 transition-colors">
                            <input
                                type="file"
                                ref="palletLabelRef"
                                accept="image/*"
                                @change="handleFileSelect('pallet_label_photo', $event)"
                                class="hidden"
                            />
                            <div v-if="!photos.pallet_label_photo" class="space-y-2">
                                <div class="flex gap-2 justify-center">
                                    <button
                                        type="button"
                                        @click="takePhoto('pallet_label_photo')"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Take Photo
                                    </button>
                                    <button
                                        type="button"
                                        @click="palletLabelRef.click()"
                                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Upload from Gallery
                                    </button>
                                </div>
                                <p class="text-xs text-gray-600">Pallet label</p>
                            </div>
                            <div v-else class="relative">
                                <img :src="photos.pallet_label_photo.preview" class="max-h-20 mx-auto rounded" />
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    class="absolute -top-2 -right-2 h-6 w-6"
                                    @click="removePhoto('pallet_label_photo')"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- GPS Photo Before Installation -->
                    <div class="space-y-2">
                        <Label class="text-sm font-medium">GPS Before Installation</Label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-3 text-center hover:border-gray-400 transition-colors">
                            <input
                                type="file"
                                ref="gpsBeforeRef"
                                accept="image/*"
                                @change="handleFileSelect('gps_photo_before_installation', $event)"
                                class="hidden"
                            />
                            <div v-if="!photos.gps_photo_before_installation" class="space-y-2">
                                <div class="flex gap-2 justify-center">
                                    <button
                                        type="button"
                                        @click="takePhoto('gps_photo_before_installation')"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Take Photo
                                    </button>
                                    <button
                                        type="button"
                                        @click="gpsBeforeRef.click()"
                                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Upload from Gallery
                                    </button>
                                </div>
                                <p class="text-xs text-gray-600">GPS before</p>
                            </div>
                            <div v-else class="relative">
                                <img :src="photos.gps_photo_before_installation.preview" class="max-h-20 mx-auto rounded" />
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    class="absolute -top-2 -right-2 h-6 w-6"
                                    @click="removePhoto('gps_photo_before_installation')"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Container Truck Photo -->
                    <div class="space-y-2">
                        <Label class="text-sm font-medium">Container Truck</Label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-3 text-center hover:border-gray-400 transition-colors">
                            <input
                                type="file"
                                ref="containerTruckRef"
                                accept="image/*"
                                @change="handleFileSelect('container_truck_photo', $event)"
                                class="hidden"
                            />
                            <div v-if="!photos.container_truck_photo" class="space-y-2">
                                <div class="flex gap-2 justify-center">
                                    <button
                                        type="button"
                                        @click="takePhoto('container_truck_photo')"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Take Photo
                                    </button>
                                    <button
                                        type="button"
                                        @click="containerTruckRef.click()"
                                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Upload from Gallery
                                    </button>
                                </div>
                                <p class="text-xs text-gray-600">Container truck</p>
                            </div>
                            <div v-else class="relative">
                                <img :src="photos.container_truck_photo.preview" class="max-h-20 mx-auto rounded" />
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    class="absolute -top-2 -right-2 h-6 w-6"
                                    @click="removePhoto('container_truck_photo')"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty Container Photo -->
                    <div class="space-y-2">
                        <Label class="text-sm font-medium">Empty Container</Label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-3 text-center hover:border-gray-400 transition-colors">
                            <input
                                type="file"
                                ref="emptyContainerRef"
                                accept="image/*"
                                @change="handleFileSelect('empty_container_photo', $event)"
                                class="hidden"
                            />
                            <div v-if="!photos.empty_container_photo" class="space-y-2">
                                <div class="flex gap-2 justify-center">
                                    <button
                                        type="button"
                                        @click="takePhoto('empty_container_photo')"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Take Photo
                                    </button>
                                    <button
                                        type="button"
                                        @click="emptyContainerRef.click()"
                                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Upload from Gallery
                                    </button>
                                </div>
                                <p class="text-xs text-gray-600">Empty container</p>
                            </div>
                            <div v-else class="relative">
                                <img :src="photos.empty_container_photo.preview" class="max-h-20 mx-auto rounded" />
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    class="absolute -top-2 -right-2 h-6 w-6"
                                    @click="removePhoto('empty_container_photo')"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Inside GPS Photo -->
                    <div class="space-y-2">
                        <Label class="text-sm font-medium">Inside GPS</Label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-3 text-center hover:border-gray-400 transition-colors">
                            <input
                                type="file"
                                ref="insideGpsRef"
                                accept="image/*"
                                @change="handleFileSelect('inside_gps_photo', $event)"
                                class="hidden"
                            />
                            <div v-if="!photos.inside_gps_photo" class="space-y-2">
                                <div class="flex gap-2 justify-center">
                                    <button
                                        type="button"
                                        @click="takePhoto('inside_gps_photo')"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Take Photo
                                    </button>
                                    <button
                                        type="button"
                                        @click="insideGpsRef.click()"
                                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Upload from Gallery
                                    </button>
                                </div>
                                <p class="text-xs text-gray-600">Inside GPS</p>
                            </div>
                            <div v-else class="relative">
                                <img :src="photos.inside_gps_photo.preview" class="max-h-20 mx-auto rounded" />
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    class="absolute -top-2 -right-2 h-6 w-6"
                                    @click="removePhoto('inside_gps_photo')"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Half Loaded Photo -->
                    <div class="space-y-2">
                        <Label class="text-sm font-medium">Half Loaded</Label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-3 text-center hover:border-gray-400 transition-colors">
                            <input
                                type="file"
                                ref="halfLoadedRef"
                                accept="image/*"
                                @change="handleFileSelect('half_loaded_photo', $event)"
                                class="hidden"
                            />
                            <div v-if="!photos.half_loaded_photo" class="space-y-2">
                                <div class="flex gap-2 justify-center">
                                    <button
                                        type="button"
                                        @click="takePhoto('half_loaded_photo')"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Take Photo
                                    </button>
                                    <button
                                        type="button"
                                        @click="halfLoadedRef.click()"
                                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Upload from Gallery
                                    </button>
                                </div>
                                <p class="text-xs text-gray-600">Half loaded</p>
                            </div>
                            <div v-else class="relative">
                                <img :src="photos.half_loaded_photo.preview" class="max-h-20 mx-auto rounded" />
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    class="absolute -top-2 -right-2 h-6 w-6"
                                    @click="removePhoto('half_loaded_photo')"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- One Side Door Closed -->
                    <div class="space-y-2">
                        <Label class="text-sm font-medium">Door Closed</Label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-3 text-center hover:border-gray-400 transition-colors">
                            <input
                                type="file"
                                ref="doorClosedRef"
                                accept="image/*"
                                @change="handleFileSelect('one_side_door_closed_with_container_number_photo', $event)"
                                class="hidden"
                            />
                            <div v-if="!photos.one_side_door_closed_with_container_number_photo" class="space-y-2">
                                <div class="flex gap-2 justify-center">
                                    <button
                                        type="button"
                                        @click="takePhoto('one_side_door_closed_with_container_number_photo')"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Take Photo
                                    </button>
                                    <button
                                        type="button"
                                        @click="doorClosedRef.click()"
                                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Upload from Gallery
                                    </button>
                                </div>
                                <p class="text-xs text-gray-600">Door closed</p>
                            </div>
                            <div v-else class="relative">
                                <img :src="photos.one_side_door_closed_with_container_number_photo.preview" class="max-h-20 mx-auto rounded" />
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    class="absolute -top-2 -right-2 h-6 w-6"
                                    @click="removePhoto('one_side_door_closed_with_container_number_photo')"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Complete Loaded Photo -->
                    <div class="space-y-2">
                        <Label class="text-sm font-medium">Complete Loaded</Label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-3 text-center hover:border-gray-400 transition-colors">
                            <input
                                type="file"
                                ref="completeLoadedRef"
                                accept="image/*"
                                @change="handleFileSelect('complete_loaded_photo', $event)"
                                class="hidden"
                            />
                            <div v-if="!photos.complete_loaded_photo" class="space-y-2">
                                <div class="flex gap-2 justify-center">
                                    <button
                                        type="button"
                                        @click="takePhoto('complete_loaded_photo')"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Take Photo
                                    </button>
                                    <button
                                        type="button"
                                        @click="completeLoadedRef.click()"
                                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Upload from Gallery
                                    </button>
                                </div>
                                <p class="text-xs text-gray-600">Complete loaded</p>
                            </div>
                            <div v-else class="relative">
                                <img :src="photos.complete_loaded_photo.preview" class="max-h-20 mx-auto rounded" />
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    class="absolute -top-2 -right-2 h-6 w-6"
                                    @click="removePhoto('complete_loaded_photo')"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Outside GPS Photo -->
                    <div class="space-y-2">
                        <Label class="text-sm font-medium">Outside GPS</Label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-3 text-center hover:border-gray-400 transition-colors">
                            <input
                                type="file"
                                ref="outsideGpsRef"
                                accept="image/*"
                                @change="handleFileSelect('outside_gps_photo', $event)"
                                class="hidden"
                            />
                            <div v-if="!photos.outside_gps_photo" class="space-y-2">
                                <div class="flex gap-2 justify-center">
                                    <button
                                        type="button"
                                        @click="takePhoto('outside_gps_photo')"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Take Photo
                                    </button>
                                    <button
                                        type="button"
                                        @click="outsideGpsRef.click()"
                                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Upload from Gallery
                                    </button>
                                </div>
                                <p class="text-xs text-gray-600">Outside GPS</p>
                            </div>
                            <div v-else class="relative">
                                <img :src="photos.outside_gps_photo.preview" class="max-h-20 mx-auto rounded" />
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    class="absolute -top-2 -right-2 h-6 w-6"
                                    @click="removePhoto('outside_gps_photo')"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Security Seal Photo -->
                    <div class="space-y-2">
                        <Label class="text-sm font-medium">Security Seal</Label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-3 text-center hover:border-gray-400 transition-colors">
                            <input
                                type="file"
                                ref="securitySealRef"
                                accept="image/*"
                                @change="handleFileSelect('security_seal_photo', $event)"
                                class="hidden"
                            />
                            <div v-if="!photos.security_seal_photo" class="space-y-2">
                                <div class="flex gap-2 justify-center">
                                    <button
                                        type="button"
                                        @click="takePhoto('security_seal_photo')"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Take Photo
                                    </button>
                                    <button
                                        type="button"
                                        @click="securitySealRef.click()"
                                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Upload from Gallery
                                    </button>
                                </div>
                                <p class="text-xs text-gray-600">Security seal</p>
                            </div>
                            <div v-else class="relative">
                                <img :src="photos.security_seal_photo.preview" class="max-h-20 mx-auto rounded" />
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    class="absolute -top-2 -right-2 h-6 w-6"
                                    @click="removePhoto('security_seal_photo')"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Container Full Seal Photo -->
                    <div class="space-y-2">
                        <Label class="text-sm font-medium">Container Full Seal</Label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-3 text-center hover:border-gray-400 transition-colors">
                            <input
                                type="file"
                                ref="containerFullSealRef"
                                accept="image/*"
                                @change="handleFileSelect('container_full_seal_photo', $event)"
                                class="hidden"
                            />
                            <div v-if="!photos.container_full_seal_photo" class="space-y-2">
                                <div class="flex gap-2 justify-center">
                                    <button
                                        type="button"
                                        @click="takePhoto('container_full_seal_photo')"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Take Photo
                                    </button>
                                    <button
                                        type="button"
                                        @click="containerFullSealRef.click()"
                                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                    >
                                        Upload from Gallery
                                    </button>
                                </div>
                                <p class="text-xs text-gray-600">Full seal</p>
                            </div>
                            <div v-else class="relative">
                                <img :src="photos.container_full_seal_photo.preview" class="max-h-20 mx-auto rounded" />
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    class="absolute -top-2 -right-2 h-6 w-6"
                                    @click="removePhoto('container_full_seal_photo')"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress Indicator -->
                <div v-if="isUploading" class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span>Uploading photos...</span>
                        <span>{{ uploadProgress }}%</span>
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
                            <div class="text-sm text-gray-500">
                                * Photos are optional but recommended for documentation
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
                                <button
                                    type="button"
                                    @click="submitPhotos"
                                    :disabled="!canSubmit || isUploading"
                                    class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 disabled:pointer-events-none disabled:opacity-50 bg-green-600 text-white shadow hover:bg-green-800 h-10 px-6 py-2 min-w-24"
                                >
                                    <svg v-if="isUploading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ isUploading ? 'Uploading...' : 'Submit Photos' }}
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
import { Label } from '@/components/ui/label'

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
    security_seal_photo: null,
    container_full_seal_photo: null,
})

const isUploading = ref(false)
const uploadProgress = ref(0)
const error = ref('')
const success = ref('')
const showCameraModal = ref(false)
const currentPhotoType = ref('')

// File input refs
const palletConditionRef = ref<HTMLInputElement>()
const palletLabelRef = ref<HTMLInputElement>()
const gpsBeforeRef = ref<HTMLInputElement>()
const containerTruckRef = ref<HTMLInputElement>()
const emptyContainerRef = ref<HTMLInputElement>()
const insideGpsRef = ref<HTMLInputElement>()
const halfLoadedRef = ref<HTMLInputElement>()
const doorClosedRef = ref<HTMLInputElement>()
const completeLoadedRef = ref<HTMLInputElement>()
const outsideGpsRef = ref<HTMLInputElement>()
const securitySealRef = ref<HTMLInputElement>()
const containerFullSealRef = ref<HTMLInputElement>()

const canSubmit = computed(() => {
    // At least one photo should be uploaded, but not all are required
    const hasAtLeastOnePhoto = Object.values(photos.value).some(photo => photo !== null)
    return hasAtLeastOnePhoto && !isUploading.value
})

function handleFileSelect(type: string, event: Event) {
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

function handleCameraCapture(imageData: string) {
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
    }
    showCameraModal.value = false
    currentPhotoType.value = ''
}

function closeCameraModal() {
    showCameraModal.value = false
    currentPhotoType.value = ''
}

async function submitPhotos() {
    if (!canSubmit.value) return

    isUploading.value = true
    uploadProgress.value = 0
    error.value = ''
    success.value = ''

    try {
        const formData = new FormData()
        formData.append('shipment_transport_id', props.id.toString())

        // Add photos to form data
        const photoTypes = [
            'pallet_condition_photo',
            'pallet_label_photo',
            'gps_photo_before_installation',
            'container_truck_photo',
            'empty_container_photo',
            'inside_gps_photo',
            'half_loaded_photo',
            'one_side_door_closed_with_container_number_photo',
            'complete_loaded_photo',
            'outside_gps_photo',
            'security_seal_photo',
            'container_full_seal_photo'
        ]
        photoTypes.forEach(type => {
            if (photos.value[type]) {
                formData.append(type, photos.value[type].file)
            }
        })

        // Simulate progress updates
        const progressInterval = setInterval(() => {
            if (uploadProgress.value < 90) {
                uploadProgress.value += 10
            }
        }, 200)

        const response = await axios.post('/containers/create-photo', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total) {
                    uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
                }
            }
        })

        clearInterval(progressInterval)
        uploadProgress.value = 100

        success.value = 'Photos uploaded successfully! Notifications sent to department representatives.'

        // Clear photos after successful upload
        photoTypes.forEach(type => {
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
            security_seal_photo: null,
            container_full_seal_photo: null,
        }

        // Close modal after a short delay
        setTimeout(() => {
            emit('close')
        }, 2000)

    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to upload photos. Please try again.'
        console.error('Upload error:', err)
    } finally {
        isUploading.value = false
    }
}

// Cleanup object URLs when component unmounts
watch(() => props.show, (newVal) => {
    if (!newVal) {
        // Clear any existing previews
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
            security_seal_photo: null,
            container_full_seal_photo: null,
        }
        error.value = ''
        success.value = ''
        uploadProgress.value = 0
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
