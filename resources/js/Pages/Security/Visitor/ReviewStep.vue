<script setup lang="ts">
import { ref } from "vue";
import { CheckCircle } from "lucide-vue-next";

const props = defineProps<{
    values: any;
    videoEnded: boolean;
    securityGuidelinesConfirmed: boolean;
    visitorType: string;
}>();

const emit = defineEmits<{
    (e: "update:security-guidelines-confirmed", value: boolean): void;
}>();

const hasScrolledToBottom = ref(false);
const contractorScrolled = ref(false);

const handleScroll = (e: Event) => {
    const el = e.target as HTMLElement;
    if (el.scrollHeight - el.scrollTop <= el.clientHeight + 10) {
        hasScrolledToBottom.value = true;
    }
};

const handleContractorScroll = (e: Event) => {
    const el = e.target as HTMLElement;
    if (el.scrollHeight - el.scrollTop <= el.clientHeight + 10) {
        contractorScrolled.value = true;
    }
};

const handleConfirmChange = (checked: boolean) => {
    emit("update:security-guidelines-confirmed", checked);
};
</script>

<template>
    <div>
        <pre>{{ props.visitorType }}</pre>
        <h3 class="text-lg font-semibold mb-4">Review & Submit</h3>

        <div
            class="h-64 overflow-y-auto border p-4 rounded mb-4"
            @scroll="handleScroll"
        >
            <h3 class="text-lg font-semibold mb-2">Security Guidelines</h3>
            <p class="mb-2">
                1. Please wear your visitor tag at all times while on premises.
            </p>
            <p class="mb-2">
                2. Photography and video recording are strictly prohibited
                unless authorized.
            </p>
            <p class="mb-2">
                3. You must be escorted by an employee at all times.
            </p>
            <p class="mb-2">
                4. Emergency exits are marked clearly in all buildings.
            </p>
            <p class="mb-2">
                5. Your visit may be terminated if any guideline is violated.
            </p>
            <p v-for="i in 20" :key="i" class="mb-2">
                Additional policy content line {{ i }}.
            </p>
        </div>

        <div
            v-if="visitorType === 'contractor'"
            class="h-64 overflow-y-auto border p-4 rounded mb-4"
            @scroll="handleContractorScroll"
        >
            <h3 class="text-lg font-semibold mb-2">Contractor Guidelines</h3>
            <section>
                <h2 class="text-xl font-semibold text-gray-800">
                    1.0 OBJECTIVE
                </h2>
                <p class="mt-2 text-gray-700">
                    The objective of this procedure is to provide guidance in
                    the selection, management, and monitoring of contractors.
                </p>
            </section>

            <!-- Scope -->
            <section class="mt-4">
                <h2 class="text-2xl font-semibold text-gray-800">2.0 SCOPE</h2>
                <p class="mt-2 text-gray-700">
                    This procedure applies to all company workplaces and covers
                    the selection, management and monitoring of service
                    provider/supplier/contractors associated with maintenance
                    and repair work. This procedure is not intended to apply to
                    capital works involving a tender or formal contract process
                    (see definitions below).
                </p>
            </section>

            <!-- Responsibilities -->
            <section class="mt-4">
                <h2 class="text-2xl font-semibold text-gray-800">
                    3.0 RESPONSIBILITIES
                </h2>

                <!-- 3.1 HOD and/or EHS Nominee -->
                <div class="mt-4">
                    <h3 class="text-xl font-medium text-gray-700">
                        3.1 Workplace HOD and/or EHS Nominee is responsible for:
                    </h3>
                    <ul
                        class="list-disc list-inside mt-2 text-gray-700 space-y-1"
                    >
                        <li>Maintaining a register of approved contractors.</li>
                        <li>
                            Inducting contractors in EHS requirements and
                            behavioural expectations while on site.
                        </li>
                        <li>
                            Familiarising contractors with their work
                            environment and the specific hazards they may be
                            potentially exposed to.
                        </li>
                        <li>
                            Investigating any hazards identified by all
                            contractor employees.
                        </li>
                        <li>
                            Acting on identified non-compliance of contractors
                            to EHS procedures.
                        </li>
                    </ul>
                </div>

                <!-- 3.2 Employees -->
                <div class="mt-4">
                    <h3 class="text-xl font-medium text-gray-700">
                        3.2 Employees are responsible for:
                    </h3>
                    <ul
                        class="list-disc list-inside mt-2 text-gray-700 space-y-1"
                    >
                        <li>Only utilising approved contractors.</li>
                        <li>
                            Reporting any hazards and non-conformances
                            identified as a result of work being performed by
                            contractors.
                        </li>
                    </ul>
                </div>

                <!-- 3.3 Contractors -->
                <div class="mt-4">
                    <h3 class="text-xl font-medium text-gray-700">
                        3.3 Contractors are responsible for:
                    </h3>
                    <ul
                        class="list-disc list-inside mt-2 text-gray-700 space-y-1"
                    >
                        <li>
                            Ensuring compliance to company EHS requirements and
                            expectations.
                        </li>
                        <li>
                            Ensuring they have the required qualifications,
                            training, experience and certificates of competency
                            required for the job.
                        </li>
                        <li>
                            Maintaining the company workplace in a safe and
                            healthy manner for themselves, sub-contractors and
                            other staff and visitors of SKP-JB.
                        </li>
                        <li>Supervision of sub-contractors.</li>
                        <li>
                            Raising any issue that is or may become an EHS
                            concern.
                        </li>
                    </ul>
                </div>

                <!-- 3.4 EHS -->
                <div class="mt-4">
                    <h3 class="text-xl font-medium text-gray-700">
                        3.4 EHS team are responsible for:
                    </h3>
                    <ul
                        class="list-disc list-inside mt-2 text-gray-700 space-y-1"
                    >
                        <li>
                            Prepare, control and issue "General Safety
                            Guidelines for Visitors, Customers, Auditors &
                            Suppliers", "Emergency Evacuation Procedures" and
                            "Assembly Point" to SKP Security Guide from each
                            site.
                        </li>
                    </ul>
                </div>

                <!-- 3.3 Contractors -->
                <div class="mt-4">
                    <h3 class="text-xl font-medium text-gray-700">
                        3.5 SKP Security Guide are responsible for:
                    </h3>
                    <ul
                        class="list-disc list-inside mt-2 text-gray-700 space-y-1"
                    >
                        <li>
                            Display and brief the "General Safety Guidelines for
                            Visitors, Customers, Auditors & Suppliers" (Appendix
                            1), "Emergency Evacuation Procedures" (Appendix 2)
                            and "Assembly Point" (Appendix 3) to new visitor/
                            customer/ auditor/ supplier and etc before enter SKP
                            premises.
                        </li>
                    </ul>
                </div>
            </section>
        </div>

        <div
            v-if="
                hasScrolledToBottom &&
                (visitorType !== 'contractor' || contractorScrolled)
            "
            class="flex items-center gap-2 mb-4"
        >
            <input
                type="checkbox"
                id="confirm"
                :checked="securityGuidelinesConfirmed"
                @change="
                    handleConfirmChange(
                        ($event.target as HTMLInputElement).checked
                    )
                "
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
