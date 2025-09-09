<script setup lang="ts">
import AdminAuthenticatedLayout from "@/Layouts/AdminAuthenticatedLayout.vue";
import {
    Breadcrumb,
    BreadcrumbPage,
    BreadcrumbSeparator,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
} from "@/components/ui/breadcrumb";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { ref, computed, nextTick, onMounted, watch } from "vue";
import axios from "axios";
import StaffVerificationTable from "@/Components/VisitorTableComponent/StaffVerificationTable.vue";

const errors = ref<{ staffName?: string; staffId?: string }>({});

// Modal state
const showVisitorVerification = ref(false);

//Modal to enter the visitor staff acknowledgement id
const showQrCodeModal = ref(false);

//visitor_acknowledge_id
const ackNumber = ref(""); // bind input for visitor ID

// Visitor data fetched from API
const visitor = ref<any>(null);
const verifiedVisitors = ref<any[]>([]); // reactive array

// Staff verification form
const staffName = ref("");
const staffId = ref("");

//Limit table
const limitTable = ref("25");
const searchQuery = ref("");

// Date/Time
const currentTime = ref(new Date());
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

// Open modal by scanning/entering visitor ID
async function openVisitorVerificationModal() {
    try {
        const res = await axios.get(
            `/visitor-staff-acknowledgement-details?ack_number=${ackNumber.value}`
        );

        visitor.value = {
            id: res.data.visitor_staff_acknowledgement.id,
            acknowledged_by:
                res.data.visitor_staff_acknowledgement.acknowledged_by,
            staff_id: res.data.visitor_staff_acknowledgement.staff_id,
            created_at: res.data.visitor_staff_acknowledgement.created_at,
            updated_at: res.data.visitor_staff_acknowledgement.updated_at,
            details: res.data.visitor_staff_acknowledgement.visitors[0] || null, // first visitor
            list_visitors:
                res.data.visitor_staff_acknowledgement.visitors || [], // full list
        };

        showQrCodeModal.value = false;
        showVisitorVerification.value = true;
    } catch (error) {
        console.error(error);
        alert("Visitor not found!");
    }
}

//function to retrive all verified visitor

async function getAllVerifiedVisitor(
    limit = limitTable.value,
    keyword = searchQuery.value
) {
    try {
        const res = await axios.get("/get-verified-visitors", {
            params: {
                limit,
                keyword,
            },
        });
        console.log(res.data);
        verifiedVisitors.value = res.data.visitors; // assign API result
    } catch (error) {
        console.error("Error fetching verified visitors:", error);
    }
}

// fetch when component mounts
onMounted(() => {
    getAllVerifiedVisitor();
});

// Function to reset form and close modal
const resetAndClose = () => {
    // Reset form values
    staffName.value = "";
    staffId.value = "";
    visitor.value = null;
    ackNumber.value = "";

    // Close modal
    showVisitorVerification.value = false;
};

// Submit staff verification
const submitVerification = async () => {
    errors.value = {}; // reset errors

    const trimmedStaffName = staffName.value.trim();
    const trimmedStaffId = staffId.value.trim();

    // Validation
    if (!trimmedStaffName) {
        errors.value.staffName = "Staff name is required";
    }
    if (!trimmedStaffId) {
        errors.value.staffId = "Staff ID is required";
    }

    // Stop if there are errors
    if (errors.value.staffName || errors.value.staffId) {
        return;
    }

    try {
        await axios.post("/verify-visitor", {
            ack_number: ackNumber.value,
            staff_name: trimmedStaffName,
            staff_id: trimmedStaffId,
        });

        alert("Visitor verified!");
        await nextTick();
        resetAndClose();
    } catch (error) {
        console.error(error);
        alert("Verification failed!");
    }
};

// Handle manual modal close (X button)
const handleModalClose = () => {
    resetAndClose();
};

watch(limitTable, (newVal) => {
    console.log("Limit changed to:", newVal);
    getAllVerifiedVisitor();
});

watch(searchQuery, (newVal) => {
    console.log("Search query changed to:", newVal);
    getAllVerifiedVisitor();
});
</script>

