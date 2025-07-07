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
import { Button } from "@/components/ui/button"
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

//function to mask ic number
function maskIC(ic: string) {
    if (!ic) return "";
    // Keep last 5 digits, mask the rest with *
    const visiblePart = ic.slice(-6);
    const maskedPart = "*".repeat(Math.max(0, ic.length - 6));
    return `${maskedPart}${visiblePart}`;
}
</script>

<template>
    <AuthenticatedLayout>
        <Card class="p-5">
          <Button type="submit">Submit</Button>
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
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="visitor in visitorForms" :key="visitor.id">
                        <TableCell>{{ visitor.visitor_name }}</TableCell>
                        <TableCell>{{ visitor.vehicle_number }}</TableCell>
                        <TableCell>{{ visitor.time_register }}</TableCell>
                        <TableCell>{{ visitor.time_in }}</TableCell>
                        <TableCell>{{ visitor.time_out }}</TableCell>
                        <TableCell>{{
                            visitor.visitor_company?.name
                        }}</TableCell>
                        <TableCell>{{ visitor.reasons }}</TableCell>
                        <TableCell>{{ maskIC(visitor.ic_number) }}</TableCell>
                        <TableCell>{{ visitor.pass_number }}</TableCell>
                        <TableCell>{{ visitor.phone_number }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </Card>
    </AuthenticatedLayout>
</template>
