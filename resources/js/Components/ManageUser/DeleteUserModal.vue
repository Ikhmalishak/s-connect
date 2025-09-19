<script setup lang="ts">
import { ref } from "vue";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogFooter } from "@/components/ui/dialog";
import axios from "axios";

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
  <Dialog :open="props.show" @close="() => emit('close')">
    <DialogContent>
      <DialogHeader>
        <h3 class="text-lg font-semibold">Confirm Delete</h3>
        <p class="text-sm text-gray-500">Are you sure you want to delete this user?</p>
      </DialogHeader>

      <DialogFooter>
        <Button variant="outline" @click="emit('close')">Cancel</Button>
        <Button variant="destructive" :disabled="loading" @click="confirmDelete">
          {{ loading ? "Deleting..." : "Delete" }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
