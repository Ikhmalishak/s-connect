<script setup lang="ts">
import { useForm, useFieldArray } from "vee-validate";
import PaxSection from "../../../Components/PaxSection.vue";
import VideoModal from "../../../Components/VideoModal.vue";
import FormProgress from "@/Components/FormProgress.vue";
import VisitorFormHeader from "@/Components/VisitorFormHeader.vue";
import { toTypedSchema } from "@vee-validate/zod";
import * as z from "zod";
import { Button } from "@/components/ui/button";
import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Card } from "@/components/ui/card";
import axios from "axios";
import { useToast } from "@/components/ui/toast/use-toast";
import { Textarea } from "@/components/ui/textarea";
import { onMounted, ref, computed, watch,} from "vue";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { router, usePage } from "@inertiajs/vue3";

// Interfaces
interface Company {
    id: number;
    name: string;
}
interface PaxEntry {
    visitor_name: string;
    ic_number?: string;
    passport?: string;
    phone_number?: string;
}

// Zod schemas
const paxSchema = z.object({
    visitor_name: z.string().min(1, "Name is required"),
    id_type: z.enum(["IC", "Passport"]),
    id_number: z.string().min(1, "ID Number is required"),
    phone_number: z.string().min(1, "Phone number is required"),
});

const formSchema = toTypedSchema(
    z
        .object({
            visitor_name: z.string().min(1, "Visitor Name is required"),
            vehicle_number: z.string().optional(),
            site: z.string().min(1, "Site is required"),
            time_register: z.string().optional(),
            time_in: z.string().optional(),
            time_out: z.string().optional(),
            remarks: z.string().optional(),
            id_type: z.enum(["IC", "Passport"]),
            id_number: z.string().min(1, "ID Number is required"),
            pass_number: z.string().min(1, "Pass Number is required"),
            phone_number: z.string().min(1, "Phone Number is required"),
            visitor_company_id: z
                .string()
                .transform((val) => parseInt(val))
                .refine((val) => !isNaN(val), {
                    message: "Visitor company is required",
                }),
            purpose: z.string().min(1, "Purpose is required"),
            person_to_meet: z.string().optional(),
            pax: z.array(paxSchema).optional(),
            is_acknowledge: z.boolean().default(false),
        })
        .refine(
            (data) => {
                if (data.id_type === "IC") {
                    return /^\d{12}$/.test(data.id_number);
                }
                return true;
            },
            {
                message: "IC Number must be exactly 12 digits",
                path: ["id_number"],
            }
        )
        .refine(
            (data) => {
                if (data.id_type === "Passport") {
                    return /^[A-Z0-9]{6,20}$/.test(data.id_number);
                }
                return true;
            },
            {
                message: "Passport Number format is invalid",
                path: ["id_number"],
            }
        )
);

const { props } = usePage();
const visitor = props.visitor as any | undefined;
const { toast } = useToast();
const visitorCompany = ref<Company[]>([]);
const showVideoModal = ref(false); // NEW
const videoEnded = ref(false);

const { handleSubmit, setFieldValue, values, meta, errors, resetForm } =
    useForm({
        validationSchema: formSchema,
        initialValues: {
            visitor_name: visitor?.visitor_name ?? "",
            vehicle_number: visitor?.vehicle_number ?? "",
            site: visitor?.site ?? "",
            time_register: visitor?.time_register ?? "",
            time_in: visitor?.time_in ?? "",
            time_out: visitor?.time_out ?? "",
            remarks: visitor?.remarks ?? "",
            id_type: visitor?.id_type ?? "",
            id_number: visitor?.id_number ?? "",
            pass_number: visitor?.pass_number ?? "",
            phone_number: visitor?.phone_number ?? "",
            visitor_company_id: visitor?.visitor_company_id ?? undefined,
            purpose: visitor?.purpose ?? "",
            person_to_meet: visitor?.person_to_meet ?? "",
            is_acknowledge: visitor?.is_acknowledge ?? false,
        },
    });

const {
    fields: paxFields,
    push: addPax,
    remove: removePax,
} = useFieldArray("pax");

const paxCount = computed({
    get: () => paxFields.value.length,
    set: (newCount) => {
        const current = paxFields.value.length;
        if (newCount > current) {
            for (let i = current; i < newCount; i++) {
                addPax({
                    visitor_name: "",
                    id_type: "IC",
                    id_number: "",
                    phone_number: "",
                });
            }
        } else if (newCount < current) {
            for (let i = current - 1; i >= newCount; i--) {
                removePax(i);
            }
        }
    },
});

