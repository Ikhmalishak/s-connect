<script setup lang="ts">
import { ref, watch, nextTick, onMounted } from "vue";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import axios from "axios";
import { ScanQrCode } from "lucide-vue-next";
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from "@/components/ui/drawer";
import { Info, CircleX } from "lucide-vue-next";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";

const props = defineProps<{
    show: boolean;
}>();
const emit = defineEmits(["close", "refresh"]);

const scannedPass = ref("");
const scannerInput = ref<any>(null);
const isLoading = ref(false);
const message = ref("");

// ✅ New modal state
const resultTitle = ref("");
const resultAction = ref(null);
const resultStatus = ref("success"); // success or error

const drawerOpen = ref(false); // ✅ Controls drawer visibility

const focusInput = async () => {
    await nextTick();
    setTimeout(() => {
        let inputElement = null;
        if (scannerInput.value) {
            if (scannerInput.value.focus) inputElement = scannerInput.value;
            else if (scannerInput.value.$el) {
                inputElement =
                    scannerInput.value.$el.tagName === "INPUT"
                        ? scannerInput.value.$el
                        : scannerInput.value.$el.querySelector("input");
            } else if (scannerInput.value.querySelector) {
                inputElement = scannerInput.value.querySelector("input");
            }
        }
        if (inputElement && typeof inputElement.focus === "function") {
            inputElement.focus();
        }
    }, 100);
};

watch(
    () => props.show,
    async (isVisible) => {
        if (isVisible) {
            message.value = "";
            scannedPass.value = "";
            focusInput();
        } else {
            message.value = "";
            scannedPass.value = "";
        }
    }
);

const handleScanCheckout = async () => {
    if (!scannedPass.value) return;
    isLoading.value = true;
    message.value = "";

    setTimeout(async () => {
        try {
            const response = await axios.post("/visitor/scan-by-pass", {
                pass_number: scannedPass.value,
            });
            console.log("Checkout", response);
            message.value = "✅ " + response.data.message;

            resultAction.value = response.data.action;
            resultTitle.value = response.data.message;
            resultStatus.value = "success";

            emit("refresh");
        } catch (error: any) {
            const msg = error.response?.data?.message || "Checkout failed";
            message.value = "❌ " + msg;

            resultTitle.value = msg;
            resultStatus.value = "error";
        } finally {
            drawerOpen.value = true; // ✅ Open drawer no matter what
            scannedPass.value = "";
            isLoading.value = false;
            focusInput();
        }
    }, 800);
};
</script>

<template>
    <!-- Main Scanner Modal -->
    <div
        v-if="show"
        class="fixed inset-0 bg-black bg-opacity-40 backdrop-blur-sm flex items-center justify-center z-50"
    >
        <div
            class="bg-gray-100 rounded-2xl shadow-lg shadow-white max-w-4xl w-full h-80 p-8 relative"
        >
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger as-child>
                        <button
                            @click="emit('close')"
                            class="absolute top-4 right-4 text-gray-500 hover:text-gray-700"
                        >
                            <CircleX class="w-8 h-8" />
                        </button>
                    </TooltipTrigger>
                    <TooltipContent>
                        <p>Close</p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>

            <!-- ✅ Centered Icon & Title Side by Side -->
            <div class="flex items-center justify-center mb-6">
                <ScanQrCode class="w-12 h-12 text-gray-700 mr-3" />
                <h2 class="text-4xl font-bold">Scan to Check In / Out</h2>
            </div>

            <Input
                ref="scannerInput"
                v-model="scannedPass"
                @keyup.enter="handleScanCheckout"
                type="text"
                class="w-full h-28 text-3xl shadow-2xl bg-white placeholder:text-3xl text-black font-bold rounded-lg text-center"
                placeholder="Scan Pass QR..."
                autocomplete="off"
                autofocus
            />

            <div class="mt-6 flex justify-between gap-4">
                <div class="flex flex-row gap-1">
                    <Info class="w-12 h-12" />
                    <p class="text-xs max-w-xl">
                        This feature allows visitors to quickly
                        <strong>check in or check out</strong>
                        by scanning their pass ID at designated points. The
                        system automatically updates their status in real time,
                        ensuring accurate logs and smooth security compliance
                        for both arrival and departure.
                    </p>
                </div>
                <div>
                    <Button
                        :disabled="isLoading"
                        @click="handleScanCheckout"
                        class="text-lg px-6 py-3"
                    >
                        {{ isLoading ? "Processing..." : "Submit Scan" }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
    <div>
        <Drawer v-model:open="drawerOpen">
            <DrawerContent
                :class="{
                    'bg-green-400':
                        resultStatus === 'success' &&
                        resultAction == 'check-out',
                    'bg-yellow-400':
                        resultStatus === 'success' &&
                        resultAction === 'check-in',
                    'bg-red-500': resultStatus === 'error',
                }"
            >
                >
                <div class="mx-auto w-full max-w-3xl">
                    <DrawerHeader>
                        <DrawerTitle
                            class="text-4xl font-bold mb-4 text-center"
                            :class="
                                resultStatus === 'success' &&
                                resultAction === 'check-in'
                                    ? 'text-black'
                                    : 'text-white'
                            "
                        >
                            {{
                                resultStatus === "success"
                                    ? "✅ Success"
                                    : "❌ Error"
                            }}
                        </DrawerTitle>

                        <DrawerDescription
                            class="text-xl font-medium text-center"
                            :class="
                                resultStatus === 'success' &&
                                resultAction === 'check-in'
                                    ? 'text-black'
                                    : 'text-white'
                            "
                        >
                            {{ resultTitle }}
                        </DrawerDescription>
                    </DrawerHeader>
                    <DrawerFooter>
                        <div class="flex justify-center w-full">
                            <Button
                                class="max-w-xs w-full"
                                @click="drawerOpen = false"
                            >
                                Close
                            </Button>
                        </div>
                    </DrawerFooter>
                </div>
            </DrawerContent>
        </Drawer>
    </div>
</template>
