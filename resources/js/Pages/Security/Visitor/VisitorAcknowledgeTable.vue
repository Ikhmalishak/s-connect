<script setup lang="ts">
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { usePage, router } from "@inertiajs/vue3";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { ref, computed } from "vue";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

interface Visitor {
    id: number;
    visitor_name: string;
    vehicle_number: string;
    time_register: string;
    visitor_company: { id: number; name: string };
    pass_number: string;
    is_acknowledge: boolean;
}

const { props } = usePage();
const visitorForms = props.data as Visitor[];

const searchQuery = ref("");

const filteredVisitors = computed(() =>
    searchQuery.value
        ? visitorForms.filter((v) =>
              v.visitor_name
                  .toLowerCase()
                  .includes(searchQuery.value.toLowerCase())
          )
        : visitorForms
);

// Video modal
const showVideoModal = ref(false);
const activeVisitorId = ref<number | null>(null);
const isAcknowledging = ref(false);
const videoEnded = ref(false);

// One static video URL
const videoUrl = '/assets/short.mp4';
const videoPlayer = ref<HTMLVideoElement | null>(null);

function openVideoModal(id: number) {
    activeVisitorId.value = id;
    showVideoModal.value = true;
    videoEnded.value = false;
}

function closeVideoModal() {
    if (videoPlayer.value) {
        videoPlayer.value.pause();
        videoPlayer.value.currentTime = 0;
    }
    showVideoModal.value = false;
    activeVisitorId.value = null;
}

async function acknowledgeVisitor() {
    if (!activeVisitorId.value) return;
    try {
        isAcknowledging.value = true;
        await axios.post(`/visitor/${activeVisitorId.value}/acknowledge`);
        closeVideoModal();
        router.reload();
    } catch (err) {
        console.error(err);
    } finally {
        isAcknowledging.value = false;
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <Card class="p-5">
            <div class="flex justify-between mb-4">
                <Input
                    v-model="searchQuery"
                    class="w-72"
                    placeholder="Search by visitor name..."
                />
            </div>

            <Table class="overflow-auto max-h-[500px]">
                <TableCaption>All visitors.</TableCaption>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Company</TableHead>
                        <TableHead>Gate Pass</TableHead>
                        <TableHead>Time Registered</TableHead>
                        <TableHead>Is Acknowledge?</TableHead>
                        <TableHead>Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="v in filteredVisitors" :key="v.id">
                        <TableCell>{{ v.visitor_name }}</TableCell>
                        <TableCell>{{ v.visitor_company?.name }}</TableCell>
                        <TableCell>{{ v.pass_number }}</TableCell>
                        <TableCell>{{ v.time_register }}</TableCell>
                        <TableCell>
                            <span
                                :class="v.is_acknowledge ? 'text-green-600 font-semibold' : 'text-gray-500'"
                            >
                                {{ v.is_acknowledge ? 'Yes' : 'No' }}
                            </span>
                        </TableCell>
                        <TableCell>
                            <template v-if="v.is_acknowledge">
                                <span class="text-green-600 font-semibold">
                                    Finished Watching
                                </span>
                            </template>
                            <template v-else>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="openVideoModal(v.id)"
                                >
                                    Watch Video
                                </Button>
                            </template>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </Card>
    </AuthenticatedLayout>

    <!-- Modal -->
    <teleport to="body">
        <div
            v-if="showVideoModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70"
        >
            <!-- Clicking backdrop does nothing -->
            <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl p-2 relative">
                <video
                    ref="videoPlayer"
                    controls
                    autoplay
                    @ended="videoEnded = true"
                    class="w-full h-full md:h-80 rounded"
                >
                    <source :src="videoUrl" type="video/mp4" />
                    Your browser does not support the video tag.
                </video>
                <div class="flex justify-end mt-4 space-x-2">
                    <Button variant="secondary" @click="closeVideoModal">
                        Close
                    </Button>
                    <Button
                        :disabled="!videoEnded || isAcknowledging"
                        @click="acknowledgeVisitor"
                    >
                        <span v-if="isAcknowledging">Processing...</span>
                        <span v-else-if="!videoEnded">Watch Video to Enable</span>
                        <span v-else>I Acknowledge</span>
                    </Button>
                </div>
            </div>
        </div>
    </teleport>
</template>
