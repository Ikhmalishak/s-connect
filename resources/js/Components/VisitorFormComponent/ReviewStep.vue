<script setup lang="ts">
import { ref, watch, nextTick } from "vue";
import { CheckCircle } from "lucide-vue-next";

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
    if (driverGuidelinesContainer.value) { // Reset driver container scroll
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
        <h3 class="text-lg font-semibold mb-4">Review & Submit</h3>

        <div
            ref="securityGuidelinesContainer"
            class="h-64 overflow-y-auto border p-4 rounded mb-4"
            @scroll="handleScroll"
        >
            <div class="text-center mb-4">
                <h3 class="text-lg font-semibold text-blue-800">VISITOR MANAGEMENT POLICY</h3>
                <!-- <p class="text-sm text-gray-600">Document No: SSOP-SCR-003 | Revision: 00</p> -->
            </div>

            <!-- Section 1: Purpose -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">1.0 Purpose</h4>
                <p class="text-sm text-gray-700 mb-1">1.1 This policy establishes standard procedures to control and monitor the access of all visitors to company premises.</p>
                <p class="text-sm text-gray-700">1.2 The goal is to protect the safety of personnel, safeguard assets, and maintain confidentiality by preventing unauthorized access.</p>
            </section>

            <!-- Section 2: Scope -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">2.0 Scope</h4>
                <p class="text-sm text-gray-700 mb-2">2.1 This policy applies to all types of visitors, including but not limited to:</p>
                <ul class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4">
                    <li>2.1.1 Contractors</li>
                    <li>2.1.2 Suppliers</li>
                    <li>2.1.3 Job applicants</li>
                    <li>2.1.4 Business partners</li>
                    <li>2.1.5 Government officials</li>
                    <li>2.1.6 Family members or personal guests of employees</li>
                </ul>
                <p class="text-sm text-gray-700 mt-2">2.2 The policy applies to all company-controlled facilities, including factories, warehouses, and office buildings.</p>
            </section>

            <!-- Section 3: Definitions -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">3.0 Definitions</h4>
                <div class="text-sm text-gray-700 space-y-1">
                    <p><strong>3.1 Visitor</strong> – Any individual not employed by the company who enters company premises for a temporary duration.</p>
                    <p><strong>3.2 Escort</strong> – A designated employee responsible for supervising the visitor during their stay.</p>
                    <p><strong>3.3 Restricted Area</strong> – An area that requires additional access control and special permission for entry.</p>
                </div>
            </section>

            <!-- Section 4: Visitor Entry Protocol -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">4.0 Visitor Entry Protocol</h4>
                <div class="text-sm text-gray-700 space-y-1">
                    <p>4.1 All visitors must enter through the designated main security gate or reception.</p>
                    <p>4.2 The visitor must present valid government-issued identification.</p>
                    <p>4.3 The visitor must state the purpose of the visit and the name of the host employee.</p>
                    <p>4.4 The host must confirm and approve the visitor before entry is granted.</p>
                    <p>4.5 All visitors must sign the Visitor Log Book or digital visitor management system.</p>
                    <p>4.6 Visitors must wear a clearly visible Visitor Pass at all times.</p>
                    <p class="font-medium">4.7 Visitor passes must indicate:</p>
                    <ul class="list-disc list-inside ml-4 space-y-1">
                        <li>4.7.1 Visitor name</li>
                        <li>4.7.2 Host employee</li>
                        <li>4.7.3 Entry date and time</li>
                        <li>4.7.4 Expiry time</li>
                        <li>4.7.5 Access level</li>
                    </ul>
                </div>
            </section>

            <!-- Section 5: Escort and Supervision -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">5.0 Escort and Supervision</h4>
                <div class="text-sm text-gray-700 space-y-1">
                    <p>5.1 All visitors must be escorted by the host or designated staff at all times.</p>
                    <p>5.2 Escorts must ensure that visitors remain in approved areas only.</p>
                    <p>5.3 Escorts are responsible for explaining site safety rules and emergency procedures.</p>
                </div>
            </section>

            <!-- Section 6: Prohibited Items and Behavior -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">6.0 Prohibited Items and Behavior</h4>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3">
                    <p class="text-sm font-medium text-yellow-800 mb-2">6.1 Visitors are prohibited from carrying:</p>
                    <ul class="list-disc list-inside text-sm text-yellow-800 space-y-1 ml-4">
                        <li>6.1.1 Weapons</li>
                        <li>6.1.2 Drugs or alcohol</li>
                        <li>6.1.3 Unauthorized photography or recording equipment</li>
                        <li>6.1.4 Flammable or hazardous materials (unless approved)</li>
                    </ul>
                    <p class="text-sm font-medium text-yellow-800 mt-2 mb-1">6.2 Visitors must not engage in:</p>
                    <ul class="list-disc list-inside text-sm text-yellow-800 space-y-1 ml-4">
                        <li>6.2.1 Unattended roaming</li>
                        <li>6.2.2 Tampering with equipment or machinery</li>
                        <li>6.2.3 Interfering with employees or ongoing operations</li>
                    </ul>
                </div>
            </section>

            <!-- Section 7: Visitor Access to Restricted Areas -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">7.0 Visitor Access to Restricted Areas</h4>
                <div class="text-sm text-gray-700 space-y-1">
                    <p>7.1 Entry to Restricted Areas is allowed only with prior written approval from the Security Manager or Department Head.</p>
                    <p>7.2 Visitors must be always accompanied by a senior-level escort.</p>
                    <p>7.3 Additional PPE (personal protective equipment) may be required and must be always worn in these areas.</p>
                </div>
                
                <div class="bg-red-50 border-l-4 border-red-400 p-3 mt-3">
                    <p class="text-sm font-bold text-red-800 mb-2">📷 Strict Prohibition on Photography and Recording in the Factory Premises</p>
                    <p class="text-sm text-red-800">
                        The taking of photos, videos, or any form of recording (whether by camera, smartphone, or any other electronic device) within the factory premises, particularly in the production line is strictly prohibited. This applies to all employees, contractors, visitors and any third parties, without prior written approval from the Security Department or authorized personnel.
                    </p>
                </div>
            </section>

            <!-- Section 8: Departure and Exit Protocol -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">8.0 Departure and Exit Protocol</h4>
                <div class="text-sm text-gray-700 space-y-1">
                    <p class="font-medium">8.1 Upon completion of their visit, the visitor must:</p>
                    <ul class="list-disc list-inside ml-4 space-y-1">
                        <li>8.1.1 Return the Visitor Pass to the security desk</li>
                        <li>8.1.2 Sign the exit log</li>
                        <li>8.1.3 Be escorted out of the premises by their host or security</li>
                    </ul>
                    <p class="mt-2">8.2 Security must verify that no company assets, documents, or data are removed without approval.</p>
                </div>
            </section>

            <!-- Section 9: Emergency Situations -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">9.0 Emergency Situations</h4>
                <div class="bg-red-50 border-l-4 border-red-400 p-3">
                    <p class="text-sm font-medium text-red-800 mb-2">🚨 9.1 In the event of a fire, evacuation, or lockdown:</p>
                    <ul class="list-disc list-inside text-sm text-red-800 space-y-1 ml-4">
                        <li>9.1.1 Visitors must follow all instructions from emergency marshals or security personnel.</li>
                        <li>9.1.2 The host is responsible for the safe evacuation of their visitor.</li>
                        <li>9.1.3 A visitor roll-call must be performed at the assembly point.</li>
                    </ul>
                </div>
            </section>

            <!-- Section 10: Training and Awareness -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">10.0 Training and Awareness</h4>
                <div class="text-sm text-gray-700 space-y-1">
                    <p>10.1 Security personnel must be trained in proper visitor screening, documentation, and emergency procedures.</p>
                    <p>10.2 All employees must be reminded of their responsibilities when hosting visitors through periodic awareness briefings.</p>
                </div>
            </section>

            <!-- Section 11: Compliance and Violations -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">11.0 Compliance and Violations</h4>
                <div class="text-sm text-gray-700 space-y-1">
                    <p>11.1 All visitors are subject to company rules and regulations while on-site.</p>
                    <p class="font-medium">11.2 Violations of this policy may result in:</p>
                    <ul class="list-disc list-inside ml-4 space-y-1">
                        <li>11.2.1 Immediate removal from premises</li>
                        <li>11.2.2 Reporting to authorities</li>
                        <li>11.2.3 Banning from future visits</li>
                    </ul>
                    <p class="mt-2">11.3 Employees who violate this policy may face disciplinary action.</p>
                </div>
            </section>

            <!-- Section 12: Review and Maintenance -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">12.0 Review and Maintenance</h4>
                <div class="text-sm text-gray-700 space-y-1">
                    <p>12.1 This policy shall be reviewed annually or following a security breach.</p>
                    <p>12.2 Updates shall be made to reflect changes in legal, operational, or safety requirements.</p>
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
            <h3 class="text-lg font-semibold mb-2 text-blue-800">Driver Policy</h3>
            
            <!-- Section 1: Driver Identification & Authorization -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">1. Driver Identification & Authorization</h4>
                <p class="text-sm text-gray-700 mb-2">All drivers (in-house or third-party) must:</p>
                <ul class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4">
                    <li>Present a valid Commercial Driving Licence (CDL / GDL Class E)</li>
                    <li>Carry a company ID or official work order</li>
                    <li>Be pre-registered with the security gate, especially for external transporters</li>
                </ul>
                <p class="text-sm text-gray-700 mt-2 font-medium">
                    Access to the facility will be granted only after identity and vehicle verification by site security.
                </p>
            </section>

            <!-- Section 2: Site Entry & Conduct Rules -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">2. Site Entry & Conduct Rules</h4>
                <p class="text-sm text-gray-700 mb-2">Entry is allowed only through designated gates and at scheduled delivery/pickup times. All drivers must:</p>
                <ul class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4">
                    <li>Undergo vehicle inspection at the gate (if required)</li>
                    <li>Sign in/out using the Driver Logbook/Register</li>
                    <li>Remain in designated parking or waiting zones</li>
                    <li>Wear appropriate PPE (vest, shoes) while on site</li>
                </ul>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-2 mt-2">
                    <p class="text-sm text-yellow-800 font-medium">
                        ⚠️ Unauthorized movement around production floors, warehouses, or offices is prohibited unless escorted.
                    </p>
                </div>
            </section>

            <!-- Section 3: Cargo Handling & Security -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">3. Cargo Handling & Security</h4>
                <p class="text-sm text-gray-700 mb-2">
                    Drivers are not allowed to load or unload unless specifically trained and authorized by SKP supervisors.
                </p>
                <p class="text-sm text-gray-700 mb-2">All cargo must be:</p>
                <ul class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4">
                    <li>Verified against Delivery Orders (DOs) or Invoice</li>
                    <li>Sealed or shrink-wrapped (especially for finished goods or raw materials)</li>
                </ul>
                <p class="text-sm text-gray-700 mb-2 mt-3">If transporting sensitive or bulk plastic materials, ensure:</p>
                <ul class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4">
                    <li>Vehicle is clean and free of contaminating residues</li>
                    <li>Load is properly secured and covered</li>
                </ul>
            </section>

            <!-- Section 4: Emergency Procedures -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">4. Emergency Procedures</h4>
                <div class="bg-red-50 border-l-4 border-red-400 p-3">
                    <p class="text-sm text-red-800 font-medium mb-2">🚨 In the event of:</p>
                    
                    <div class="mb-3">
                        <p class="text-sm font-medium text-red-700">Accidents or incidents:</p>
                        <ul class="list-disc list-inside text-sm text-red-700 space-y-1 ml-4">
                            <li>Stop vehicle safely, assist injured (if applicable), inform the police and SKP</li>
                            <li>Take photos and document time, location, and any damage</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <p class="text-sm font-medium text-red-700">Cargo tampering or theft attempts:</p>
                        <ul class="list-disc list-inside text-sm text-red-700 space-y-1 ml-4">
                            <li>Do not resist; notify SKP security immediately</li>
                            <li>Protect the scene and evidence where safe to do so</li>
                        </ul>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-red-700">Medical emergencies:</p>
                        <ul class="list-disc list-inside text-sm text-red-700 space-y-1 ml-4">
                            <li>Call nearest clinic/emergency line</li>
                            <li>Notify SKP contact person listed on job sheet/emergency contact number</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Section 5: Prohibited Conduct -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">5. Prohibited Conduct</h4>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3">
                    <p class="text-sm text-yellow-800 font-medium mb-2">🚫 The following are strictly prohibited on SKP's premises and during transport duties:</p>
                    <ul class="list-disc list-inside text-sm text-yellow-800 space-y-1 ml-4">
                        <li>Use of alcohol, drugs, or smoking</li>
                        <li>Aggressive or disrespectful behaviour</li>
                        <li>Take photography/video/recording</li>
                        <li>Transporting unauthorized passengers</li>
                        <li>Mobile phone use while driving (unless hands-free)</li>
                    </ul>
                    <p class="text-sm text-yellow-800 font-medium mt-2">Any violation may result in:</p>
                    <ul class="list-disc list-inside text-sm text-yellow-800 space-y-1 ml-4">
                        <li>Denial of site access</li>
                        <li>Suspension of contract or employment</li>
                    </ul>
                </div>
            </section>

            <!-- Section 6: Training & Awareness -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">6. Training & Awareness</h4>
                <p class="text-sm text-gray-700 mb-2">Drivers must attend:</p>
                <ul class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4">
                    <li>Annual security awareness</li>
                    <li>Defensive driving & route security sessions (as needed)</li>
                    <li>Third-party drivers may receive a site-specific orientation upon first visit</li>
                </ul>
            </section>

            <!-- Section 7: Documentation & Compliance -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">7. Documentation & Compliance</h4>
                <p class="text-sm text-gray-700 mb-2">All drivers must carry:</p>
                <ul class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4">
                    <li>Valid license and delivery documents</li>
                    <li>All gate-ins and gate-outs will be logged and retained for audit purposes</li>
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
                <h3 class="text-lg font-semibold text-blue-800">VISITOR/SUPPLIER/CONTRACTOR MANAGEMENT PROCEDURE</h3>
                <p class="text-sm text-gray-600">Document No: SKP-EHSSOP-018 | Revision: 05 | Date: 01.04.2024</p>
            </div>

            <!-- Section 1: Objective -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">1.0 OBJECTIVE</h4>
                <p class="text-sm text-gray-700">
                    The objective of this procedure is to provide guidance in the selection, management and monitoring of contractors.
                </p>
            </section>

            <!-- Section 2: Scope -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">2.0 SCOPE</h4>
                <p class="text-sm text-gray-700">
                    This procedure applies to all company workplaces and covers the selection, management and monitoring of service provider/supplier/contractors associated with maintenance and repair work. This procedure is not intended to apply to capital works involving a tender or formal contract process.
                </p>
            </section>

            <!-- Section 3: Responsibilities -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">3.0 RESPONSIBILITIES</h4>
                
                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">3.1 Workplace HOD and/or EHS Nominee is responsible for:</p>
                    <ul class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4">
                        <li>Maintaining a register of approved contractors</li>
                        <li>Inducting contractors in EHS requirements and behavioural expectations while on site</li>
                        <li>Familiarising contractors with their work environment and the specific hazards they may be potentially exposed to</li>
                        <li>Investigating any hazards identified by all contractor employees</li>
                        <li>Acting on identified non-compliance of contractors to EHS procedures</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">3.2 Employees are responsible for:</p>
                    <ul class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4">
                        <li>Only utilising approved contractors</li>
                        <li>Reporting any hazards and non-conformances identified as a result of work being performed by contractors</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">3.3 Contractors are responsible for:</p>
                    <ul class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4">
                        <li>Ensuring compliance to company EHS requirements and expectations</li>
                        <li>Ensuring they have the required qualifications, training, experience and certificates of competency required for the job</li>
                        <li>Maintaining the company workplace in a safe and healthy manner for themselves, sub-contractors and other staff and visitors of SKP-JB</li>
                        <li>Supervision of sub-contractors</li>
                        <li>Raising any issue that is or may become an EHS concern</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">3.4 EHS team are responsible for:</p>
                    <ul class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4">
                        <li>Prepare, control and issue "General Safety Guidelines for Visitors, Customers, Auditors & Suppliers", "Emergency Evacuation Procedures" and "Assembly Point" to SKP Security Guide from each site</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">3.5 SKP Security Guide are responsible for:</p>
                    <ul class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4">
                        <li>Display and brief the "General Safety Guidelines for Visitors, Customers, Auditors & Suppliers", "Emergency Evacuation Procedures" and "Assembly Point" to new visitor/customer/auditor/supplier before enter SKP premises</li>
                    </ul>
                </div>
            </section>

            <!-- Section 5: Definitions -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">5.0 DEFINITIONS</h4>
                <div class="text-sm text-gray-700 space-y-2">
                    <div>
                        <p><strong>Contractor:</strong> Service providers/individuals who are not direct employees of the company and are providing services/works in relation to maintenance and repair work. This includes contractor employees, subcontractors and sub contractor's employees.</p>
                    </div>
                    <div>
                        <p><strong>High Risk Work:</strong> Work where the service provider/contractor personnel will attend SKP workplaces and perform work including gas, electrical installations, permits required work, licensed work, hazardous materials removal, work at heights above 2 metres, construction work.</p>
                    </div>
                    <div>
                        <p><strong>Medium Risk Work:</strong> Repairs or service to plant/equipment, fixtures/fittings, work in restricted access areas.</p>
                    </div>
                    <div>
                        <p><strong>Low Risk Work:</strong> Service provider/contractor personnel will not attend SKP workplaces or perform low risk work like inspection services, delivery of materials, advisory services.</p>
                    </div>
                </div>
            </section>

            <!-- Section 6: Procedure -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">6.0 PROCEDURE</h4>
                
                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">6.2 Requesting a Contractor</p>
                    <p class="text-sm text-gray-700 mb-2">When a service need is identified that cannot be completed by a company employee, the person requiring this service must inform the Workplace HOD/EHS Nominee, HR Department or Operation Manager. Only contractors on the Approved Contractor List may be contacted/engaged.</p>
                    <p class="text-sm text-gray-700 mb-1">Examples of services include:</p>
                    <ul class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4">
                        <li>Air conditioning maintenance</li>
                        <li>Plumbing and electrical repairs</li>
                        <li>Equipment/Machine repairs</li>
                        <li>Pest control</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">6.3 Approving Contractors</p>
                    <p class="text-sm text-gray-700 mb-2">For non-approved contractors, the Workplace HOD/EHS Nominee must notify the Operation Manager and get approval prior to their engagement. The contractor will be required to undergo an induction briefing on company EHS requirements using the "Supplier/Contractor Environmental Health and Safety Manual".</p>
                </div>

                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">6.6 Visitor/Supplier/Contractor Induction Checklist</p>
                    <p class="text-sm text-gray-700 mb-2">Contractor inductions are valid for 2 years. Topics covered in the induction include:</p>
                    <ul class="list-disc list-inside text-sm text-gray-700 space-y-1 ml-4">
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
                    <p class="text-sm font-medium text-gray-700 mb-1">6.7 Contractor Permit to Work</p>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3">
                        <p class="text-sm text-yellow-800 mb-1">The Workplace HOD/EHS Nominee must ensure specific EHS Procedure and Permit to Work system is followed for work including:</p>
                        <ul class="list-disc list-inside text-sm text-yellow-800 space-y-1 ml-4">
                            <li>Hot work</li>
                            <li>Working at height</li>
                            <li>Repair/service maintenance/calibration</li>
                            <li>Renovation</li>
                            <li>All related work that can introduce hazard</li>
                        </ul>
                        <p class="text-sm text-yellow-800 mt-2">All Permit to Work must be signed by the EHS department.</p>
                    </div>
                </div>

                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">6.10 Non-Conformance</p>
                    <div class="bg-red-50 border-l-4 border-red-400 p-3">
                        <p class="text-sm text-red-800 mb-1">If contractor fails to comply with company EHS requirements, a Non-Conformance Report will be issued. Sources include:</p>
                        <ul class="list-disc list-inside text-sm text-red-800 space-y-1 ml-4">
                            <li>Working in an unsafe manner</li>
                            <li>Not wearing contractor badge or PPE</li>
                            <li>Poor workmanship or inappropriate behavior</li>
                        </ul>
                        <p class="text-sm text-red-800 mt-2">Failure to comply can lead to termination of approval status.</p>
                    </div>
                </div>
            </section>

            <!-- Appendix: General Safety Guidelines -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">General Safety Guidelines for Contractors</h4>
                <div class="bg-blue-50 border-l-4 border-blue-400 p-3">
                    <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                        <li>Ensure that you are escorted by SKP employee at all times during your visit</li>
                        <li>Walk within the passageway and do not cross over to the production area unless required</li>
                        <li>Do not touch any line equipment, products or components unnecessarily</li>
                        <li>No photography/camera allowed</li>
                        <li>Ensure that you are equipped with safety gears at all times (covered shoes, ear plugs & safety goggles)</li>
                        <li>Wear ear plugs if exposed to high noise (>85dB) for more than 15 minutes in production area</li>
                        <li>In emergency situations, follow all instructions by your SKP contact person and proceed to Assembly Area</li>
                    </ul>
                </div>
            </section>

            <!-- Emergency Contact Information -->
            <section class="mb-4">
                <h4 class="text-md font-semibold text-gray-800 mb-2">Emergency Contact Information</h4>
                <div class="bg-gray-50 border p-2 rounded text-xs">
                    <p><strong>SKP JB Main:</strong> 07-598 0000 (S1), 07-595 1677 (S2)</p>
                    <p><strong>Security Department:</strong> Ext 141 (S1), Ext 147 (S2)</p>
                    <p><strong>Safety Department:</strong> 012-5768338 (S1), 016-7193083 (S2)</p>
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
                        I have read and understood the security guidelines.
                    </label>
                </div>

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

        <div v-if="videoEnded" class="flex items-center gap-2 text-green-600">
            <CheckCircle class="h-5 w-5" />
            <span>Security video completed</span>
        </div>
    </div>
</template>