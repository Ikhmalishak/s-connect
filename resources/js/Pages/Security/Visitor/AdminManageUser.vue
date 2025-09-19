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
import PasswordPolicyModal from "@/Components/ManageUser/PasswordPolicyModal.vue";
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

async function fetchUser() {
    try {
        const res = await axios.get("/admin/visitor/user-list");
        userList.value = res.data.data; // <-- fixed
    } catch (error) {
        console.error("Failed to fetch users:", error);
    }
}

async function deleteUser(userId: number) {
    try {
        await axios.delete(`/delete-user/${userId}`);
        await fetchUser(); // refresh table after delete
    } catch (error) {
        console.error("Failed to delete user:", error);
    }
}

onMounted(() => {
    console.log("Mounted VisitorTable.vue");
    fetchUser();
    intervalId = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

function openDeleteUserModal(user) {
    selectedUser.value = user;
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
                        <BreadcrumbPage>Dashboard</BreadcrumbPage>
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

        <UserTable
            :user="userList"
            :limit="limitTable"
            @update:limit="limitTable = $event"
            @search="searchQuery = $event"
            @open-password-policy-modal="showPasswordPolicyModal = true"
            @open-create-user-modal="showCreateUserModal = true"
            @open-edit-user-modal="openEditUserModal"
            @open-delete-user-modal="openDeleteUserModal"
        />

        <PasswordPolicyModal
            :show="showPasswordPolicyModal"
            @close="showPasswordPolicyModal = false"
        />

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
            :user-id="selectedUser?.id"
            v-if="selectedUser"
            @deleted="fetchUser"
            @close="closeDeleteUserModal"
        />
    </AdminAuthenticatedLayout>
</template>
