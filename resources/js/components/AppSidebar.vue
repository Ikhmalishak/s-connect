<script setup>
import { ChevronRight } from "lucide-vue-next";
import SearchForm from '@/components/SearchForm.vue';
import VersionSwitcher from '@/components/VersionSwitcher.vue';
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible';
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
} from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const props = defineProps({
  side: { type: String, required: false },
  variant: { type: String, required: false },
  collapsible: { type: String, required: false },
  class: { type: null, required: false },
});

// This is sample data.
const data = {
  versions: ["1.0.1"],
  navMain: [
    {
      title: "Main",
      url: "#",
      items: [
        {
          title: "Dashboard",
          url: "/dashboard",
        },
        {
          title: "Project Structure",
          url: "/login",
        },
      ],
    },
    {
      title: "MIS",
      url: "#",
      items: [
        {
          title: "IT Request Form",
          url: "/table",
        },
        {
          title: "IT Change Request Form",
          url: "/table",
        },
      ],
    },
    {
      title: "Engineering",
      url: "#",
      items: [
        {
          title: "IT Request Form",
          url: "/table",
        },
        {
          title: "IT Change Request Form",
          url: "/table",
        },
      ],
    },
    {
      title: "Security",
      url: "#",
      items: [
        {
          title: "Visitor Form",
          url: "/visitor",
        },
      ],
    }
  ],
};
</script>

<template>
  <Sidebar v-bind="props">
    <SidebarHeader>
      <VersionSwitcher
        :versions="data.versions"
        :default-version="data.versions[0]"
      />
      <!-- <SearchForm /> -->
    </SidebarHeader>
    <SidebarContent class="gap-0">
      <Collapsible
        v-for="item in data.navMain"
        :key="item.title"
        :title="item.title"
        default-open
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
                  :is-active="page.url.startsWith(childItem.url)">
                    <a :href="childItem.url">{{ childItem.title }}</a>
                  </SidebarMenuButton>
                </SidebarMenuItem>
              </SidebarMenu>
            </SidebarGroupContent>
          </CollapsibleContent>
        </SidebarGroup>
      </Collapsible>
    </SidebarContent>
    <SidebarRail />
  </Sidebar>
</template>
