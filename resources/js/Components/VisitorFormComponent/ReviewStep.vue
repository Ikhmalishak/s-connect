<script setup lang="ts">
import { ref, watch, nextTick } from "vue";
import { CheckCircle } from "lucide-vue-next";
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps<{
    values: any;
    videoEnded: boolean;
    securityGuidelinesConfirmed: boolean;
    visitorType: string;
    resetReview?: boolean;
}>();

const emit = defineEmits<{
    (e: "update:security-guidelines-confirmed", value: boolean): void;
}>();

const hasScrolledToBottom = ref(false);
const contractorScrolled = ref(false);
const driverScrolled = ref(false); // New ref for driver scroll state

// Refs for the scrollable containers
const securityGuidelinesContainer = ref<HTMLElement>();
const contractorGuidelinesContainer = ref<HTMLElement>();
const driverGuidelinesContainer = ref<HTMLElement>(); // New ref for driver container

// Watch for reset trigger
watch(
    () => props.resetReview,
    async (newVal) => {
        if (newVal) {
            await resetScrollState();
        }
    }
);

async function resetScrollState() {
    // Reset scroll state flags
    hasScrolledToBottom.value = false;
    contractorScrolled.value = false;
    driverScrolled.value = false; // Reset driver scroll state

    // Wait for next tick to ensure DOM is updated
    await nextTick();

    // Reset scroll positions to top
    if (securityGuidelinesContainer.value) {
        securityGuidelinesContainer.value.scrollTop = 0;
    }
    if (contractorGuidelinesContainer.value) {
        contractorGuidelinesContainer.value.scrollTop = 0;
    }
    if (driverGuidelinesContainer.value) {
        // Reset driver container scroll
        driverGuidelinesContainer.value.scrollTop = 0;
    }
}

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

// New handler for driver scroll
const handleDriverScroll = (e: Event) => {
    const el = e.target as HTMLElement;
    if (el.scrollHeight - el.scrollTop <= el.clientHeight + 10) {
        driverScrolled.value = true;
    }
};

const handleConfirmChange = (checked: boolean) => {
    emit("update:security-guidelines-confirmed", checked);
};
</script>

