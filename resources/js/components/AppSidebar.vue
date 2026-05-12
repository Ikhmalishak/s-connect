<script setup>
import { ChevronRight } from "lucide-vue-next";
import VersionSwitcher from "@/components/VersionSwitcher.vue";
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from "@/components/ui/collapsible";
import {
    Sidebar,
    SidebarContent,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
} from "@/components/ui/sidebar";
import { usePage, Link } from "@inertiajs/vue3";

const page = usePage();

const props = defineProps({
    side: { type: String, required: false },
    variant: { type: String, required: false },
    collapsible: { type: String, required: false },
    class: { type: null, required: false },
    user: { type: Object, required: true },
});

// Extract permission names from the user
const userPermissionNames = props.user?.permissions?.map((p) => p) || [];

// Sample navigation structure
const navMain = [
    {
        title: "Home",
        items: [
            { title: "Dashboard", url: "/dashboard", permission: "public" },
        ],
        permission: "public",
    },
    {
        title: "Container Management System",
        items: [
            {
                title: "Overview",
                url: "/container/dashboard",
                permission: "container.access",
            },
            {
                title: "Approvals",
                url: "/container/approvals",
                permission: "container.approve",
            },
            {
                title: "Shipping Requirements",
                url: "/container/shipping-requirements",
                permission: "container.shipping.access",
            },
            {
                title: "Shipping Requirements Approvals",
                url: "/container/shipping-requirements-approvals",
                permission: "container.shipping.approve",
            },
            {
                title: "Archive Container Reports",
                url: "/container/archive",
                permission: "container.access",
            },
        ],
        permission: "container.access",
    },
    {
        title: "Visitor Management System",
        items: [
            {
                title: "Overview",
                url: "/visitor/dashboard",
                permission: "visitor.access",
            },
            // {
            //     title: "Report",
            //     url: "/visitor/report",
            //     permission: "visitor.report",
            // },
        ],
        permission: "visitor.access", // parent permission
    },
    {
        title: "EHS Management System",
        items: [
            {
                title: "Overview",
                url: "/safety/dashboard",
                permission: "visitor.access",
            },
            {
                title: "Manage PIC (EHS Audit)",
                url: "/safety/manage-pic",
                permission: "visitor.access",
            },
        ],
        permission: "visitor.access", // parent permission
    },
    {
        title: "Meeting Room Reservation",
        items: [
            {
                title: "Overview",
                url: "/room-reservation/dashboard",
                permission: "room-reservation.access",
            },
        ],
        permission: "room-reservation.access",
    },
    {
        title: "Setting",
        items: [
            {
                title: "Password Configuration",
                url: "/admin/get-password-policy-page",
                permission: "superadmin",
            },
            {
                title: "System Log",
                url: "/admin/system-log",
                permission: "superadmin",
            },
            {
                title: "Manage User",
                url: "/admin/manage-user",
                permission: "superadmin",
            },
        ],
        permission: "superadmin",
    },
];

// Filter nav items by user permissions (both parent and children)
const filteredNav = navMain
    .map((parent) => {
        const filteredItems = parent.items.filter((child) =>
            userPermissionNames.includes(child.permission),
        );
        if (filteredItems.length === 0) return null;
        return { ...parent, items: filteredItems };
    })
    .filter(Boolean);
</script>

<template>
    <Sidebar v-bind="props">
        <!-- Sidebar header -->
        <SidebarHeader class="p-0">
            <VersionSwitcher :versions="['1.0.1']" :default-version="'1.0.1'" />
        </SidebarHeader>

        <!-- Sidebar content -->
        <SidebarContent class="gap-0">
            <Collapsible
                v-for="item in filteredNav"
                :key="item.title"
                :title="item.title"
                class="group/collapsible"
            >
                <SidebarGroup>
                    <SidebarGroupLabel
                        as-child
                        class="group/label text-sm text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                    >
                        <CollapsibleTrigger>
                            {{ item.title }}
                            <ChevronRight
                                class="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-90"
                            />
                        </CollapsibleTrigger>
                    </SidebarGroupLabel>

                    <CollapsibleContent>
                        <SidebarGroupContent>
                            <SidebarMenu>
                                <SidebarMenuItem
                                    v-for="childItem in item.items"
                                    :key="childItem.title"
                                >
                                    <SidebarMenuButton
                                        as-child
                                        :is-active="
                                            page.url.startsWith(childItem.url)
                                        "
                                        class="hover:bg-blue-100"
                                    >
                                        <Link :href="childItem.url">{{
                                            childItem.title
                                        }}</Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            </SidebarMenu>
                        </SidebarGroupContent>
                    </CollapsibleContent>
                </SidebarGroup>
            </Collapsible>

            <!-- Fallback if no items are visible -->
            <div
                v-if="filteredNav.length === 0"
                class="p-4 text-center text-sm text-muted-foreground"
            >
                No menu items available for your role.
            </div>
        </SidebarContent>

        <SidebarRail />
    </Sidebar>
</template>
