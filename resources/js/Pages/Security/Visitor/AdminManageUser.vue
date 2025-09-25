<script setup lang="ts">
import AdminAuthenticatedLayout from "@/Layouts/AdminAuthenticatedLayout.vue";
import { Card } from "@/components/ui/card";
import axios from "axios";
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import {
    Breadcrumb,
    BreadcrumbPage,
    BreadcrumbSeparator,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
} from "@/components/ui/breadcrumb";
import UserTable from "@/Components/ManageUser/UserTable.vue";
// import PasswordPolicyModal from "@/Components/ManageUser/PasswordPolicyModal.vue";
import CreateUserModal from "@/Components/ManageUser/CreateUserModal.vue";
import EditUserModal from "@/Components/ManageUser/EditUserModal .vue";
import DeleteUserModal from "@/Components/ManageUser/DeleteUserModal.vue";

const limitTable = ref("50");
const searchQuery = ref("");
const currentTime = ref(new Date());
const userList = ref([]);
const showPasswordPolicyModal = ref(false);
const showCreateUserModal = ref(false);
const showEditUserModal = ref(false);
const showDeleteUserModal = ref(false);
const selectedUser = ref(null);
const totalAdmin = ref(0);
const totalUser = ref(0);
const totalRecentUser = ref(0);
let intervalId;

const formattedDate = computed(() =>
    currentTime.value.toLocaleDateString("en-GB", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
    })
);

const formattedTime = computed(() =>
    currentTime.value.toLocaleTimeString("en-GB")
);

function openEditUserModal(user) {
    selectedUser.value = user; // pass clicked user
    showEditUserModal.value = true;
}

async function fetchUser(
    limit = limitTable.value,
    keyword = searchQuery.value
) {
    try {
        const res = await axios.get("/admin/visitor/user-list", {
            params: {
                limit,
                keyword,
            },
        });
        userList.value = res.data.data; // <-- fixed
    } catch (error) {
        console.error("Failed to fetch users:", error);
    }
}

async function fetchUserStatsCard() {
    try {
        const res = await axios.get("/admin/user-stats-card");
        let statscard = res.data;

        totalAdmin.value = statscard.total_admin;
        totalRecentUser.value = statscard.total_recent_users;
        totalUser.value = statscard.total_user;
    } catch (error) {
        console.error("Failed to fetch users:", error);
    }
}

onMounted(() => {
    console.log("Mounted VisitorTable.vue");
    fetchUser();
    fetchUserStatsCard();
    intervalId = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

function openDeleteUserModal(user) {
    console.log("test enter function", user);
    selectedUser.value = user;
    console.log("finish assigneded", selectedUser);
    showDeleteUserModal.value = true;
}

function closeDeleteUserModal() {
    showDeleteUserModal.value = false;
    selectedUser.value = null;
}

watch(selectedUser, (newUser) => {
    if (newUser) {
        console.log("Selected user changed:", newUser.id);
    }
});

watch(limitTable, (newVal) => {
    console.log("Limit changed to:", newVal);
    fetchUser();
});

watch(searchQuery, (newVal) => {
    console.log("Search query changed to:", newVal);
    fetchUser();
});
</script>

<template>
    <AdminAuthenticatedLayout>
        <template #breadcrumb>
            <Breadcrumb>
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink href="/"
                            >Visitor Management System</BreadcrumbLink
                        >
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbPage>Manage User</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>
        </template>

        <Card
            class="shadow-lg shadow-opacity-30 p-2 mb-4 text-2xl font-bold flex items-center justify-between bg-gray-100"
        >
            <div class="flex flex-row items-center">
                <img src="/assets/ss1.png" class="h-12 w-12" alt="" />
                <div>Visitor Management System</div>
            </div>
            <div class="flex flex-row items-center gap-10">
                <div
                    class="flex flex-row space-x-4 text-base font-normal text-gray-600 text-right"
                >
                    <div>{{ formattedDate }}</div>
                    <div>{{ formattedTime }}</div>
                </div>
            </div>
        </Card>

        <!-- Quick Stats Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            <Card
                class="p-6 bg-gradient-to-br from-blue-50 to-blue-100 border-l-4 border-blue-500 hover:shadow-lg transition-shadow"
            >
                <div class="flex items-center">
                    <div class="p-3 bg-blue-500 rounded-full">
                        <svg
                            class="w-6 h-6 text-white"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                            ></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-blue-600">
                            Total Users
                        </p>
                        <p class="text-2xl font-bold text-blue-900">{{ totalUser }}</p>
                    </div>
                </div>
            </Card>

            <Card
                class="p-6 bg-gradient-to-br from-purple-50 to-purple-100 border-l-4 border-purple-500 hover:shadow-lg transition-shadow"
            >
                <div class="flex items-center">
                    <div class="p-3 bg-purple-500 rounded-full">
                        <svg
                            class="w-6 h-6 text-white"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                            ></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-purple-600">
                            Admin Users
                        </p>
                        <p class="text-2xl font-bold text-purple-900">{{ totalAdmin }}</p>
                    </div>
                </div>
            </Card>

            <Card
                class="p-6 bg-gradient-to-br from-orange-50 to-orange-100 border-l-4 border-orange-500 hover:shadow-lg transition-shadow"
            >
                <div class="flex items-center">
                    <div class="p-3 bg-orange-500 rounded-full">
                        <svg
                            class="w-6 h-6 text-white"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                            ></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-orange-600">
                            Recent Users
                        </p>
                        <p class="text-2xl font-bold text-orange-900">{{ totalRecentUser }}</p>
                    </div>
                </div>
            </Card>
        </div>

        <UserTable
            :user="userList"
            :limit="limitTable"
            @update:limit="limitTable = $event"
            @search="searchQuery = $event"
            @open-create-user-modal="showCreateUserModal = true"
            @open-edit-user-modal="openEditUserModal"
            @open-delete-user-modal="openDeleteUserModal"
        />

        <!-- <PasswordPolicyModal
            :show="showPasswordPolicyModal"
            @close="showPasswordPolicyModal = false"
        /> -->

        <CreateUserModal
            :show="showCreateUserModal"
            @close="showCreateUserModal = false"
            @saved="fetchUser"
        />

        <EditUserModal
            :show="showEditUserModal"
            :user="selectedUser"
            @saved="fetchUser"
            @close="showEditUserModal = false"
        />

        <DeleteUserModal
            :show="showDeleteUserModal"
            :user-id="selectedUser"
            v-if="selectedUser"
            @deleted="fetchUser"
            @close="closeDeleteUserModal"
        />
    </AdminAuthenticatedLayout>
</template>
