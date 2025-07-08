<script setup lang="ts">
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import axios from "axios";
import { router } from "@inertiajs/vue3";
import { Input } from "@/components/ui/input";
import { ref, computed } from "vue";

interface Props {
    data: VisitorForm[];
}

interface VisitorForm {
    id: number;
    visitor_name: string;
    vehicle_number: string;
    time_register: string;
    time_in: string;
    time_out: string;
    visitor_company: {
        id: number;
        name: string;
    };
    reasons: string;
    ic_number: string;
    pass_number: string;
    phone_number: string;
    created_at?: string;
    updated_at?: string;
}

const { props } = usePage();
const visitorForms = props.data as VisitorForm[];

const searchQuery = ref("");

const filteredVisitors = computed(() => {
    if (!searchQuery.value) return visitorForms;
    return visitorForms.filter((visitor) =>
        visitor.visitor_name
            .toLowerCase()
            .includes(searchQuery.value.toLowerCase())
    );
});
console.log(filteredVisitors);

//function to mask ic number
function maskIC(ic: string) {
    if (!ic) return "";
    // Keep last 5 digits, mask the rest with *
    const visiblePart = ic.slice(-6);
    const maskedPart = "*".repeat(Math.max(0, ic.length - 6));
    return `${maskedPart}${visiblePart}`;
}

async function checkIn(id: number) {
    try {
        await axios.post(`/visitor/${id}/check-in`);
        router.visit(window.location.pathname);
    } catch (error) {
        console.error(error);
    }
}

async function checkOut(id: number) {
    try {
        await axios.post(`/visitor/${id}/check-out`);
        router.visit(window.location.pathname);
    } catch (error) {
        console.error(error);
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <Card class="p-5">
            <div class="flex space-x-4 justify-between mb-4">
                <div class="flex flex-row space-x-2">
                    <Input
                        v-model="searchQuery"
                        class="w-400"
                        placeholder="Search by name..."
                    />
                </div>
                <Button as-child>
                    <a href="/visitor/form">New Visitor</a>
                </Button>
            </div>

            <Table class="overflow-auto max-h-[400px] overflow-y-auto">
                <TableCaption>A list of all visitor records.</TableCaption>
                <TableHeader>
                    <TableRow>
                        <TableHead>Visitor Name</TableHead>
                        <TableHead>Vehicle Number</TableHead>
                        <TableHead>Time Register</TableHead>
                        <TableHead>Time In</TableHead>
                        <TableHead>Time Out</TableHead>
                        <TableHead>Company</TableHead>
                        <TableHead>Reasons</TableHead>
                        <TableHead>IC Number</TableHead>
                        <TableHead>Pass Number</TableHead>
                        <TableHead>Phone Number</TableHead>
                        <TableHead>Action</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="visitor in filteredVisitors"
                        :key="visitor.id"
                    >
                        <TableCell>{{ visitor.visitor_name }}</TableCell>
                        <TableCell>{{ visitor.vehicle_number }}</TableCell>
                        <TableCell>{{ visitor.time_register }}</TableCell>
                        <TableCell>
                            <template v-if="visitor.time_in">
                                {{ visitor.time_in }}
                            </template>
                            <template v-else>
                                <Button size="sm" @click="checkIn(visitor.id)">
                                    Check In
                                </Button>
                            </template>
                        </TableCell>
                        <TableCell>
                            <template v-if="visitor.time_out">
                                {{ visitor.time_out }}
                            </template>
                            <template v-else>
                                <Button size="sm" @click="checkOut(visitor.id)">
                                    Check Out
                                </Button>
                            </template>
                        </TableCell>
                        <TableCell>{{
                            visitor.visitor_company?.name
                        }}</TableCell>
                        <TableCell>{{ visitor.reasons }}</TableCell>
                        <TableCell>{{ maskIC(visitor.ic_number) }}</TableCell>
                        <TableCell>{{ visitor.pass_number }}</TableCell>
                        <TableCell>{{ visitor.phone_number }}</TableCell>
                        <TableCell
                            ><Button as-child variant="outline" size="sm">
                                <a :href="`/visitor/${visitor.id}/edit`"
                                    >Edit</a
                                >
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </Card>
    </AuthenticatedLayout>
</template>