onMounted(async () => {
    try {
        const res = await axios.get("/listvisitorcompany");
        visitorCompany.value = res.data.visitor_company;
    } catch (error) {
        console.error("Error fetching companies:", error);
        toast({
            title: "Error",
            description: "Failed to load companies.",
            variant: "destructive",
        });
    }
});

const purposes = ["Visit", "Meeting", "Delivery", "Interview"];
const sites = ["Site 1", "Site 2", "Site 3", "Site 4"];

const onSubmit = handleSubmit(async (values) => {
    try {
        if (!visitor?.id) {
            setFieldValue("is_acknowledge", videoEnded.value);
        }

        if (visitor?.id) {
            await axios.post(`/visitor/${visitor.id}`, {
                ...values,
                _method: "PUT",
            });
            toast({ title: "Updated", description: "Visitor updated." });
        } else {
            await axios.post("/visitor/submit", values);
            toast({ title: "Created", description: "Visitor created." });

            // Reset form after successful creation
            resetForm({
                values: {
                    visitor_name: "",
                    vehicle_number: "",
                    site: "",
                    time_register: "",
                    time_in: "",
                    time_out: "",
                    remarks: "",
                    id_type: undefined,
                    id_number: "",
                    pass_number: "",
                    phone_number: "",
                    visitor_company_id: undefined,
                    purpose: "",
                    person_to_meet: "",
                    pax: [],
                    is_acknowledge: false,
                },
            });

            // Also reset your custom state
            videoEnded.value = false;
        }
    } catch (error) {
        console.error(error);
        toast({
            title: "Error",
            description: "An error occurred.",
            variant: "destructive",
        });
    }
});

function handleVideoEnded() {
    videoEnded.value = true;
    setFieldValue("is_acknowledge", true);
    showVideoModal.value = false; // Close modal
    toast({
        title: "Video Completed",
        description: "You can now submit the form.",
        variant: "success",
    });
}

const requiredFields = [
    "visitor_name",
    "id_type",
    "id_number",
    "pass_number",
    "phone_number",
    "visitor_company_id",
    "purpose",
];

// Add this computed property after your existing computed properties
const isFormValid = computed(() => {
    // Check if main form has validation errors
    if (Object.keys(errors.value).length > 0) {
        return false;
    }

    // Check main required fields
    const mainFieldsValid = requiredFields.every((field) => {
        const value = values[field as keyof typeof values];
        return value !== undefined && value !== null && value !== "";
    });

    // Check pax fields if they exist
    const paxFieldsValid =
        paxFields.value.length === 0 ||
        paxFields.value.every((field) => {
            const paxData = field.value as any;
            return (
                paxData.visitor_name &&
                paxData.id_type &&
                paxData.id_number &&
                paxData.phone_number
            );
        });

    return mainFieldsValid && paxFieldsValid;
});

</script>

