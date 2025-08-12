<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { Card } from "@/components/ui/card";
import { Link, usePage } from "@inertiajs/vue3";
import AppSidebar from "@/components/AppSidebar.vue";
import {
    SidebarInset,
    SidebarProvider,
    SidebarTrigger,
} from "@/components/ui/sidebar";
import { Separator } from "@/components/ui/separator";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

const showingMobileMenu = ref(false);

const page = usePage();
const user = page.props.auth.user;

// Idle state
const isIdle = ref(false);
let idleTimer = null;
const idleLimit = 5 * 60 * 1000; // 5 minutes

function resetIdleTimer() {
    clearTimeout(idleTimer);
    isIdle.value = false;
    idleTimer = setTimeout(() => {
        isIdle.value = true;
    }, idleLimit);
}

onMounted(() => {
    resetIdleTimer();
    window.addEventListener("mousemove", resetIdleTimer);
    window.addEventListener("keydown", resetIdleTimer);
    window.addEventListener("click", resetIdleTimer);
});

onBeforeUnmount(() => {
    window.removeEventListener("mousemove", resetIdleTimer);
    window.removeEventListener("keydown", resetIdleTimer);
    window.removeEventListener("click", resetIdleTimer);
    clearTimeout(idleTimer);
});
</script>

<template>
    <SidebarProvider :defaultOpen="false">
        <AppSidebar />
        <!-- Apply blur to everything when idle -->
        <SidebarInset
            class="flex flex-col min-h-screen overflow-hidden relative transition duration-300"
            :class="{ 'blur-md': isIdle }"
        >
            <header
                class="sticky top-0 z-10 h-16 bg-gray-200 border-b px-4 flex items-center gap-2"
            >
                <SidebarTrigger class="-ml-1" />
                <Separator orientation="vertical" class="mr-2 h-4" />
                <slot name="breadcrumb" />

                <div
                    class="flex flex-row items-center absolute inset-y right-3"
                >
                    <div class="w-20 mr-4">
                        <img src="/assets/skpLogo.png" alt="My Avatar" />
                    </div>
                    <DropdownMenu>
                        <DropdownMenuTrigger>
                            <Avatar class="w-10 h-10">
                                <AvatarImage
                                    src="/assets/skp.jpg"
                                    alt="My Avatar"
                                    class="rounded-full object-cover"
                                />
                                <AvatarFallback>CN</AvatarFallback>
                            </Avatar>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent>
                            <DropdownMenuLabel
                                >Hi {{ user.name }}</DropdownMenuLabel
                            >
                            <DropdownMenuSeparator />
                            <DropdownMenuItem asChild>
                                <Link href="/profile"> Profile </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href="/logout" method="post">
                                    Logout
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>
            <div class="flex-1 overflow-y-auto p-4">
                <slot />
            </div>
        </SidebarInset>
        <!-- Idle overlay ABOVE blur -->
        <div
            v-if="isIdle"
            class="absolute inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm"
        >
            <Card class="w-full max-w-md p-6 text-center shadow-lg">
                <h2 class="text-2xl font-semibold mb-2">You’ve been idle</h2>
                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    It’s been a while since your last activity.
                </p>
            </Card>
        </div>
    </SidebarProvider>
</template>
