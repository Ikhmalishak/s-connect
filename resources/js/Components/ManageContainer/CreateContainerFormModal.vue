<script setup lang="ts">
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/zod";
import * as z from "zod";
import { Button } from "@/components/ui/button";
import {
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from "@/components/ui/form";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { ref, watch } from "vue";
import axios from "axios";

const transportType = ref<"Truck" | "Container" | "">("");

const formSchema = toTypedSchema(
    z.object({
        transport_type: z.enum(["Truck", "Container"], {
            required_error: "Transport Type is required",
        }),
        transport_number: z.string().min(1, "Transport Number is required"),
        sku_number: z.string().min(1, "SKU Number is required"),
        model_project: z.string().min(1, "Model/Project is required"),
        forwarder: z.string().min(1, "Forwarder is required"),
        country: z.string().min(1, "Country is required"),
        work_order: z.string().min(1, "Work Order is required"),
        hauler: z.string().min(1, "Hauler is required"),
        high_security_seal: z.string().optional(),
        gps: z.string().optional(),
        fork_seal: z.string().optional(),
        temporary_seal: z.string().optional(),
    })
);

const form = useForm({
    validationSchema: formSchema,
});

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits(["close", "save"]);

watch(transportType, (val) => {
    if (val === "Truck") {
        form.setFieldValue("high_security_seal", "");
        form.setFieldValue("gps", "");
        form.setFieldValue("fork_seal", "");
        form.setFieldValue("temporary_seal", "");
    }
});

// Destructure resetForm from form context
const { handleSubmit, resetForm } = form;

const onSubmit = handleSubmit(async (values) => {
    console.log("Form Values:", values);
    try {
        const response = await axios.post("/containers/create", values);
        const data = response.data;
        console.log("Success:", data);

        // Close modal
        emit("close");

        // Reset form
        resetForm(); // <-- use this instead of form.reset()
    } catch (error) {
        console.error("Request failed:", error);
    }
});
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
                        class="bg-white p-6 rounded-lg shadow-[0_4px_20px_rgba(255,255,255,0.6)] w-[80%] max-w-2xl max-h-[80vh] overflow-y-auto"
                    >
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold text-red-700">
                                Create New Shipment Container
                            </h2>

                            <button
                                @click="emit('close')"
                                class="text-gray-500 hover:text-gray-700 text-xl font-bold"
                            >
                                ×
                            </button>
                        </div>

                        <form @submit.prevent="onSubmit">
                            <FormField
                                v-slot="{ componentField }"
                                name="transport_type"
                            >
                                <FormItem>
                                    <FormLabel>Transport Type</FormLabel>
                                    <Select
                                        v-bind="componentField"
                                        v-model="transportType"
                                    >
                                        <SelectTrigger>
                                            <SelectValue
                                                placeholder="Select Transport Type"
                                            />
                                        </SelectTrigger>
                                        <SelectContent class="z-[10001]">
                                            <SelectGroup>
                                                <SelectItem value="Truck"
                                                    >Truck</SelectItem
                                                >
                                                <SelectItem value="Container"
                                                    >Container</SelectItem
                                                >
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-slot="{ componentField }"
                                name="transport_number"
                            >
                                <FormItem>
                                    <FormLabel>Transport Number</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-slot="{ componentField }"
                                name="sku_number"
                            >
                                <!--User Name-->
                                <FormItem>
                                    <FormLabel>SKU Number</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-slot="{ componentField }"
                                name="model_project"
                            >
                                <!--User Name-->
                                <FormItem>
                                    <FormLabel>Model Project</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-slot="{ componentField }"
                                name="forwarder"
                            >
                                <!--User Name-->
                                <FormItem>
                                    <FormLabel>Forwarder</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-slot="{ componentField }"
                                name="hauler"
                            >
                                <!--User Name-->
                                <FormItem>
                                    <FormLabel>Hauler</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-slot="{ componentField }"
                                name="country"
                            >
                                <!--User Name-->
                                <FormItem>
                                    <FormLabel>Country</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-slot="{ componentField }"
                                name="work_order"
                            >
                                <!--User Name-->
                                <FormItem>
                                    <FormLabel>Work Order </FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-if="transportType === 'Container'"
                                v-slot="{ componentField }"
                                name="high_security_seal"
                            >
                                <FormItem>
                                    <FormLabel>High Security Seal</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-if="transportType === 'Container'"
                                v-slot="{ componentField }"
                                name="gps"
                            >
                                <FormItem>
                                    <FormLabel>GPS</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-if="transportType === 'Container'"
                                v-slot="{ componentField }"
                                name="fork_seal"
                            >
                                <FormItem>
                                    <FormLabel>Fork Seal</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <FormField
                                v-if="transportType === 'Container'"
                                v-slot="{ componentField }"
                                name="temporary_seal"
                            >
                                <FormItem>
                                    <FormLabel>Temporary Seal</FormLabel>
                                    <FormControl>
                                        <Input
                                            type="text"
                                            v-bind="componentField"
                                        />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>

                            <div
                                class="flex flex-row justify-between items-center"
                            >
                                <Button class="mt-4" type="submit">
                                    Submit
                                </Button>
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
