<template>
    <Dialog :open="show" @open-change="$emit('close')">
        <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Manage Permissions for {{ user?.name }}</DialogTitle>
                <DialogDescription>
                    View and modify user permissions. Changes take effect immediately.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-6">
                <!-- Current Permissions -->
                <div>
                    <h3 class="text-lg font-semibold mb-3">Current Permissions</h3>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="permission in userPermissions"
                            :key="permission"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                        >
                            {{ permission }}
                        </span>
                    </div>
                </div>

                <!-- Add Permission -->
                <div>
                    <h3 class="text-lg font-semibold mb-3">Add Permission</h3>
                    <div class="flex gap-2">
                        <select
                            v-model="selectedPermission"
                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        >
                            <option value="">Select a permission to add</option>
                            <option
                                v-for="permission in availablePermissions"
                                :key="permission"
                                :value="permission"
                                :disabled="userPermissions.includes(permission)"
                            >
                                {{ permission }}
                            </option>
                        </select>
                        <Button
                            @click="addPermission"
                            :disabled="!selectedPermission"
                            class="bg-green-600 hover:bg-green-700"
                        >
                            Add
                        </Button>
                    </div>
                </div>

                <!-- Remove Permission -->
                <div>
                    <h3 class="text-lg font-semibold mb-3">Remove Permission</h3>
                    <div class="flex gap-2">
                        <select
                            v-model="selectedPermissionToRemove"
                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        >
                            <option value="">Select a permission to remove</option>
                            <option
                                v-for="permission in userPermissions"
                                :key="permission"
                                :value="permission"
                            >
                                {{ permission }}
                            </option>
                        </select>
                        <Button
                            @click="removePermission"
                            :disabled="!selectedPermissionToRemove"
                            class="bg-red-600 hover:bg-red-700"
                        >
                            Remove
                        </Button>
                    </div>
                </div>

                <!-- Permission Groups -->
                <div>
                    <h3 class="text-lg font-semibold mb-3">Quick Actions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-medium mb-2">Container Permissions</h4>
                            <div class="space-y-2">
                                <Button
                                    @click="addContainerPermissions"
                                    variant="outline"
                                    class="w-full justify-start"
                                    size="sm"
                                >
                                    Add All Container Permissions
                                </Button>
                                <Button
                                    @click="removeContainerPermissions"
                                    variant="outline"
                                    class="w-full justify-start"
                                    size="sm"
                                >
                                    Remove All Container Permissions
                                </Button>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium mb-2">Visitor Permissions</h4>
                            <div class="space-y-2">
                                <Button
                                    @click="addVisitorPermissions"
                                    variant="outline"
                                    class="w-full justify-start"
                                    size="sm"
                                >
                                    Add All Visitor Permissions
                                </Button>
                                <Button
                                    @click="removeVisitorPermissions"
                                    variant="outline"
                                    class="w-full justify-start"
                                    size="sm"
                                >
                                    Remove All Visitor Permissions
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="$emit('close')">
                    Close
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'

const props = defineProps({
    show: Boolean,
    user: Object
})

const emit = defineEmits(['close', 'permissionsUpdated'])

const selectedPermission = ref('')
const selectedPermissionToRemove = ref('')
const availablePermissions = ref([])
const loading = ref(false)

const userPermissions = computed(() => {
    return props.user?.permissions || []
})

const fetchAvailablePermissions = async () => {
    try {
        loading.value = true
        const response = await axios.get('/admin/permissions')
        availablePermissions.value = response.data.permissions
    } catch (error) {
        console.error('Error fetching permissions:', error)
    } finally {
        loading.value = false
    }
}

const addPermission = async () => {
    if (!selectedPermission.value) return

    try {
        await axios.post(`/admin/users/${props.user.id}/permissions`, {
            permission: selectedPermission.value,
            action: 'add'
        })

        // Update local user permissions
        if (props.user) {
            props.user.permissions = [...userPermissions.value, selectedPermission.value]
        }

        selectedPermission.value = ''
        emit('permissionsUpdated')
    } catch (error) {
        console.error('Error adding permission:', error)
        alert('Failed to add permission')
    }
}

const removePermission = async () => {
    if (!selectedPermissionToRemove.value) return

    try {
        await axios.post(`/admin/users/${props.user.id}/permissions`, {
            permission: selectedPermissionToRemove.value,
            action: 'remove'
        })

        // Update local user permissions
        if (props.user) {
            props.user.permissions = userPermissions.value.filter(p => p !== selectedPermissionToRemove.value)
        }

        selectedPermissionToRemove.value = ''
        emit('permissionsUpdated')
    } catch (error) {
        console.error('Error removing permission:', error)
        alert('Failed to remove permission')
    }
}

const addContainerPermissions = async () => {
    const containerPermissions = availablePermissions.filter(p => p.startsWith('container.'))

    for (const permission of containerPermissions) {
        if (!userPermissions.value.includes(permission)) {
            try {
                await axios.post(`/admin/users/${props.user.id}/permissions`, {
                    permission: permission,
                    action: 'add'
                })
            } catch (error) {
                console.error(`Error adding ${permission}:`, error)
            }
        }
    }

    // Refresh permissions
    if (props.user) {
        const response = await axios.get(`/admin/users/${props.user.id}/permissions`)
        props.user.permissions = response.data.permissions
    }

    emit('permissionsUpdated')
}

const removeContainerPermissions = async () => {
    const containerPermissions = availablePermissions.filter(p => p.startsWith('container.'))

    for (const permission of containerPermissions) {
        if (userPermissions.value.includes(permission)) {
            try {
                await axios.post(`/admin/users/${props.user.id}/permissions`, {
                    permission: permission,
                    action: 'remove'
                })
            } catch (error) {
                console.error(`Error removing ${permission}:`, error)
            }
        }
    }

    // Refresh permissions
    if (props.user) {
        const response = await axios.get(`/admin/users/${props.user.id}/permissions`)
        props.user.permissions = response.data.permissions
    }

    emit('permissionsUpdated')
}

const addVisitorPermissions = async () => {
    const visitorPermissions = availablePermissions.filter(p => p.startsWith('visitor.'))

    for (const permission of visitorPermissions) {
        if (!userPermissions.value.includes(permission)) {
            try {
                await axios.post(`/admin/users/${props.user.id}/permissions`, {
                    permission: permission,
                    action: 'add'
                })
            } catch (error) {
                console.error(`Error adding ${permission}:`, error)
            }
        }
    }

    // Refresh permissions
    if (props.user) {
        const response = await axios.get(`/admin/users/${props.user.id}/permissions`)
        props.user.permissions = response.data.permissions
    }

    emit('permissionsUpdated')
}

const removeVisitorPermissions = async () => {
    const visitorPermissions = availablePermissions.filter(p => p.startsWith('visitor.'))

    for (const permission of visitorPermissions) {
        if (userPermissions.value.includes(permission)) {
            try {
                await axios.post(`/admin/users/${props.user.id}/permissions`, {
                    permission: permission,
                    action: 'remove'
                })
            } catch (error) {
                console.error(`Error removing ${permission}:`, error)
            }
        }
    }

    // Refresh permissions
    if (props.user) {
        const response = await axios.get(`/admin/users/${props.user.id}/permissions`)
        props.user.permissions = response.data.permissions
    }

    emit('permissionsUpdated')
}

// Fetch permissions when modal opens
watch(() => props.show, (newVal) => {
    if (newVal) {
        fetchAvailablePermissions()
    } else {
        selectedPermission.value = ''
        selectedPermissionToRemove.value = ''
    }
})
</script>
