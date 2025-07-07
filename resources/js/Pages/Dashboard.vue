<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
        </template>

        <!-- <div class="py-0">
            <div class="bg-blue-500 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">You're logged in!</div>
                </div>
            </div>
        </div> -->

        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="aspect-video rounded-xl bg-muted/50" />
                <div class="aspect-video rounded-xl bg-muted/50" />
                <div class="aspect-video rounded-xl bg-muted/50" />
            </div>
            <div
                class="min-h-[100vh] flex-1 rounded-xl bg-muted/50 md:min-h-min"
            />
        </div>
  

        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="aspect-video rounded-xl bg-muted/50" />
                <div class="aspect-video rounded-xl bg-muted/50" />
                <div class="aspect-video rounded-xl bg-muted/50" />
            </div>
            <div
                class="min-h-[100vh] flex-1 rounded-xl bg-muted/50 md:min-h-min"
            />
        </div>       
        
                <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="aspect-video rounded-xl bg-muted/50" />
                <div class="aspect-video rounded-xl bg-muted/50" />
                <div class="aspect-video rounded-xl bg-muted/50" />
            </div>
            <div
                class="min-h-[100vh] flex-1 rounded-xl bg-muted/50 md:min-h-min"
            />
        </div>        
    </AuthenticatedLayout>
</template>
