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
import {
    Breadcrumb,
    BreadcrumbPage,
    BreadcrumbSeparator,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
} from "@/components/ui/breadcrumb";
import { Card } from "@/components/ui/card";
interface RequestForm {
    id: number;
    user: {
        name: string;
        // You can add user.id, user.email etc. if you have them
    };
    request_date: string;
    type: string;
    description: string;
    justification: string;
    urgency: string;
    attachment: string | null;
    status: string;
    created_at?: string;
    updated_at?: string;
}

const { props } = usePage();
const requests = props.data as RequestForm[];
</script>

<template>
    <AuthenticatedLayout>
        <Card class="p-5 w-">
            <Table class="overflow-auto max-h-[400px] overflow-y-auto">
                <TableCaption>A list of your recent departments.</TableCaption>
                <TableHeader>
                    <TableRow>
                        <TableHead>User</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Type</TableHead>
                        <TableHead>Description</TableHead>
                        <TableHead>Justification</TableHead>
                        <TableHead>Urgency</TableHead>
                        <TableHead>Attachment</TableHead>
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="request in requests" :key="request.id">
                        <TableCell class="font-medium">
                            {{ request.user.name }}
                        </TableCell>
                        <TableCell>{{ request.request_date }}</TableCell>
                        <TableCell>{{ request.type }}</TableCell>
                        <TableCell>{{ request.description }}</TableCell>
                        <TableCell>{{ request.justification }}</TableCell>
                        <TableCell>{{ request.urgency }}</TableCell>
                        <TableCell>{{ request.attachment }}</TableCell>
                        <TableCell>{{ request.status }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </Card>
    </AuthenticatedLayout>
</template>
