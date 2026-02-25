<script setup lang="ts">
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import { ref, watch } from "vue";
import { Card } from "@/components/ui/card";
import { Trash, Pencil, FileLock, UserRoundPlus, Unlock } from "lucide-vue-next";
import { Button } from "@/components/ui/button";

const props = defineProps<{
    limit: string;
    user: any;
}>();

const emit = defineEmits(["update:limit", "search", "openCreateUserModal", "openEditUserModal","openDeleteUserModal", "openManagePermissionsModal", "unlockAccount"]);

const searchQuery = ref("");

watch(searchQuery, (newVal) => {
    emit("search", newVal);
});
</script>

<template>
    <div class="relative">
        <!-- Badge -->
        <div class="absolute -top-3 left-2 z-10">
            <span
                class="bg-gray-100 text-black px-2 py-1 rounded-full text-xs font-semibold border shadow-md"
            >
                User List
            </span>
        </div>
        <Card class="p-5 shadow-2xl max-h-[700px] shadow-opacity-60">
            <div class="flex space-x-4 justify-between mb-2">
                <div class="flex items-center gap-4">
                    <div class="flex flex-row space-x-2">
                        <input
                            v-model="searchQuery"
                            class="w-400 bg-gray-300 text-black placeholder:text-black border-none rounded-lg text-sm"
                            placeholder="Search..."
                        />
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div>
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger>
                                    <button
                                        @click="emit('openCreateUserModal')"
                                    >
                                        <UserRoundPlus class="w-9 h-9" />
                                    </button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>Create User</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                    <div>
                        <Select
                            :model-value="limit"
                            @update:model-value="emit('update:limit', $event)"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select limit" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="50">50</SelectItem>
                                    <SelectItem value="100">100</SelectItem>
                                    <SelectItem value="200">200</SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </div>

            <div
                class="flex-1 overflow-y-auto max-h-[420px] border border-gray-300"
            >
                <table class="min-w-full">
                    <thead
                        class="sticky top-0 bg-gray-100 z-40 border border-b-gray-300"
                    >
                        <tr
                            class="border border-gray-300 font-black divide-x divide-gray-300 text-black"
                        >
                            <th
                                class="font-black text-black text-center bg-gray-100 p-3 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                No
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-3 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Name
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-3 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Site
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-3 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Email
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-3 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Role
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-3 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Last Password Change
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-3 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Status
                            </th>
                            <th
                                class="font-black text-black text-center bg-gray-100 p-3 sticky top-0 z-20 border-r border-gray-300 text-sm"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="border border-gray-300 divide-x divide-gray-300"
                    >
                        <tr
                            v-for="(u, index) in user"
                            :key="u.id"
                            class="text-sm border border-gray-300 divide-x divide-gray-300"
                        >
                            <td class="text-center p-2">{{ index + 1 }}</td>
                            <td class="text-center p-2">{{ u.name }}</td>
                            <td class="text-center p-2">{{ u.site?.name ?? "-" }}</td>
                            <td class="text-center p-2">{{ u.email }}</td>
                            <td class="text-center p-2">{{ u.roles[0].name ?? "-" }}</td>
                            <td class="text-center p-2">
                                {{
                                    u.password_changed_at
                                        ? new Date(
                                              u.password_changed_at
                                          ).toLocaleDateString("en-GB")
                                        : "-"
                                }}
                            </td>
                            <td class="text-center p-2">
                                <span
                                    :class="u.locked_until && new Date(u.locked_until) > new Date() ? 'text-red-600 font-semibold' : 'text-green-600'"
                                >
                                    {{ u.locked_until && new Date(u.locked_until) > new Date() ? 'Locked' : 'Active' }}
                                </span>
                            </td>
                            <td class="flex justify-center gap-2 p-2">
                                <!-- Edit button -->
                                <Button
                                    variant="outline"
                                    size="icon"
                                    class="text-blue-500 border-blue-300 hover:bg-blue-50"
                                    @click="$emit('openEditUserModal', u)"
                                >
                                    <Pencil class="w-4 h-4" />
                                </Button>

                                <!-- Unlock button (only show if account is locked) -->
                                <Button
                                    v-if="u.locked_until && new Date(u.locked_until) > new Date()"
                                    variant="outline"
                                    size="icon"
                                    class="text-orange-500 border-orange-300 hover:bg-orange-50"
                                    @click="$emit('unlockAccount', u)"
                                >
                                    <Unlock class="w-4 h-4" />
                                </Button>

                                <!-- Permissions button -->
                                <Button
                                    variant="outline"
                                    size="icon"
                                    class="text-green-500 border-green-300 hover:bg-green-50"
                                    @click="$emit('openManagePermissionsModal', u)"
                                >
                                    <FileLock class="w-4 h-4" />
                                </Button>

                                <!-- Delete button -->
                                <Button
                                    variant="outline"
                                    size="icon"
                                    class="text-red-500 border-red-300 hover:bg-red-50"
                                    @click="$emit('openDeleteUserModal', u.id)"
                                >
                                    <Trash class="w-4 h-4" />
                                </Button>
                            </td>
                        </tr>

                        <!-- empty state if no users -->
                        <tr v-if="!user || user.length === 0">
                            <td
                                colspan="8"
                                class="text-center p-4 text-gray-500"
                            >
                                No users found
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