<template>
    <div>
        <h3 class="text-lg font-semibold mb-4">{{ t('visitor.review.title') }}</h3>

        <div
            ref="securityGuidelinesContainer"
            class="h-64 overflow-y-auto border p-4 rounded mb-4"
            @scroll="handleScroll"
        >
            <div class="text-center mb-4">
                <h3 class="text-lg font-semibold text-blue-800">
                    {{ t('visitor.review.securityGuidelines.title') }}
                </h3>
                <!-- <p class="text-sm text-gray-600">Document No: SSOP-SCR-003 | Revision: 00</p> -->
            </div>

            <!-- Section 1: Purpose -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    {{ t('visitor.review.securityGuidelines.purpose.title') }}
                </h4>
                <p class="text-sm text-gray-700 mb-1">
                    {{ t('visitor.review.securityGuidelines.purpose.content1') }}
                </p>
                <p class="text-sm text-gray-700">
                    {{ t('visitor.review.securityGuidelines.purpose.content2') }}
                </p>
            </section>

            <!-- Section 2: Scope -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    {{ t('visitor.review.securityGuidelines.scope.title') }}
                </h4>
                <p class="text-sm text-gray-700 mb-2">
                    {{ t('visitor.review.securityGuidelines.scope.content1') }}
                </p>
                <ul
                    class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4"
                >
                    <li>{{ t('visitor.review.securityGuidelines.scope.visitors') }}</li>
                    <li>{{ t('visitor.review.securityGuidelines.scope.suppliers') }}</li>
                    <li>{{ t('visitor.review.securityGuidelines.scope.applicants') }}</li>
                    <li>{{ t('visitor.review.securityGuidelines.scope.partners') }}</li>
                    <li>{{ t('visitor.review.securityGuidelines.scope.officials') }}</li>
                    <li>{{ t('visitor.review.securityGuidelines.scope.guests') }}</li>
                </ul>
                <p class="text-sm text-gray-700 mt-2">
                    {{ t('visitor.review.securityGuidelines.scope.content2') }}
                </p>
            </section>

            <!-- Section 3: Definitions -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    {{ t('visitor.review.securityGuidelines.definitions.title') }}
                </h4>
                <div class="text-sm text-gray-700 space-y-1">
                    <p>
                        <strong>{{ t('visitor.review.securityGuidelines.definitions.visitor').split(' – ')[0] }}</strong> – {{ t('visitor.review.securityGuidelines.definitions.visitor').split(' – ')[1] }}
                    </p>
                    <p>
                        <strong>{{ t('visitor.review.securityGuidelines.definitions.escort').split(' – ')[0] }}</strong> – {{ t('visitor.review.securityGuidelines.definitions.escort').split(' – ')[1] }}
                    </p>
                    <p>
                        <strong>{{ t('visitor.review.securityGuidelines.definitions.restricted').split(' – ')[0] }}</strong> – {{ t('visitor.review.securityGuidelines.definitions.restricted').split(' – ')[1] }}
                    </p>
                </div>
            </section>

            <!-- Section 4: Visitor Entry Protocol -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    {{ t('visitor.review.securityGuidelines.entryProtocol.title') }}
                </h4>
                <div class="text-sm text-gray-700 space-y-1">
                    <p>{{ t('visitor.review.securityGuidelines.entryProtocol.content1') }}</p>
                    <p>{{ t('visitor.review.securityGuidelines.entryProtocol.content2') }}</p>
                    <p>{{ t('visitor.review.securityGuidelines.entryProtocol.content3') }}</p>
                    <p>{{ t('visitor.review.securityGuidelines.entryProtocol.content4') }}</p>
                    <p>{{ t('visitor.review.securityGuidelines.entryProtocol.content5') }}</p>
                    <p>{{ t('visitor.review.securityGuidelines.entryProtocol.content6') }}</p>
                    <p class="font-medium">{{ t('visitor.review.securityGuidelines.entryProtocol.passLabel') }}</p>
                    <ul class="list-disc list-inside ml-4 space-y-1">
                        <li>{{ t('visitor.review.securityGuidelines.entryProtocol.pass1') }}</li>
                        <li>{{ t('visitor.review.securityGuidelines.entryProtocol.pass2') }}</li>
                        <li>{{ t('visitor.review.securityGuidelines.entryProtocol.pass3') }}</li>
                        <li>{{ t('visitor.review.securityGuidelines.entryProtocol.pass4') }}</li>
                        <li>{{ t('visitor.review.securityGuidelines.entryProtocol.pass5') }}</li>
                    </ul>
                </div>
            </section>

            <!-- Section 5: Escort and Supervision -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    {{ t('visitor.review.securityGuidelines.escort.title') }}
                </h4>
                <div class="text-sm text-gray-700 space-y-1">
                    <p>{{ t('visitor.review.securityGuidelines.escort.content1') }}</p>
                    <p>{{ t('visitor.review.securityGuidelines.escort.content2') }}</p>
                    <p>{{ t('visitor.review.securityGuidelines.escort.content3') }}</p>
                </div>
            </section>

            <!-- Section 6: Prohibited Items and Behavior -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    {{ t('visitor.review.securityGuidelines.prohibited.title') }}
                </h4>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3">
                    <p class="text-sm font-medium text-yellow-800 mb-2">
                        {{ t('visitor.review.securityGuidelines.prohibited.warning') }}
                    </p>
                    <ul class="list-disc list-inside text-sm text-yellow-800 space-y-1 ml-4">
                        <li>{{ t('visitor.review.securityGuidelines.prohibited.weapons') }}</li>
                        <li>{{ t('visitor.review.securityGuidelines.prohibited.drugs') }}</li>
                        <li>{{ t('visitor.review.securityGuidelines.prohibited.recording') }}</li>
                        <li>{{ t('visitor.review.securityGuidelines.prohibited.materials') }}</li>
                    </ul>
                    <p class="text-sm font-medium text-yellow-800 mt-2 mb-1">
                        {{ t('visitor.review.securityGuidelines.prohibited.behavior') }}
                    </p>
                    <ul class="list-disc list-inside text-sm text-yellow-800 space-y-1 ml-4">
                        <li>{{ t('visitor.review.securityGuidelines.prohibited.roaming') }}</li>
                        <li>{{ t('visitor.review.securityGuidelines.prohibited.tampering') }}</li>
                        <li>{{ t('visitor.review.securityGuidelines.prohibited.interfering') }}</li>
                    </ul>
                </div>
            </section>

            <!-- Section 7: Visitor Access to Restricted Areas -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    {{ t('visitor.review.securityGuidelines.restrictedAreas.title') }}
                </h4>
                <div class="text-sm text-gray-700 space-y-1">
                    <p>{{ t('visitor.review.securityGuidelines.restrictedAreas.content1') }}</p>
                    <p>{{ t('visitor.review.securityGuidelines.restrictedAreas.content2') }}</p>
                    <p>{{ t('visitor.review.securityGuidelines.restrictedAreas.content3') }}</p>
                </div>

                <div class="bg-red-50 border-l-4 border-red-400 p-3 mt-3">
                    <p class="text-sm font-bold text-red-800 mb-2">
                        {{ t('visitor.review.securityGuidelines.restrictedAreas.photography') }}
                    </p>
                    <p class="text-sm text-red-800">
                        {{ t('visitor.review.securityGuidelines.restrictedAreas.photographyContent') }}
                    </p>
                </div>
            </section>

            <!-- Section 8: Departure and Exit Protocol -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    {{ t('visitor.review.securityGuidelines.departure.title') }}
                </h4>
                <div class="text-sm text-gray-700 space-y-1">
                    <p class="font-medium">{{ t('visitor.review.securityGuidelines.departure.content1') }}</p>
                    <ul class="list-disc list-inside ml-4 space-y-1">
                        <li>{{ t('visitor.review.securityGuidelines.departure.step1') }}</li>
                        <li>{{ t('visitor.review.securityGuidelines.departure.step2') }}</li>
                        <li>{{ t('visitor.review.securityGuidelines.departure.step3') }}</li>
                    </ul>
                    <p class="mt-2">{{ t('visitor.review.securityGuidelines.departure.content2') }}</p>
                </div>
            </section>

            <!-- Section 9: Emergency Situations -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    {{ t('visitor.review.securityGuidelines.emergency.title') }}
                </h4>
                <div class="bg-red-50 border-l-4 border-red-400 p-3">
                    <p class="text-sm font-medium text-red-800 mb-2">
                        {{ t('visitor.review.securityGuidelines.emergency.warning') }}
                    </p>
                    <ul class="list-disc list-inside text-sm text-red-800 space-y-1 ml-4">
                        <li>{{ t('visitor.review.securityGuidelines.emergency.fire1') }}</li>
                        <li>{{ t('visitor.review.securityGuidelines.emergency.fire2') }}</li>
                        <li>{{ t('visitor.review.securityGuidelines.emergency.fire3') }}</li>
                    </ul>
                </div>
            </section>

            <!-- Section 10: Training and Awareness -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    {{ t('visitor.review.securityGuidelines.training.title') }}
                </h4>
                <div class="text-sm text-gray-700 space-y-1">
                    <p>{{ t('visitor.review.securityGuidelines.training.content1') }}</p>
                    <p>{{ t('visitor.review.securityGuidelines.training.content2') }}</p>
                </div>
            </section>

            <!-- Section 11: Compliance and Violations -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    {{ t('visitor.review.securityGuidelines.compliance.title') }}
                </h4>
                <div class="text-sm text-gray-700 space-y-1">
                    <p>{{ t('visitor.review.securityGuidelines.compliance.content1') }}</p>
                    <p class="font-medium">{{ t('visitor.review.securityGuidelines.compliance.violations') }}</p>
                    <ul class="list-disc list-inside ml-4 space-y-1">
                        <li>{{ t('visitor.review.securityGuidelines.compliance.violation1') }}</li>
                        <li>{{ t('visitor.review.securityGuidelines.compliance.violation2') }}</li>
                        <li>{{ t('visitor.review.securityGuidelines.compliance.violation3') }}</li>
                    </ul>
                    <p class="mt-2">{{ t('visitor.review.securityGuidelines.compliance.content2') }}</p>
                </div>
            </section>

            <!-- Section 12: Review and Maintenance -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    {{ t('visitor.review.securityGuidelines.review.title') }}
                </h4>
                <div class="text-sm text-gray-700 space-y-1">
                    <p>{{ t('visitor.review.securityGuidelines.review.content1') }}</p>
                    <p>{{ t('visitor.review.securityGuidelines.review.content2') }}</p>
                </div>
            </section>
        </div>

        <!-- Driver Guidelines Section -->
        <div
            v-if="visitorType === 'driver'"
            ref="driverGuidelinesContainer"
            class="h-64 overflow-y-auto border p-4 rounded mb-4"
            @scroll="handleDriverScroll"
        >
            <h3 class="text-lg font-semibold mb-2 text-blue-800">
                Driver Policy
            </h3>

            <!-- Section 1: Driver Identification & Authorization -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    1. Driver Identification & Authorization
                </h4>
                <p class="text-sm text-gray-700 mb-2">
                    All drivers (in-house or third-party) must:
                </p>
                <ul
                    class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4"
                >
                    <li>
                        Present a valid Commercial Driving Licence (CDL / GDL
                        Class E)
                    </li>
                    <li>Carry a company ID or official work order</li>
                    <li>
                        Be pre-registered with the security gate, especially for
                        external transporters
                    </li>
                </ul>
                <p class="text-sm text-gray-700 mt-2 font-medium">
                    Access to the facility will be granted only after identity
                    and vehicle verification by site security.
                </p>
            </section>

            <!-- Section 2: Site Entry & Conduct Rules -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    2. Site Entry & Conduct Rules
                </h4>
                <p class="text-sm text-gray-700 mb-2">
                    Entry is allowed only through designated gates and at
                    scheduled delivery/pickup times. All drivers must:
                </p>
                <ul
                    class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4"
                >
                    <li>
                        Undergo vehicle inspection at the gate (if required)
                    </li>
                    <li>Sign in/out using the Driver Logbook/Register</li>
                    <li>Remain in designated parking or waiting zones</li>
                    <li>Wear appropriate PPE (vest, shoes) while on site</li>
                </ul>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-2 mt-2">
                    <p class="text-sm text-yellow-800 font-medium">
                        ⚠️ Unauthorized movement around production floors,
                        warehouses, or offices is prohibited unless escorted.
                    </p>
                </div>
            </section>

            <!-- Section 3: Cargo Handling & Security -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    3. Cargo Handling & Security
                </h4>
                <p class="text-sm text-gray-700 mb-2">
                    Drivers are not allowed to load or unload unless
                    specifically trained and authorized by SKP supervisors.
                </p>
                <p class="text-sm text-gray-700 mb-2">All cargo must be:</p>
                <ul
                    class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4"
                >
                    <li>Verified against Delivery Orders (DOs) or Invoice</li>
                    <li>
                        Sealed or shrink-wrapped (especially for finished goods
                        or raw materials)
                    </li>
                </ul>
                <p class="text-sm text-gray-700 mb-2 mt-3">
                    If transporting sensitive or bulk plastic materials, ensure:
                </p>
                <ul
                    class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4"
                >
                    <li>Vehicle is clean and free of contaminating residues</li>
                    <li>Load is properly secured and covered</li>
                </ul>
            </section>

            <!-- Section 4: Emergency Procedures -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    4. Emergency Procedures
                </h4>
                <div class="bg-red-50 border-l-4 border-red-400 p-3">
                    <p class="text-sm text-red-800 font-medium mb-2">
                        🚨 In the event of:
                    </p>

                    <div class="mb-3">
                        <p class="text-sm font-medium text-red-700">
                            Accidents or incidents:
                        </p>
                        <ul
                            class="list-disc list-inside text-sm text-red-700 space-y-1 ml-4"
                        >
                            <li>
                                Stop vehicle safely, assist injured (if
                                applicable), inform the police and SKP
                            </li>
                            <li>
                                Take photos and document time, location, and any
                                damage
                            </li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <p class="text-sm font-medium text-red-700">
                            Cargo tampering or theft attempts:
                        </p>
                        <ul
                            class="list-disc list-inside text-sm text-red-700 space-y-1 ml-4"
                        >
                            <li>
                                Do not resist; notify SKP security immediately
                            </li>
                            <li>
                                Protect the scene and evidence where safe to do
                                so
                            </li>
                        </ul>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-red-700">
                            Medical emergencies:
                        </p>
                        <ul
                            class="list-disc list-inside text-sm text-red-700 space-y-1 ml-4"
                        >
                            <li>Call nearest clinic/emergency line</li>
                            <li>
                                Notify SKP contact person listed on job
                                sheet/emergency contact number
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Section 5: Prohibited Conduct -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    5. Prohibited Conduct
                </h4>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3">
                    <p class="text-sm text-yellow-800 font-medium mb-2">
                        🚫 The following are strictly prohibited on SKP's
                        premises and during transport duties:
                    </p>
                    <ul
                        class="list-disc list-inside text-sm text-yellow-800 space-y-1 ml-4"
                    >
                        <li>Use of alcohol, drugs, or smoking</li>
                        <li>Aggressive or disrespectful behaviour</li>
                        <li>Take photography/video/recording</li>
                        <li>Transporting unauthorized passengers</li>
                        <li>
                            Mobile phone use while driving (unless hands-free)
                        </li>
                    </ul>
                    <p class="text-sm text-yellow-800 font-medium mt-2">
                        Any violation may result in:
                    </p>
                    <ul
                        class="list-disc list-inside text-sm text-yellow-800 space-y-1 ml-4"
                    >
                        <li>Denial of site access</li>
                        <li>Suspension of contract or employment</li>
                    </ul>
                </div>
            </section>

            <!-- Section 6: Training & Awareness -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    6. Training & Awareness
                </h4>
                <p class="text-sm text-gray-700 mb-2">Drivers must attend:</p>
                <ul
                    class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4"
                >
                    <li>Annual security awareness</li>
                    <li>
                        Defensive driving & route security sessions (as needed)
                    </li>
                    <li>
                        Third-party drivers may receive a site-specific
                        orientation upon first visit
                    </li>
                </ul>
            </section>

            <!-- Section 7: Documentation & Compliance -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    7. Documentation & Compliance
                </h4>
                <p class="text-sm text-gray-700 mb-2">
                    All drivers must carry:
                </p>
                <ul
                    class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4"
                >
                    <li>Valid license and delivery documents</li>
                    <li>
                        All gate-ins and gate-outs will be logged and retained
                        for audit purposes
                    </li>
                </ul>
            </section>
        </div>

        <!-- Contractor Guidelines Section -->
        <div
            v-if="visitorType === 'contractor'"
            ref="contractorGuidelinesContainer"
            class="h-64 overflow-y-auto border p-4 rounded mb-4"
            @scroll="handleContractorScroll"
        >
            <div class="text-center mb-4">
                <h3 class="text-lg font-semibold text-blue-800">
                    VISITOR/SUPPLIER/CONTRACTOR MANAGEMENT PROCEDURE
                </h3>
                <p class="text-sm text-gray-600">
                    Document No: SKP-EHSSOP-018 | Revision: 05 | Date:
                    01.04.2024
                </p>
            </div>

            <!-- Section 1: Objective -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    1.0 OBJECTIVE
                </h4>
                <p class="text-sm text-gray-700">
                    The objective of this procedure is to provide guidance in
                    the selection, management and monitoring of contractors.
                </p>
            </section>

            <!-- Section 2: Scope -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    2.0 SCOPE
                </h4>
                <p class="text-sm text-gray-700">
                    This procedure applies to all company workplaces and covers
                    the selection, management and monitoring of service
                    provider/supplier/contractors associated with maintenance
                    and repair work. This procedure is not intended to apply to
                    capital works involving a tender or formal contract process.
                </p>
            </section>

            <!-- Section 3: Responsibilities -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    3.0 RESPONSIBILITIES
                </h4>

                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">
                        3.1 Workplace HOD and/or EHS Nominee is responsible for:
                    </p>
                    <ul
                        class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4"
                    >
                        <li>Maintaining a register of approved contractors</li>
                        <li>
                            Inducting contractors in EHS requirements and
                            behavioural expectations while on site
                        </li>
                        <li>
                            Familiarising contractors with their work
                            environment and the specific hazards they may be
                            potentially exposed to
                        </li>
                        <li>
                            Investigating any hazards identified by all
                            contractor employees
                        </li>
                        <li>
                            Acting on identified non-compliance of contractors
                            to EHS procedures
                        </li>
                    </ul>
                </div>

                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">
                        3.2 Employees are responsible for:
                    </p>
                    <ul
                        class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4"
                    >
                        <li>Only utilising approved contractors</li>
                        <li>
                            Reporting any hazards and non-conformances
                            identified as a result of work being performed by
                            contractors
                        </li>
                    </ul>
                </div>

                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">
                        3.3 Contractors are responsible for:
                    </p>
                    <ul
                        class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4"
                    >
                        <li>
                            Ensuring compliance to company EHS requirements and
                            expectations
                        </li>
                        <li>
                            Ensuring they have the required qualifications,
                            training, experience and certificates of competency
                            required for the job
                        </li>
                        <li>
                            Maintaining the company workplace in a safe and
                            healthy manner for themselves, sub-contractors and
                            other staff and visitors of SKP-JB
                        </li>
                        <li>Supervision of sub-contractors</li>
                        <li>
                            Raising any issue that is or may become an EHS
                            concern
                        </li>
                    </ul>
                </div>

                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">
                        3.4 EHS team are responsible for:
                    </p>
                    <ul
                        class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4"
                    >
                        <li>
                            Prepare, control and issue "General Safety
                            Guidelines for Visitors, Customers, Auditors &
                            Suppliers", "Emergency Evacuation Procedures" and
                            "Assembly Point" to SKP Security Guide from each
                            site
                        </li>
                    </ul>
                </div>

                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">
                        3.5 SKP Security Guide are responsible for:
                    </p>
                    <ul
                        class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4"
                    >
                        <li>
                            Display and brief the "General Safety Guidelines for
                            Visitors, Customers, Auditors & Suppliers",
                            "Emergency Evacuation Procedures" and "Assembly
                            Point" to new visitor/customer/auditor/supplier
                            before enter SKP premises
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Section 5: Definitions -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    5.0 DEFINITIONS
                </h4>
                <div class="text-sm text-gray-700 space-y-2">
                    <div>
                        <p>
                            <strong>Contractor:</strong> Service
                            providers/individuals who are not direct employees
                            of the company and are providing services/works in
                            relation to maintenance and repair work. This
                            includes contractor employees, subcontractors and
                            sub contractor's employees.
                        </p>
                    </div>
                    <div>
                        <p>
                            <strong>High Risk Work:</strong> Work where the
                            service provider/contractor personnel will attend
                            SKP workplaces and perform work including gas,
                            electrical installations, permits required work,
                            licensed work, hazardous materials removal, work at
                            heights above 2 metres, construction work.
                        </p>
                    </div>
                    <div>
                        <p>
                            <strong>Medium Risk Work:</strong> Repairs or
                            service to plant/equipment, fixtures/fittings, work
                            in restricted access areas.
                        </p>
                    </div>
                    <div>
                        <p>
                            <strong>Low Risk Work:</strong> Service
                            provider/contractor personnel will not attend SKP
                            workplaces or perform low risk work like inspection
                            services, delivery of materials, advisory services.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Section 6: Procedure -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    6.0 PROCEDURE
                </h4>

                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">
                        6.2 Requesting a Contractor
                    </p>
                    <p class="text-sm text-gray-700 mb-2">
                        When a service need is identified that cannot be
                        completed by a company employee, the person requiring
                        this service must inform the Workplace HOD/EHS Nominee,
                        HR Department or Operation Manager. Only contractors on
                        the Approved Contractor List may be contacted/engaged.
                    </p>
                    <p class="text-sm text-gray-700 mb-1">
                        Examples of services include:
                    </p>
                    <ul
                        class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4"
                    >
                        <li>Air conditioning maintenance</li>
                        <li>Plumbing and electrical repairs</li>
                        <li>Equipment/Machine repairs</li>
                        <li>Pest control</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">
                        6.3 Approving Contractors
                    </p>
                    <p class="text-sm text-gray-700 mb-2">
                        For non-approved contractors, the Workplace HOD/EHS
                        Nominee must notify the Operation Manager and get
                        approval prior to their engagement. The contractor will
                        be required to undergo an induction briefing on company
                        EHS requirements using the "Supplier/Contractor
                        Environmental Health and Safety Manual".
                    </p>
                </div>

                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">
                        6.6 Visitor/Supplier/Contractor Induction Checklist
                    </p>
                    <p class="text-sm text-gray-700 mb-2">
                        Contractor inductions are valid for 2 years. Topics
                        covered in the induction include:
                    </p>
                    <ul
                        class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4"
                    >
                        <li>EHS policies</li>
                        <li>General safety rules</li>
                        <li>Evacuation procedure</li>
                        <li>Hazard and incident reporting</li>
                        <li>Personal Protective Equipment (PPE)</li>
                        <li>Safety Data Sheets</li>
                        <li>Cleanliness and waste management</li>
                        <li>Enforcement</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">
                        6.7 Contractor Permit to Work
                    </p>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3">
                        <p class="text-sm text-yellow-800 mb-1">
                            The Workplace HOD/EHS Nominee must ensure specific
                            EHS Procedure and Permit to Work system is followed
                            for work including:
                        </p>
                        <ul
                            class="list-disc list-inside text-sm text-yellow-800 space-y-1 ml-4"
                        >
                            <li>Hot work</li>
                            <li>Working at height</li>
                            <li>Repair/service maintenance/calibration</li>
                            <li>Renovation</li>
                            <li>All related work that can introduce hazard</li>
                        </ul>
                        <p class="text-sm text-yellow-800 mt-2">
                            All Permit to Work must be signed by the EHS
                            department.
                        </p>
                    </div>
                </div>

                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">
                        6.10 Non-Conformance
                    </p>
                    <div class="bg-red-50 border-l-4 border-red-400 p-3">
                        <p class="text-sm text-red-800 mb-1">
                            If contractor fails to comply with company EHS
                            requirements, a Non-Conformance Report will be
                            issued. Sources include:
                        </p>
                        <ul
                            class="list-disc list-inside text-sm text-red-800 space-y-1 ml-4"
                        >
                            <li>Working in an unsafe manner</li>
                            <li>Not wearing contractor badge or PPE</li>
                            <li>Poor workmanship or inappropriate behavior</li>
                            <li>Smoking inside SKP premises</li>
                        </ul>
                        <p class="text-sm text-red-800 mt-2">
                            Failure to comply can lead to termination of
                            approval status.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Appendix: General Safety Guidelines -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    General Safety Guidelines for Contractors
                </h4>
                <div class="bg-blue-50 border-l-4 border-blue-400 p-3">
                    <ul
                        class="list-disc list-inside text-sm text-blue-800 space-y-1"
                    >
                        <li>
                            Ensure that you are escorted by SKP employee at all
                            times during your visit
                        </li>
                        <li>
                            Walk within the passageway and do not cross over to
                            the production area unless required
                        </li>
                        <li>
                            Do not touch any line equipment, products or
                            components unnecessarily
                        </li>
                        <li>No photography/camera allowed</li>
                        <li>
                            Ensure that you are equipped with safety gears at
                            all times (covered shoes, ear plugs & safety
                            goggles)
                        </li>
                        <li>
                            Wear ear plugs if exposed to high noise (>85dB) for
                            more than 15 minutes in production area
                        </li>
                        <li>
                            In emergency situations, follow all instructions by
                            your SKP contact person and proceed to Assembly Area
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Section 7: Contractor Declaration -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    7.0 Contractor Declaration
                </h4>
                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">
                        7.1 Contractor Declaration Checklist
                    </p>
                    <ul
                        class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4"
                    >
                         <li>
                            I understand my duties under the Occupational Safety
                            & Health Act and Environment Act in relation to the
                            circumstances in which the work will be conducted.
                        </li>
                        <li>
                            I hold current certifications, qualifications, and
                            licenses required by legislation for this work.
                        </li>
                        <li>
                            I have completed the SKP site induction and safety
                            briefing.
                        </li>
                        <li>
                            I will cease work immediately to ensure the
                            workplace is safe and contact the SKP site contact
                            if I notice or realize any danger to myself or
                            others during the period of the work.
                        </li>
                        <li>
                            I agree to comply with all site safety requirements
                            and reasonable directions given by SKP.
                        </li>
                        <li>
                            Smoking is strictly prohibited inside SKP premises.
                        </li>
                    </ul>
                </div>
            </section>
            
            <!-- Emergency Contact Information -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">
                    Emergency Contact Information
                </h4>
                <div class="bg-gray-50 border p-2 rounded text-xs">
                    <p>
                        <strong>SKP JB Main:</strong> 07-598 0000 (S1), 07-595
                        1677 (S2)
                    </p>
                    <p>
                        <strong>Security Department:</strong> Ext 141 (S1), Ext
                        147 (S2)
                    </p>
                    <p>
                        <strong>Safety Department:</strong> 012-5768338 (S1),
                        016-7193083 (S2)
                    </p>
                </div>
            </section>

        </div>

        <div
            v-if="
                hasScrolledToBottom &&
                (visitorType !== 'contractor' || contractorScrolled) &&
                (visitorType !== 'driver' || driverScrolled)
            "
            class="flex items-center gap-2 mb-4"
        >
            <div class="flex flex-row justify-between w-full">
                <div>
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
                        {{ t('visitor.review.readGuidelines') }}
                    </label>
                </div>

                <div class="flex flex-row text-sm">
                    <div>{{ t('visitor.result.visitorName') }}:</div>
                    <div>
                        <span
                            v-for="(v, index) in values.visitors"
                            :key="index"
                            class="ml-2 flex flex-cols"
                        >
                            {{ v.visitor_name }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="videoEnded" class="flex items-center gap-2 text-green-600">
            <CheckCircle class="h-5 w-5" />
            <span>{{ t('visitor.review.videoCompleted') }}</span>
        </div>
    </div>
</template>
