<script lang="ts">
export const description = "A form for update password if the password expired";
</script>

<script setup lang="ts">
import PasswordExpiredForm from "@/components/PasswordExpiredForm.vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const reason = (page.props as any).flash?.reason;
</script>

<template>
    <!-- <Head title="Register" /> -->
    <div
        class="flex min-h-svh flex-col items-center justify-center bg-muted p-6 md:p-10"
    >
        <div class="w-full max-w-sm md:max-w-3xl">
            <PasswordExpiredForm :reason="reason" />
        </div>
    </div>
</template>
