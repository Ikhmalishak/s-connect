<script setup>
import { Link } from "@inertiajs/vue3";
</script>

<template>
    <!-- header -->
    <nav class="bg-white border-b shadow-sm">
        <div
            class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center"
        >
            <Link href="/" class="flex items-center space-x-2">
                <div
                    class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center"
                >
                    <span class="text-white font-bold text-sm">S</span>
                </div>
                <span class="text-xl font-bold text-gray-900">S Connect</span>
            </Link>

            <div class="hidden md:flex space-x-4">
                <Link
                    href="/"
                    class="px-4 py-2 text-gray-700 hover:text-blue-600"
                    >Home</Link
                >
                <Link
                    href="/forms"
                    class="px-4 py-2 text-gray-700 hover:text-blue-600"
                    >Forms</Link
                >
                <Link
                    href="/login"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                    >Login</Link
                >
            </div>
        </div>
    </nav>

    <!-- Company Description Section -->
    <section class="bg-gray-50 py-12">
        <div
            class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row items-center gap-8"
        >
            <!-- Image -->
            <img
                src="https://www.google.com/url?sa=i&url=https%3A%2F%2Fwww.nst.com.my%2Fbusiness%2Fcorporate%2F2025%2F03%2F1183815%2Fskp-resources-poised-strong-growth-new-customer-acquisitions&psig=AOvVaw0S4tbWvYppanQJtakkFeZL&ust=1751535465806000&source=images&cd=vfe&opi=89978449&ved=0CBQQjRxqFwoTCKDOoJ7wnY4DFQAAAAAdAAAAABAK"
                alt="Company"
                class="w-full md:w-1/2 rounded-lg shadow"
            />

            <!-- Text Content -->
            <div class="w-full md:w-1/2">
                <h2 class="text-2xl font-bold text-gray-900">
                    About Our Company
                </h2>
                <p class="mt-4 text-gray-700">
                    S Connect is an integrated internal system designed to
                    digitize and streamline all company processes. From form
                    submissions to workflow management, our goal is to make your
                    work more efficient and organized.
                </p>
                <p class="mt-2 text-gray-700">
                    In the first phase, we focus on enabling digital request
                    forms and providing a modern, user-friendly interface for
                    employees.
                </p>
            </div>
        </div>
    </section>
</template>