<template>
    <AdminAuthenticatedLayout>
        <!-- Breadcrumb -->
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
                        <BreadcrumbPage>Staff Verification</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>
        </template>

        <!-- Header -->
        <Card
            class="shadow-lg shadow-opacity-30 p-4 mb-4 text-2xl font-bold flex items-center justify-between bg-gray-100"
        >
            <div class="flex flex-row items-center space-x-3">
                <img src="/assets/ss1.png" class="h-12 w-12" alt="" />
                <div>Visitor Management System</div>
            </div>
            <div
                class="flex flex-row space-x-4 text-base font-normal text-gray-600 text-right"
            >
                <div>{{ formattedDate }}</div>
                <div>{{ formattedTime }}</div>
            </div>
        </Card>

        <!-- Sub Header -->
        <Card
            class="shadow-lg shadow-opacity-30 p-4 mb-4 text-xl font-bold flex items-center justify-between bg-gray-100"
        >
            <div class="flex flex-row items-center space-x-3">
                <div>Staff Verification</div>
            </div>
        </Card>

        <!-- Scan/Enter Visitor ID -->
        <Card class="p-3 shadow-2xl max-h-[700px] shadow-opacity-60 space-y-3">
            <StaffVerificationTable
                :visitors="verifiedVisitors"
                :limit="limitTable"
                @open-checkout-modal="showQrCodeModal = true"
                @update:limit="limitTable = $event"
                @search="searchQuery = $event"
            />
        </Card>

        <!-- Visitor Verification Modal -->
        <Dialog
            v-model:open="showVisitorVerification"
            @update:open="(open) => !open && handleModalClose()"
        >
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Visitor Verification</DialogTitle>
                </DialogHeader>

                <div v-if="visitor">
                    <!-- Visitors Table -->
                    <table
                        class="table-auto border-collapse border border-gray-400 w-full text-sm"
                    >
                        <thead>
                            <tr class="bg-gray-100">
                                <th
                                    class="border border-gray-400 px-2 py-1 text-left"
                                >
                                    Name
                                </th>
                                <th
                                    class="border border-gray-400 px-2 py-1 text-left"
                                >
                                    Type
                                </th>
                                <th
                                    class="border border-gray-400 px-2 py-1 text-left"
                                >
                                    Company
                                </th>
                                <th
                                    class="border border-gray-400 px-2 py-1 text-left"
                                >
                                    Purpose
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="v in visitor.list_visitors" :key="v.id">
                                <td class="border border-gray-400 px-2 py-1">
                                    {{ v.visitor_name }}
                                </td>
                                <td class="border border-gray-400 px-2 py-1">
                                    {{ v.visitor_type }}
                                </td>
                                <td class="border border-gray-400 px-2 py-1">
                                    {{ v.visitor_company }}
                                </td>
                                <td class="border border-gray-400 px-2 py-1">
                                    {{ v.purpose }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Staff Form -->
                    <div>
                        <Label for="staffName">Staff Name</Label>
                        <Input
                            id="staffName"
                            v-model="staffName"
                            placeholder="Enter your name"
                            @keyup.enter="submitVerification"
                        />
                        <p
                            v-if="errors.staffName"
                            class="text-red-500 text-sm mt-1"
                        >
                            {{ errors.staffName }}
                        </p>
                    </div>

                    <div>
                        <Label for="staffId">Staff ID</Label>
                        <Input
                            id="staffId"
                            v-model="staffId"
                            placeholder="Enter your staff ID"
                            @keyup.enter="submitVerification"
                        />
                        <p
                            v-if="errors.staffId"
                            class="text-red-500 text-sm mt-1"
                        >
                            {{ errors.staffId }}
                        </p>
                    </div>
                </div>

                <DialogFooter class="mt-4">
                    <Button variant="secondary" @click="handleModalClose"
                        >Cancel</Button
                    >
                    <Button @click="submitVerification">Verify</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- QR Code Modal -->
        <Dialog v-model:open="showQrCodeModal">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle class="text-center"
                        >Visitor Verification</DialogTitle
                    >
                    <DialogDescription class="text-center">
                        Please Scan the QR Code
                    </DialogDescription>
                </DialogHeader>

                <!-- QR Code Input -->
                <div class="space-y-4 mt-4">
                    <div>
                        <Input
                            id="ackNumber"
                            v-model="ackNumber"
                            placeholder="Scan QR Code"
                            class="placeholder:text-center h-16 text-xl placeholder:text-xl"
                            @keyup.enter="openVisitorVerificationModal"
                        />
                    </div>
                </div>

                <DialogFooter class="mt-4 text-xs">
                    <label
                        >This feature allows visitors to quickly check in or
                        check out by scanning their pass ID at designated
                        points. The system automatically updates their status in
                        real time, ensuring accurate logs and smooth security
                        compliance for both arrival and departure.</label
                    >
                    <Button @click="openVisitorVerificationModal"
                        >Submit Scan</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminAuthenticatedLayout>
</template>
