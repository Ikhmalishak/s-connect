<script setup>
import { ref } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import { Link } from "@inertiajs/vue3";
import AppSidebar from "@/components/AppSidebar.vue";
import {
    SidebarInset,
    SidebarProvider,
    SidebarTrigger,
} from "@/components/ui/sidebar";
import {
    Breadcrumb,
    BreadcrumbPage,
    BreadcrumbSeparator,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
} from "@/components/ui/breadcrumb";
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

import { usePage } from "@inertiajs/vue3";

const page = usePage();
const user = page.props.auth.user;
console.log(user.email);
</script>

<template>
    <SidebarProvider>
        <AppSidebar />
        <SidebarInset class="flex flex-col min-h-screen overflow-hidden">
            <header
                class="sticky top-0 z-10 h-16 bg-background border-b px-4 flex items-center gap-2"
            >
                <SidebarTrigger class="-ml-1" />
                <Separator orientation="vertical" class="mr-2 h-4" />
                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem class="hidden md:block">
                            <BreadcrumbLink href="#">
                                Building Your Application
                            </BreadcrumbLink>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator class="hidden md:block" />
                        <BreadcrumbItem>
                            <BreadcrumbPage>Data Fetching</BreadcrumbPage>
                        </BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
                <div class="absolute inset-y right-3">
                    <DropdownMenu>
                        <DropdownMenuTrigger
                            ><Avatar class="w-10 h-10">
                                <AvatarImage
                                    src="/assets/skp.jpg"
                                    alt="My Avatar"
                                    class="rounded-full object-cover"
                                />
                                <AvatarFallback>CN</AvatarFallback>
                            </Avatar></DropdownMenuTrigger
                        >
                        <DropdownMenuContent>
                            <DropdownMenuLabel>Hi {{ user.name }}</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <!--buat as child biar sata component render as link-->
                            <DropdownMenuItem asChild>
                                <Link href="/profile"> Profile </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <Link href="/logout" method="post"> Logout </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>
            <div class="flex-1 overflow-y-auto p-6">
                <slot />
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>
