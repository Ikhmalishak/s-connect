<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Card } from "@/components/ui/card";
import {
    Breadcrumb,
    BreadcrumbPage,
    BreadcrumbSeparator,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
} from "@/components/ui/breadcrumb";

// Tab management
const activeTab = ref<"types" | "sections" | "questions">("types");

const tabs = [
    { id: "types" as const, label: "Audit Types" },
    { id: "sections" as const, label: "Sections" },
    { id: "questions" as const, label: "Questions" },
];

// ─── IMPORT CHILD COMPONENTS ──────────────────────────────
import AuditTypeManager from "@/Components/ManageSafety/auditSetup/AuditTypeManager.vue";
import AuditSectionManager from "@/Components/ManageSafety/auditSetup/AuditSectionManager.vue";
import AuditQuestionManager from "@/Components/ManageSafety/auditSetup/AuditQuestionManager.vue";

const currentTime = ref(new Date());
let intervalId: ReturnType<typeof setInterval>;

const formattedDate = computed(() =>
    currentTime.value.toLocaleDateString("en-GB", {
        weekday: "long", year: "numeric", month: "long", day: "numeric",
    })
);
const formattedTime = computed(() => currentTime.value.toLocaleTimeString("en-GB"));

onMounted(() => {
    intervalId = setInterval(() => { currentTime.value = new Date(); }, 1000);
});
onUnmounted(() => { if (intervalId) clearInterval(intervalId); });
</script>

<template>
    <AuthenticatedLayout>
        <template #breadcrumb>
            <Breadcrumb>
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink href="/">EHS Management System</BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbPage>Audit Setup</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>
        </template>

        <Card class="shadow-lg shadow-opacity-30 p-2 mb-4 text-2xl font-bold flex items-center justify-between bg-gray-100">
            <div class="flex flex-row items-center">
                <img src="/assets/ss1.png" class="h-12 w-12" alt="" />
                <div>EHS Management System</div>
            </div>
            <div class="flex flex-row items-center gap-10">
                <div class="flex flex-row space-x-4 text-base font-normal text-gray-600 text-right">
                    <div>{{ formattedDate }}</div>
                    <div>{{ formattedTime }}</div>
                </div>
            </div>
        </Card>

        <!-- Tabs -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="flex space-x-4" aria-label="Tabs">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    class="px-5 py-3 text-sm font-semibold transition-all duration-200 border-b-2 -mb-[1px]"
                    :class="activeTab === tab.id
                        ? 'border-blue-600 text-blue-600 bg-blue-50 rounded-t-lg'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                >
                    {{ tab.label }}
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <AuditTypeManager v-if="activeTab === 'types'" />
        <AuditSectionManager v-else-if="activeTab === 'sections'" />
        <AuditQuestionManager v-else-if="activeTab === 'questions'" />
    </AuthenticatedLayout>
</template>
