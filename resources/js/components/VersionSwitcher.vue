<script setup>
import { Archive, Check, ChevronsUpDown, GalleryVerticalEnd } from "lucide-vue-next";

import { ref } from "vue";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar';

const props = defineProps({
  versions: { type: Array, required: true },
  defaultVersion: { type: String, required: true },
});

const selectedVersion = ref(props.defaultVersion);
</script>

<template>
  <SidebarMenu>
    <SidebarMenuItem>
      <DropdownMenu>
<DropdownMenuTrigger as-child>
  <Button variant="ghost" class="h-8 w-32 justify-between px-2">
    <div class="flex items-center gap-2">
      <img
        src="/assets/logo.png"
        alt="Logo"
        class="w-5 h-5"
      />
      <span class="font-semibold">S-Connect</span>
    </div>
    <ChevronDown class="ml-2 size-4 opacity-50" />
  </Button>
</DropdownMenuTrigger>
        <DropdownMenuContent
          class="w-[--reka-dropdown-menu-trigger-width]"
          align="start"
        >
          <DropdownMenuItem
            v-for="version in versions"
            :key="version"
            @select="selectedVersion = version"
          >
            v{{ version }}
            <Check v-if="selectedVersion === version" class="ml-auto" />
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </SidebarMenuItem>
  </SidebarMenu>
</template>
