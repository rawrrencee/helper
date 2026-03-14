<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type UpcomingAppointment = {
    id: number;
    title: string;
    doctor: string | null;
    appointment_date: string;
    appointment_time: string | null;
    location: string | null;
    status: string;
};

type AdminData = {
    role: 'admin';
    totalHelpers: number;
    recentPayments: {
        id: number;
        month: number;
        year: number;
        total_amount: string;
        paid_at: string | null;
        helper: { id: number; name: string };
    }[];
    unpaidCurrentMonth: number;
    helpersWithoutPayment: number;
    upcomingAppointments: UpcomingAppointment[];
    familyInformation: string | null;
};

type MedicationItem = {
    id: number;
    name: string;
    dosage: string | null;
    frequency: string;
    notes: string | null;
};

type PatientMedications = {
    patient_name: string;
    patient_id: number;
    medications: MedicationItem[];
};

type HelperData = {
    role: 'helper';
    helper: { id: number; name: string; fin: string; nationality: string | null; monthly_salary: string } | null;
    recentPayments: {
        id: number;
        month: number;
        year: number;
        total_amount: string;
        paid_at: string | null;
    }[];
    documentsCount: number;
    upcomingAppointments: UpcomingAppointment[];
    familyInformation: string | null;
    patientMedications: PatientMedications[];
};

const props = defineProps<{
    dashboardData: AdminData | HelperData;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
];

function formatFrequency(freq: string): string {
    const match = freq.match(/^(\d{2}):(\d{2})$/);
    if (!match) return freq;
    const h = parseInt(match[1]);
    const m = match[2];
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h % 12 || 12;
    return `${h12}:${m} ${ampm}`;
}

