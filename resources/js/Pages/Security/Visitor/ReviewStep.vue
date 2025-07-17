<script setup lang="ts">
import { ref } from "vue";
import { CheckCircle } from "lucide-vue-next";

const props = defineProps<{
  values: any;
  videoEnded: boolean;
  securityGuidelinesConfirmed: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:security-guidelines-confirmed', value: boolean): void;
}>();

const hasScrolledToBottom = ref(false);

const handleScroll = (e: Event) => {
  const el = e.target as HTMLElement;
  if (el.scrollHeight - el.scrollTop <= el.clientHeight + 10) {
    hasScrolledToBottom.value = true;
  }
};

const handleConfirmChange = (checked: boolean) => {
  emit('update:security-guidelines-confirmed', checked);
};
</script>

<template>
  <div>
    <h3 class="text-lg font-semibold mb-4">Review & Submit</h3>
    
    <div class="mb-6">
      <h4 class="font-medium mb-2">Visitor Information</h4>
      <div class="bg-gray-50 p-4 rounded">
        <div v-for="(visitor, i) in values.visitors" :key="i" class="mb-4">
          <p><strong>Visitor {{ i + 1 }}:</strong> {{ visitor.visitor_name }}</p>
          <p>ID: {{ visitor.id_type }} - {{ visitor.id_number }}</p>
          <p>Phone: {{ visitor.phone_number }}</p>
        </div>
      </div>
    </div>

    <div class="mb-6">
      <h4 class="font-medium mb-2">Visit Details</h4>
      <div class="bg-gray-50 p-4 rounded">
        <p><strong>Site:</strong> {{ values.site }}</p>
        <p><strong>Purpose:</strong> {{ values.purpose }}</p>
        <p v-if="values.person_to_meet"><strong>Person to Meet:</strong> {{ values.person_to_meet }}</p>
      </div>
    </div>

    <div class="h-64 overflow-y-auto border p-4 rounded mb-4" @scroll="handleScroll">
      <h3 class="text-lg font-semibold mb-2">Security Guidelines</h3>
      <p class="mb-2">1. Please wear your visitor tag at all times while on premises.</p>
      <p class="mb-2">2. Photography and video recording are strictly prohibited unless authorized.</p>
      <p class="mb-2">3. You must be escorted by an employee at all times.</p>
      <p class="mb-2">4. Emergency exits are marked clearly in all buildings.</p>
      <p class="mb-2">5. Your visit may be terminated if any guideline is violated.</p>
      <p v-for="i in 20" :key="i" class="mb-2">Additional policy content line {{ i }}.</p>
    </div>

    <div v-if="hasScrolledToBottom" class="flex items-center gap-2 mb-4">
      <input
        type="checkbox"
        id="confirm"
        :checked="securityGuidelinesConfirmed"
        @change="handleConfirmChange(($event.target as HTMLInputElement).checked)"
        class="w-5 h-5"
      />
      <label for="confirm" class="text-sm">
        I have read and understood the security guidelines.
      </label>
    </div>

    <div v-if="videoEnded" class="flex items-center gap-2 text-green-600">
      <CheckCircle class="h-5 w-5" />
      <span>Security video completed</span>
    </div>
  </div>
</template>