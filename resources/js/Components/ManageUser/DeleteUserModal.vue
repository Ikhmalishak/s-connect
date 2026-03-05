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
    user: number | null;
}>();

const emit = defineEmits(["close", "deleted"]);
const successMessage = ref("");
const errorMessage = ref("");
const loading = ref(false);

async function confirmDelete() {
    if (!props.user) return;
    loading.value = true;
    successMessage.value = "";
    errorMessage.value = "";

    try {
        await axios.delete(`/admin/delete-user/${props.user}`);
        successMessage.value = "User deleted successfully!";
        emit("deleted");

        // Optional: auto-close after 1s
        setTimeout(() => emit("close"), 1000);
    } catch (error: any) {
        console.error("Delete failed:", error);
        errorMessage.value =
            error.response?.data?.message || "Failed to delete user.";
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <Dialog
        :open="props.show"
        @update:open="
            (val) => {
                if (!val) emit('close');
            }
        "
    >
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Confirm Delete?</DialogTitle>
                <DialogDescription class="text-sm text-gray-500">
                    Are you sure you want to delete this user?
                </DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <div>
                    <p
                        v-if="successMessage"
                        class="text-green-600 text-sm mb-2"
                    >
                        {{ successMessage }}
                    </p>
                    <p v-if="errorMessage" class="text-red-600 text-sm mb-2">
                        {{ errorMessage }}
                    </p>
                </div>
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