const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Admin Dashboard -->
            <template v-if="dashboardData.role === 'admin'">
                <h1 class="text-2xl font-semibold">Admin Dashboard</h1>

                <div class="grid gap-4 md:grid-cols-3">
                    <Card>
                        <template #title>Total Helpers</template>
                        <template #content>
                            <div class="text-3xl font-bold">{{ (dashboardData as AdminData).totalHelpers }}</div>
                        </template>
                    </Card>

                    <Card>
                        <template #title>Unpaid This Month</template>
                        <template #content>
                            <div class="text-3xl font-bold text-orange-500">{{ (dashboardData as AdminData).unpaidCurrentMonth }}</div>
                        </template>
                    </Card>

                    <Card>
                        <template #title>No Payment Record</template>
                        <template #content>
                            <div class="text-3xl font-bold text-red-500">{{ (dashboardData as AdminData).helpersWithoutPayment }}</div>
                            <p class="mt-1 text-sm text-muted-foreground">helpers without a record this month</p>
                        </template>
                    </Card>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <Link href="/helpers/create">
                        <Card class="cursor-pointer transition hover:shadow-md">
                            <template #title>
                                <span class="flex items-center gap-2"><i class="pi pi-plus-circle"></i> Add Helper</span>
                            </template>
                            <template #content>
                                <p class="text-sm text-muted-foreground">Register a new domestic worker</p>
                            </template>
                        </Card>
                    </Link>
                    <Link href="/salary-payments/create">
                        <Card class="cursor-pointer transition hover:shadow-md">
                            <template #title>
                                <span class="flex items-center gap-2"><i class="pi pi-dollar"></i> Create Salary Record</span>
                            </template>
                            <template #content>
                                <p class="text-sm text-muted-foreground">Record a new salary payment</p>
                            </template>
                        </Card>
                    </Link>
                </div>

                <Card v-if="(dashboardData as AdminData).recentPayments.length > 0">
                    <template #title>Recent Salary Payments</template>
                    <template #content>
                        <div class="space-y-3">
                            <div v-for="payment in (dashboardData as AdminData).recentPayments" :key="payment.id"
                                class="flex items-center justify-between rounded-lg border p-3">
                                <div>
                                    <Link :href="`/helpers/${payment.helper.id}`" class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                                        {{ payment.helper.name }}
                                    </Link>
                                    <p class="text-sm text-muted-foreground">{{ months[payment.month - 1] }} {{ payment.year }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="font-medium">${{ Number(payment.total_amount).toFixed(2) }}</span>
                                    <Tag v-if="payment.paid_at" severity="success" value="Paid" />
                                    <Tag v-else severity="warn" value="Unpaid" />
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <div class="grid gap-4 md:grid-cols-2">
                    <Card>
                        <template #title>Upcoming Appointments</template>
                        <template #content>
                            <div v-if="(dashboardData as AdminData).upcomingAppointments.length > 0" class="space-y-3">
                                <div v-for="appt in (dashboardData as AdminData).upcomingAppointments" :key="appt.id" class="flex items-center justify-between rounded border p-2">
                                    <div>
                                        <p class="text-sm font-medium">{{ appt.title }}</p>
                                        <p v-if="appt.doctor" class="text-xs text-muted-foreground">Dr. {{ appt.doctor }}</p>
                                    </div>
                                    <span class="text-xs text-muted-foreground">{{ new Date(appt.appointment_date).toLocaleDateString('en-SG', { day: 'numeric', month: 'short' }) }}</span>
                                </div>
                            </div>
                            <p v-else class="text-sm text-muted-foreground">No upcoming appointments.</p>
                            <div class="mt-3">
                                <Link href="/appointments">
                                    <Button label="View All" severity="secondary" size="small" />
                                </Link>
                            </div>
                        </template>
                    </Card>

                    <Card>
                        <template #title>Family Info</template>
                        <template #content>
                            <div v-if="(dashboardData as AdminData).familyInformation" class="prose dark:prose-invert max-w-none text-sm line-clamp-4" v-html="(dashboardData as AdminData).familyInformation" />
                            <p v-else class="text-sm text-muted-foreground">No family information available.</p>
                            <div class="mt-3">
                                <Link href="/family-info">
                                    <Button label="View" severity="secondary" size="small" />
                                </Link>
                            </div>
                        </template>
                    </Card>
                </div>
            </template>

            <!-- Helper Dashboard -->
            <template v-else>
                <h1 class="text-2xl font-semibold">My Dashboard</h1>

                <template v-if="(dashboardData as HelperData).helper">
                    <Card>
                        <template #title>My Profile</template>
                        <template #content>
                            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                                <div>
                                    <span class="text-sm text-muted-foreground">Name</span>
                                    <p class="font-medium">{{ (dashboardData as HelperData).helper!.name }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-muted-foreground">FIN</span>
                                    <p class="font-medium">{{ (dashboardData as HelperData).helper!.fin }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-muted-foreground">Nationality</span>
                                    <p class="font-medium">{{ (dashboardData as HelperData).helper!.nationality ?? '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-muted-foreground">Monthly Salary</span>
                                    <p class="font-medium">${{ Number((dashboardData as HelperData).helper!.monthly_salary).toFixed(2) }}</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <Link :href="`/helpers/${(dashboardData as HelperData).helper!.id}`">
                                    <Button label="View Full Profile" severity="secondary" size="small" />
                                </Link>
                            </div>
                        </template>
                    </Card>

                    <Card>
                        <template #title>Medications Schedule</template>
                        <template #content>
                            <div v-if="(dashboardData as HelperData).patientMedications.length > 0" class="space-y-6">
                                <div v-for="group in (dashboardData as HelperData).patientMedications" :key="group.patient_id">
                                    <div class="mb-2 flex items-center justify-between">
                                        <h3 class="text-sm font-semibold">{{ group.patient_name }}</h3>
                                        <Link :href="`/patients/${group.patient_id}`">
                                            <Button label="View Patient" severity="secondary" text size="small" />
                                        </Link>
                                    </div>
                                    <div class="space-y-2">
                                        <div v-for="med in group.medications" :key="med.id" class="flex items-center gap-3 rounded border p-2">
                                            <Tag :value="formatFrequency(med.frequency)" severity="info" />
                                            <div>
                                                <span class="text-sm font-medium">{{ med.name }}</span>
                                                <span v-if="med.dosage" class="ml-1 text-sm text-muted-foreground">({{ med.dosage }})</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-sm text-muted-foreground">No medications to track.</p>
                        </template>
                    </Card>

                    <div class="grid gap-4 md:grid-cols-2">
                        <Card>
                            <template #title>Recent Salary</template>
                            <template #content>
                                <div v-if="(dashboardData as HelperData).recentPayments.length > 0" class="space-y-3">
                                    <div v-for="payment in (dashboardData as HelperData).recentPayments" :key="payment.id"
                                        class="flex items-center justify-between rounded border p-2">
                                        <span class="text-sm">{{ months[payment.month - 1] }} {{ payment.year }}</span>
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium">${{ Number(payment.total_amount).toFixed(2) }}</span>
                                            <Tag v-if="payment.paid_at" severity="success" value="Paid" />
                                            <Tag v-else severity="warn" value="Unpaid" />
                                        </div>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-muted-foreground">No salary records yet.</p>
                                <div class="mt-3">
                                    <Link href="/salary-payments">
                                        <Button label="View All" severity="secondary" size="small" />
                                    </Link>
                                </div>
                            </template>
                        </Card>

                        <Card>
                            <template #title>Documents</template>
                            <template #content>
                                <p class="text-3xl font-bold">{{ (dashboardData as HelperData).documentsCount }}</p>
                                <p class="text-sm text-muted-foreground">documents on file</p>
                                <div class="mt-3">
                                    <Link :href="`/helpers/${(dashboardData as HelperData).helper!.id}/documents`">
                                        <Button label="View Documents" severity="secondary" size="small" />
                                    </Link>
                                </div>
                            </template>
                        </Card>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <Card>
                            <template #title>Upcoming Appointments</template>
                            <template #content>
                                <div v-if="(dashboardData as HelperData).upcomingAppointments.length > 0" class="space-y-3">
                                    <div v-for="appt in (dashboardData as HelperData).upcomingAppointments" :key="appt.id" class="flex items-center justify-between rounded border p-2">
                                        <div>
                                            <p class="text-sm font-medium">{{ appt.title }}</p>
                                            <p v-if="appt.doctor" class="text-xs text-muted-foreground">Dr. {{ appt.doctor }}</p>
                                        </div>
                                        <span class="text-xs text-muted-foreground">{{ new Date(appt.appointment_date).toLocaleDateString('en-SG', { day: 'numeric', month: 'short' }) }}</span>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-muted-foreground">No upcoming appointments.</p>
                                <div class="mt-3">
                                    <Link href="/appointments">
                                        <Button label="View All" severity="secondary" size="small" />
                                    </Link>
                                </div>
                            </template>
                        </Card>

                        <Card>
                            <template #title>Family Info</template>
                            <template #content>
                                <div v-if="(dashboardData as HelperData).familyInformation" class="prose dark:prose-invert max-w-none text-sm line-clamp-4" v-html="(dashboardData as HelperData).familyInformation" />
                                <p v-else class="text-sm text-muted-foreground">No family information available.</p>
                                <div class="mt-3">
                                    <Link href="/family-info">
                                        <Button label="View" severity="secondary" size="small" />
                                    </Link>
                                </div>
                            </template>
                        </Card>
                    </div>
                </template>

                <Card v-else>
                    <template #content>
                        <p class="text-muted-foreground">Your profile has not been set up yet. Please contact your employer.</p>
                    </template>
                </Card>
            </template>
        </div>
    </AppLayout>
</template>
