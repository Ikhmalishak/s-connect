<script setup lang="ts">
import { ref } from "vue";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogFooter,
} from "@/components/ui/dialog";
import axios from "axios";
import DialogTitle from "@/components/ui/dialog/DialogTitle.vue";
import DialogDescription from "@/components/ui/dialog/DialogDescription.vue";

const props = defineProps<{
    show: boolean;
    userId: number;
}>();

const emit = defineEmits(["close", "deleted"]);

const loading = ref(false);

async function confirmDelete() {
    if (!props.userId) return;
    loading.value = true;

    try {
        await axios.delete(`/delete-user/${props.userId}`);
        emit("deleted");
        emit("close");
    } catch (error) {
        console.error("Delete failed:", error);
    } finally {
        loading.value = false;
    }
}
</script>

<template>
<Dialog :open="props.show" @update:open="(val) => { if (!val) emit('close') }">
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Confirm Delete?</DialogTitle>
            <DialogDescription class="text-sm text-gray-500">
                Are you sure you want to delete this user?
            </DialogDescription>
        </DialogHeader>

        <DialogFooter>
            <Button variant="outline" @click="emit('close')">Cancel</Button>
            <Button
                variant="destructive"
                :disabled="loading"
                @click="confirmDelete"
            >
                {{ loading ? "Deleting..." : "Delete" }}
            </Button>
        </DialogFooter>
    </DialogContent>
</Dialog>

</template>
