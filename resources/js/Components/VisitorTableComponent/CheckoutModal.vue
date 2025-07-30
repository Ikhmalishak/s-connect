<script setup lang="ts">
import { ref, watch, nextTick, onMounted } from "vue";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import axios from "axios";

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits(["close", "refresh"]);

const scannedPass = ref("");
const scannerInput = ref<any>(null);
const isLoading = ref(false);
const message = ref("");

// Enhanced focus function for reliability
const focusInput = async () => {
    await nextTick();
    setTimeout(() => {
        // Handle different possible structures for UI library components
        let inputElement = null;
        
        if (scannerInput.value) {
            // Try direct access first (if it's already an input)
            if (scannerInput.value.focus && typeof scannerInput.value.focus === 'function') {
                inputElement = scannerInput.value;
            }
            // Try accessing $el property (Vue component)
            else if (scannerInput.value.$el) {
                inputElement = scannerInput.value.$el.tagName === 'INPUT' 
                    ? scannerInput.value.$el 
                    : scannerInput.value.$el.querySelector('input');
            }
            // Try direct querySelector on the element
            else if (scannerInput.value.querySelector) {
                inputElement = scannerInput.value.querySelector('input');
            }
        }
        
        if (inputElement && typeof inputElement.focus === 'function') {
            inputElement.focus();
        }
    }, 100);
};

// ✅ Watch modal state to focus input and reset message
watch(() => props.show, async (isVisible) => {
    if (isVisible) {
        message.value = "";       // reset message
        scannedPass.value = "";   // reset scanned text
        focusInput();             // focus input reliably
    } else {
        message.value = "";       // clear on close
        scannedPass.value = "";
    }
});

const handleScanCheckout = async () => {
    if (!scannedPass.value) return;
    isLoading.value = true;
    message.value = "";

    try {
        const response = await axios.post("/visitor/checkout-by-pass", {
            pass_number: scannedPass.value,
        });
        message.value = "✅ " + response.data.message;

        emit("refresh"); // refresh visitor list
    } catch (error: any) {
        message.value = "❌ " + (error.response?.data?.message || "Checkout failed");
    }

    scannedPass.value = "";
    isLoading.value = false;
    
    // Ensure focus returns to input after checkout for continuous scanning
    focusInput();
};

onMounted(() => {
    if (props.show) {
        focusInput();
    }
});
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50"
    >
        <div class="bg-white rounded-lg shadow-lg w-96 p-6">
            <h2 class="text-lg font-bold mb-4">Scanner Checkout</h2>

            <!-- Scanner input with autofocus -->
            <Input
                ref="scannerInput"
                v-model="scannedPass"
                @keyup.enter="handleScanCheckout"
                type="text"
                class="w-full"
                placeholder="Scan pass number..."
                autocomplete="off"
                autofocus
            />

            <div v-if="message" class="mt-2 text-sm">{{ message }}</div>

            <div class="mt-4 flex justify-end gap-2">
                <Button @click="emit('close')" variant="outline">Close</Button>
                <Button :disabled="isLoading" @click="handleScanCheckout">
                    {{ isLoading ? "Processing..." : "Checkout" }}
                </Button>
            </div>
        </div>
    </div>
</template>