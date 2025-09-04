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
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { ref, computed } from "vue";
import axios from "axios";
import StaffVerificationTable from "@/Components/VisitorTableComponent/StaffVerificationTable.vue";

// Modal state
const showQrCodeModal = ref(false);

//visitor_acknowledge_id
const visitorAcknowledgeId = ref(null);

// Visitor data fetched from API
const visitor = ref<any>(null);

// Staff verification form
const staffName = ref("");
const staffId = ref("");

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
async function openVisitorVerificationModal(visitorAcknowledgeId: string) {
    try {
        const res = await axios.get(
            `/visitor-staff-acknowledgement-details?id=${visitorAcknowledgeId}`
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

        showQrCodeModal.value = true;
    } catch (error) {
        console.error(error);
        alert("Visitor not found!");
    }
}

// Submit staff verification
const submitVerification = async () => {
    if (!staffName.value || !staffId.value) {
        alert("Please fill in staff details");
        return;
    }

    try {
        await axios.post("/verify-visitor", {
            visitor_ack_id: visitor.value.id,
            staff_name: staffName.value,
            staff_id: staffId.value,
        });

        showQrCodeModal.value = false;
        alert("Visitor verified!");
        visitor.value = null;
        staffName.value = "";
        staffId.value = "";
    } catch (error) {
        console.error(error);
        alert("Verification failed!");
    }
};
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

        <!-- Scan/Enter Visitor ID -->
        <Card class="p-3 shadow-2xl max-h-[700px] shadow-opacity-60 space-y-3">
            <Input placeholder="Enter Visitor ID" v-model="visitorIdInput" />
            <Button
                class="w-full"
                @click="openVisitorVerificationModal(visitorIdInput)"
            >
                Scan / Enter Visitor QR
            </Button>

            <StaffVerificationTable
            />
        </Card>

        <!-- Modal -->
        <Dialog v-model:open="showQrCodeModal">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Visitor Verification</DialogTitle>
                </DialogHeader>

                <!-- Visitor Details -->
                <!-- <div v-if="visitor" class="space-y-2 text-sm">
                    <p><strong>ID:</strong> {{ visitor.id }}</p>
                    <p>
                        <strong>Visitor Detail ID:</strong>
                        {{ visitor.details?.visitor_id }}
                    </p>
                    <p><strong>Created At:</strong> {{ visitor.created_at }}</p>
                    <p><strong>Updated At:</strong> {{ visitor.updated_at }}</p> -->
                <!-- Optionally display the full list -->
                <!-- <div v-if="visitor.list_visitors.length">
                        <p class="font-bold mt-2">
                            Other Visitors in Acknowledgement:
                        </p>
                        <ul class="list-disc pl-5">
                            <li v-for="v in visitor.list_visitors" :key="v.id">
                                {{ v.visitor_id }}
                            </li>
                        </ul>
                    </div>
                </div> -->

                <!-- First visitor -->
                <p>
                    <strong>Visitor Name:</strong>
                    {{ visitor.details?.visitor_name }}
                </p>
                <p>
                    <strong>Visitor Type:</strong>
                    {{ visitor.details?.visitor_type }}
                </p>
                <p>
                    <strong>Company:</strong>
                    {{ visitor.details?.visitor_company }}
                </p>
                <p><strong>Purpose:</strong> {{ visitor.details?.purpose }}</p>

                <!-- List all visitors in this acknowledgement -->
                <div v-if="visitor.list_visitors.length">
                    <p class="font-bold mt-2">Other Visitors:</p>
                    <ul class="list-disc pl-5">
                        <li v-for="v in visitor.list_visitors" :key="v.id">
                            {{ v.visitor_name }} ({{ v.visitor_type }}) -
                            {{ v.visitor_company }}
                        </li>
                    </ul>
                </div>

                <!-- Staff Form -->
                <div class="space-y-4 mt-4">
                    <div>
                        <Label for="staffName">Staff Name</Label>
                        <Input
                            id="staffName"
                            v-model="staffName"
                            placeholder="Enter your name"
                        />
                    </div>
                    <div>
                        <Label for="staffId">Staff ID</Label>
                        <Input
                            id="staffId"
                            v-model="staffId"
                            placeholder="Enter your staff ID"
                        />
                    </div>
                </div>

                <DialogFooter class="mt-4">
                    <Button variant="secondary" @click="showQrCodeModal = false"
                        >Cancel</Button
                    >
                    <Button @click="submitVerification">Verify</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminAuthenticatedLayout>
</template>

<script lang="ts">
const visitorIdInput = ref(""); // bind input for visitor ID
</script>
