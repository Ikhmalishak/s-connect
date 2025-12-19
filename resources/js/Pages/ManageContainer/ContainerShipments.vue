<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ContainerShipmentTable from '@/Components/ManageContainer/ContainerShipmentTable.vue';
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <ContainerShipmentTable />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