<template>
    <VisitorFormHeader title="Visitor Registration Form" />
    <Card class="mt-4 mx-auto max-w-3xl w-full shadow-2xl shadow-opacity-30">
        <form @submit="onSubmit" class="grid gap-4 p-4">
            <div class="grid grid-cols-3 gap-4">
                <!-- Visitor Name -->
                <FormField v-slot="{ componentField }" name="visitor_name">
                    <FormItem>
                        <FormLabel>Visitor Name</FormLabel>
                        <FormControl>
                            <Input type="text" v-bind="componentField" />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- ID Type Select -->
                <FormField name="id_type" v-slot="{ componentField }">
                    <FormItem>
                        <FormLabel>ID Type</FormLabel>
                        <Select v-bind="componentField">
                            <FormControl>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select ID Type" />
                                </SelectTrigger>
                            </FormControl>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="IC"
                                        >Identification Card</SelectItem
                                    >
                                    <SelectItem value="Passport"
                                        >Passport</SelectItem
                                    >
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Single ID Number Input -->
                <FormField name="id_number" v-slot="{ componentField }">
                    <FormItem>
                        <FormLabel>
                            {{
                                values.id_type === "Passport"
                                    ? "Passport Number"
                                    : "IC Number"
                            }}
                        </FormLabel>
                        <FormControl>
                            <Input
                                v-bind="componentField"
                                placeholder="Enter ID Number"
                            />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                </FormField>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <!-- Phone Number -->
                <FormField v-slot="{ componentField }" name="phone_number">
                    <FormItem>
                        <FormLabel>Phone Number</FormLabel>
                        <FormControl>
                            <Input type="text" v-bind="componentField" />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Pass Number -->
                <FormField v-slot="{ componentField }" name="pass_number">
                    <FormItem>
                        <FormLabel>Pass Number</FormLabel>
                        <FormControl>
                            <Input type="text" v-bind="componentField" />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                </FormField>
            </div>

            <!-- Number of Pax - Now using standard HTML elements -->
            <div>
                <PaxSection
                    :paxFields="paxFields"
                    :paxCount="paxCount"
                    :maxPax="10"
                    @update:paxCount="(val) => (paxCount = val)"
                    @removePax="removePax"
                />
            </div>

            <div class="grid grid-cols-3 gap-4">
                <!-- Vehicle Number -->
                <FormField v-slot="{ componentField }" name="vehicle_number">
                    <FormItem>
                        <FormLabel>Vehicle Number</FormLabel>
                        <FormControl>
                            <Input type="text" v-bind="componentField" />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Site -->
                <FormField v-slot="{ componentField }" name="site">
                    <FormItem>
                        <FormLabel>Site</FormLabel>
                        <Select v-bind="componentField">
                            <FormControl>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Site" />
                                </SelectTrigger>
                            </FormControl>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem
                                        v-for="s in sites"
                                        :key="s"
                                        :value="s"
                                    >
                                        {{ s }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Visitor Company -->
                <FormField
                    v-slot="{ componentField }"
                    name="visitor_company_id"
                >
                    <FormItem>
                        <FormLabel>Visitor Company</FormLabel>
                        <Select v-bind="componentField">
                            <FormControl>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Company" />
                                </SelectTrigger>
                            </FormControl>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem
                                        v-for="company in visitorCompany"
                                        :key="company.id"
                                        :value="company.id.toString()"
                                    >
                                        {{ company.name }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <FormMessage />
                    </FormItem>
                </FormField>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <!-- Purpose -->
                <FormField v-slot="{ componentField }" name="purpose">
                    <FormItem>
                        <FormLabel>Purpose</FormLabel>
                        <Select v-bind="componentField">
                            <FormControl>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Purpose" />
                                </SelectTrigger>
                            </FormControl>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem
                                        v-for="p in purposes"
                                        :key="p"
                                        :value="p"
                                    >
                                        {{ p }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Person to Meet -->
                <FormField
                    v-if="values.purpose === 'Meeting'"
                    v-slot="{ componentField }"
                    name="person_to_meet"
                >
                    <FormItem>
                        <FormLabel>Person to Meet</FormLabel>
                        <FormControl>
                            <Input type="text" v-bind="componentField" />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Remarks -->
                <FormField v-slot="{ componentField }" name="remarks">
                    <FormItem>
                        <FormLabel>Remarks</FormLabel>
                        <FormControl>
                            <Textarea
                                v-bind="componentField"
                                class="h-[100px]"
                            />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                </FormField>
            </div>
            <div class="my-4 h-px bg-stone-200 shadow-2xl"></div>

            <div class="flex items-center gap-2 mt-4 relative group">
                <span class="text-sm"
                    >Please Click the video icon to start the security
                    briefing</span
                >

                <!-- Watch Video Button -->
                <button
                    type="button"
                    @click="showVideoModal = true"
                    :disabled="!isFormValid"
                    class="flex items-center gap-1 text-blue-600 hover:underline text-sm disabled:opacity-50 disabled:pointer-events-none"
                >
                    <img
                        src="/assets/icon/play.png"
                        alt="Play Icon"
                        class="w-4 h-4"
                    />
                    (Watch Video)
                </button>

                <!-- Tooltip when form is incomplete -->
                <div
                    v-if="!isFormValid"
                    class="absolute -bottom-5 left-0 text-xs text-red-600 hidden group-hover:block"
                >
                    Please fill in all required fields first
                </div>
            </div>

            <div>
                <!-- Checkbox (readonly) -->
                <input
                    type="checkbox"
                    :checked="videoEnded"
                    disabled
                    class="border-gray-300 rounded"
                />
                I hereby confirm that I have read and understood the SKP
                security guideline
            </div>

            <!-- Submit Button - Disabled until video ends for new visitors -->
            <Button
                class="mt-4"
                type="submit"
                :disabled="!videoEnded && !visitor?.id"
            >
                <span v-if="!videoEnded && !visitor?.id"
                    >Watch Video to Enable</span
                >
                <span v-else>{{ visitor?.id ? "Update" : "Create" }}</span>
            </Button>
        </form>
        <!-- Video Modal -->
        <VideoModal
            :visible="showVideoModal"
            @close="showVideoModal = false"
            @ended="handleVideoEnded"
        />
    </Card>
    <FormProgress :values="values" :requiredFields="requiredFields" />
</template>
