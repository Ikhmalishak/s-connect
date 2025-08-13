<script setup lang="ts">
import { ref, watch } from 'vue';

interface Visitor {
    id: number;
    visitor_name: string;
    purpose: string;
    time_in: string;
    remarks: string;
    gate_pass: {
        pass_number: string;
    };
}

const props = defineProps<{
    show: boolean;
    visitors: Visitor | null;
}>();

const emit = defineEmits(["close", "updateRemarks"]);

// Local state for editable remarks
const editableRemarks = ref('');
const isEditing = ref(false);
const isSaving = ref(false);

// Watch for changes in visitors prop to update local state
watch(() => props.visitors?.remarks, (newRemarks) => {
    if (newRemarks !== undefined) {
        editableRemarks.value = newRemarks || '';
    }
}, { immediate: true });

// Reset editing state when modal closes
watch(() => props.show, (show) => {
    if (!show) {
        isEditing.value = false;
        isSaving.value = false;
    }
});

const startEditing = () => {
    isEditing.value = true;
};

const cancelEditing = () => {
    editableRemarks.value = props.visitors?.remarks || '';
    isEditing.value = false;
};

const saveRemarks = async () => {
    isSaving.value = true;
    try {
        // Emit the update event to parent component
        emit('updateRemarks', {
            visitorId: props.visitors.id,
            remarks: editableRemarks.value
        });
        isEditing.value = false;
    } catch (error) {
        console.error('Failed to save remarks:', error);
    } finally {
        isSaving.value = false;
    }
};
</script>

<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="show"
        class="fixed inset-0 bg-black/50 backdrop-blur-md flex items-center justify-center z-[9999]"
        @click.self="emit('close')"
      >
        <Transition name="modal-scale" appear>
          <div
            v-if="show"
            class="bg-white p-6 rounded-lg shadow-lg w-[80%] max-w-2xl max-h-[80vh] overflow-y-auto"
          >
            <!-- Header -->
            <div class="flex justify-between items-center mb-6 border-b pb-3">
              <h2 class="text-xl font-bold text-red-700">
                Visitor Details
              </h2>
              <button
                @click="emit('close')"
                class="text-gray-500 hover:text-gray-700 text-2xl leading-none"
              >
                ×
              </button>
            </div>

            <!-- Form -->
            <form class="space-y-5" @submit.prevent="saveRemarks">
              <!-- Visitor Name -->
              <div>
                <label class="block font-semibold mb-1 text-gray-700">
                  Visitor Name
                </label>
                <input
                  type="text"
                  :value="visitors.visitor_name || 'N/A'"
                  disabled
                  class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-gray-500 shadow-sm focus:outline-none disabled:opacity-100"
                />
              </div>

              <!-- Remarks -->
              <div>
                <div class="flex justify-between items-center mb-1">
                  <label class="block font-semibold text-gray-700">
                    Remarks
                  </label>
                  <button
                    v-if="!isEditing"
                    type="button"
                    @click="startEditing"
                    class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                  >
                    Edit
                  </button>
                </div>

                <!-- View mode -->
                <textarea
                  v-if="!isEditing"
                  :value="visitors.remarks || 'No remarks'"
                  disabled
                  rows="3"
                  class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-gray-500 shadow-sm disabled:opacity-100 resize-none"
                ></textarea>

                <!-- Edit mode -->
                <div v-else class="space-y-3">
                  <textarea
                    v-model="editableRemarks"
                    rows="3"
                    placeholder="Enter remarks..."
                    class="w-full rounded-md border border-blue-300 focus:border-blue-500 focus:ring focus:ring-blue-200 px-3 py-2 resize-none"
                  ></textarea>

                  <!-- Action buttons -->
                  <div class="flex gap-2">
                    <button
                      type="submit"
                      :disabled="isSaving"
                      class="px-4 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700 disabled:opacity-50 flex items-center gap-2"
                    >
                      <span v-if="isSaving">Saving...</span>
                      <span v-else>Save</span>
                    </button>
                    <button
                      type="button"
                      @click="cancelEditing"
                      :disabled="isSaving"
                      class="px-4 py-2 bg-gray-500 text-white text-sm rounded hover:bg-gray-600 disabled:opacity-50"
                    >
                      Cancel
                    </button>
                  </div>
                  <div>Click 'Save' to apply your changes.</div>
                </div>
              </div>
            </form>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>


<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.modal-scale-enter-active,
.modal-scale-leave-active {
    transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.modal-scale-enter-from,
.modal-scale-leave-to {
    opacity: 0;
    transform: perspective(1000px) rotateX(-90deg) scale(0.3) translateY(-200px);
}
</style>