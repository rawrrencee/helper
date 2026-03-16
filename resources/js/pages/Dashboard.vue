<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import Card from 'primevue/card';
import Carousel from 'primevue/carousel';
import Dialog from 'primevue/dialog';
import PrimeButton from 'primevue/button';
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

type ScheduleEvent = {
    id: number;
    title: string;
    event_type: string;
    recurrence_type: string;
    recurrence_days: number[] | null;
    time_of_day: string;
    notes: string | null;
    is_active: boolean;
};

type MedicationCalEvent = {
    id: string;
    title: string;
    time: string;
    type: 'medication';
    is_optional: boolean;
    recurrence_type: string;
    recurrence_days: number[] | null;
};

type AppointmentCalEvent = {
    id: string;
    appointment_id: number;
    title: string;
    date: string;
    time: string;
    type: 'appointment';
    status: string;
    location: string | null;
    doctor: string | null;
    notes: string | null;
};

type PatientSchedule = {
    patient: { id: number; name: string };
    scheduleEvents: ScheduleEvent[];
    medicationEvents: MedicationCalEvent[];
    appointments: AppointmentCalEvent[];
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
    patientOptionalMedications: PatientMedications[];
    patientSchedules: PatientSchedule[];
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

function getFrequencySeverity(freq: string): string {
    switch (freq) {
        case 'After Breakfast': return 'success';
        case 'After Lunch': return 'warn';
        case 'After Dinner': return 'danger';
        case 'Before Sleep': return 'contrast';
        case '2 Times a Day':
        case '3 Times a Day': return 'secondary';
        default: return /^\d{2}:\d{2}$/.test(freq) ? 'info' : 'info';
    }
}

const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

// Calendar widget for helper dashboard
const currentPatientIndex = ref(0);

function eventColor(type: string): string {
    switch (type) {
        case 'medication': return '#3b82f6';
        case 'activity': return '#22c55e';
        case 'appointment': return '#f97316';
        default: return '#6b7280';
    }
}

function formatDateStr(d: Date): string {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function generateCalendarEvents(schedule: PatientSchedule, start: Date, end: Date) {
    const appointments: any[] = [];
    const activities: any[] = [];
    const medications: any[] = [];

    for (const apt of schedule.appointments) {
        if (apt.status !== 'cancelled') {
            appointments.push({
                title: `${apt.time ? apt.time.substring(0, 5) + ' ' : ''}${apt.title}`,
                date: apt.date,
                color: eventColor('appointment'),
                extendedProps: { ...apt, source: 'appointment' },
            });
        }
    }

    for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
        const dow = d.getDay();
        const dateStr = formatDateStr(d);

        for (const evt of schedule.scheduleEvents) {
            if (evt.recurrence_type === 'daily' || (evt.recurrence_days && evt.recurrence_days.includes(dow))) {
                activities.push({
                    title: `${evt.time_of_day.substring(0, 5)} ${evt.title}`,
                    date: dateStr,
                    color: eventColor(evt.event_type),
                    extendedProps: { ...evt, source: 'schedule' },
                });
            }
        }

        for (const med of schedule.medicationEvents) {
            if (med.recurrence_type === 'daily' || (med.recurrence_days && med.recurrence_days.includes(dow))) {
                medications.push({
                    title: `${med.time.substring(0, 5)} ${med.title}`,
                    date: dateStr,
                    color: med.is_optional ? '#93c5fd' : '#3b82f6',
                    extendedProps: { ...med, source: 'medication' },
                });
            }
        }
    }

    return [...appointments, ...activities, ...medications];
}

function calendarOptions(schedule: PatientSchedule) {
    return {
        plugins: [dayGridPlugin],
        initialView: 'dayGridMonth',
        events: (info: any, successCallback: (events: any[]) => void) => {
            successCallback(generateCalendarEvents(schedule, info.start, info.end));
        },
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: '',
        },
        height: 'auto',
        dayMaxEvents: 4,
        dateClick: (info: any) => showDayDetail(schedule, info.dateStr),
        eventClick: (info: any) => showDayDetail(schedule, info.event.startStr),
    };
}

const showDayDialog = ref(false);
const selectedDate = ref('');
const selectedDateEvents = ref<any[]>([]);

function showDayDetail(schedule: PatientSchedule, dateStr: string) {
    selectedDate.value = dateStr;
    const dow = new Date(dateStr + 'T00:00:00').getDay();
    const events: any[] = [];

    for (const evt of schedule.scheduleEvents) {
        if (evt.recurrence_type === 'daily' || (evt.recurrence_days && evt.recurrence_days.includes(dow))) {
            events.push({ ...evt, source: 'schedule', color: eventColor(evt.event_type) });
        }
    }

    for (const med of schedule.medicationEvents) {
        if (med.recurrence_type === 'daily' || (med.recurrence_days && med.recurrence_days.includes(dow))) {
            events.push({ ...med, time_of_day: med.time, source: 'medication', color: eventColor('medication') });
        }
    }

    for (const apt of schedule.appointments) {
        if (apt.date === dateStr && apt.status !== 'cancelled') {
            events.push({ ...apt, time_of_day: apt.time, source: 'appointment', color: eventColor('appointment') });
        }
    }

    events.sort((a, b) => (a.time_of_day || a.time || '').localeCompare(b.time_of_day || b.time || ''));
    selectedDateEvents.value = events;
    showDayDialog.value = true;
}

function formatTime(time: string | null): string {
    if (!time) return '';
    const match = time.match(/^(\d{2}):(\d{2})/);
    if (!match) return time;
    const h = parseInt(match[1]);
    const m = match[2];
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h % 12 || 12;
    return `${h12}:${m} ${ampm}`;
}

function formatSelectedDate(): string {
    if (!selectedDate.value) return '';
    const d = new Date(selectedDate.value + 'T00:00:00');
    return d.toLocaleDateString('en-SG', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
}

function downloadIcs(apt: any) {
    const date = apt.date || apt.appointment_date;
    const time = apt.time || apt.appointment_time || apt.time_of_day;
    const dateClean = date.replace(/-/g, '');

    const lines = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'CALSCALE:GREGORIAN',
        'PRODID:-//Helper//Appointment//EN',
        'BEGIN:VEVENT',
        `UID:${Date.now()}-${Math.random().toString(36).substring(2)}@helper`,
    ];

    if (time) {
        const timeClean = time.replace(/:/g, '').substring(0, 4) + '00';
        lines.push(`DTSTART:${dateClean}T${timeClean}`);
        const startHour = parseInt(time.substring(0, 2));
        const endHour = (startHour + 1) % 24;
        const endTime = `${String(endHour).padStart(2, '0')}${timeClean.substring(2)}`;
        lines.push(`DTEND:${dateClean}T${endTime}`);
    } else {
        lines.push(`DTSTART;VALUE=DATE:${dateClean}`);
        lines.push(`DTEND;VALUE=DATE:${dateClean}`);
    }

    lines.push(`SUMMARY:${apt.title}`);
    if (apt.location) lines.push(`LOCATION:${apt.location}`);
    const descParts = [apt.notes, apt.doctor ? `Doctor: ${apt.doctor}` : ''].filter(Boolean);
    if (descParts.length) lines.push(`DESCRIPTION:${descParts.join(' | ')}`);
    lines.push('END:VEVENT', 'END:VCALENDAR');

    const blob = new Blob([lines.join('\r\n')], { type: 'text/calendar;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${apt.title.replace(/[^a-zA-Z0-9]/g, '_')}.ics`;
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Admin Dashboard -->
            <template v-if="dashboardData.role === 'admin'">
                <h1 class="text-2xl font-semibold">Admin Dashboard</h1>

                <div class="grid gap-4 md:grid-cols-2">
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
                            <div v-if="(dashboardData as AdminData).familyInformation" class="prose prose-sm dark:prose-invert max-w-none [&_p]:my-1" v-html="(dashboardData as AdminData).familyInformation" />
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
                    <div class="grid gap-4 md:grid-cols-2">
                        <!-- Calendar Widget -->
                        <Card v-if="(dashboardData as HelperData).patientSchedules.length > 0">
                            <template #title>Schedule Calendar</template>
                            <template #content>
                                <Carousel
                                    v-if="(dashboardData as HelperData).patientSchedules.length > 1"
                                    :value="(dashboardData as HelperData).patientSchedules"
                                    :numVisible="1"
                                    :numScroll="1"
                                    :page="currentPatientIndex"
                                    @update:page="currentPatientIndex = $event"
                                >
                                    <template #item="{ data: schedule, index }">
                                        <div class="px-1">
                                            <div class="mb-2 flex items-center justify-between">
                                                <h3 class="text-sm font-semibold">{{ schedule.patient.name }}</h3>
                                                <Link :href="`/patients/${schedule.patient.id}/schedule`">
                                                    <Button label="Full Schedule" severity="secondary" text size="small" />
                                                </Link>
                                            </div>
                                            <FullCalendar v-if="currentPatientIndex === index" :options="calendarOptions(schedule)" />
                                        </div>
                                    </template>
                                </Carousel>
                                <template v-else>
                                    <div class="mb-2 flex items-center justify-between">
                                        <h3 class="text-sm font-semibold">{{ (dashboardData as HelperData).patientSchedules[0].patient.name }}</h3>
                                        <Link :href="`/patients/${(dashboardData as HelperData).patientSchedules[0].patient.id}/schedule`">
                                            <Button label="Full Schedule" severity="secondary" text size="small" />
                                        </Link>
                                    </div>
                                    <FullCalendar :options="calendarOptions((dashboardData as HelperData).patientSchedules[0])" />
                                </template>

                                <div class="mt-2 flex flex-wrap gap-3 text-xs">
                                    <div class="flex items-center gap-1">
                                        <span class="h-2 w-2 rounded-full" style="background: #3b82f6"></span>
                                        <span>Meds</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="h-2 w-2 rounded-full" style="background: #22c55e"></span>
                                        <span>Activities</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="h-2 w-2 rounded-full" style="background: #f97316"></span>
                                        <span>Appts</span>
                                    </div>
                                </div>
                            </template>
                        </Card>

                        <!-- Medications -->
                        <Card>
                            <template #title>Medications Schedule</template>
                            <template #content>
                                <div class="max-h-[600px] overflow-y-auto">
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
                                                    <Tag :value="formatFrequency(med.frequency)" :severity="getFrequencySeverity(med.frequency)" />
                                                    <div>
                                                        <span class="text-sm font-medium">{{ med.name }}</span>
                                                        <span v-if="med.dosage" class="ml-1 text-sm text-muted-foreground">({{ med.dosage }})</span>
                                                        <p v-if="med.notes" class="text-xs text-muted-foreground">{{ med.notes }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-else class="text-sm text-muted-foreground">No scheduled medications to track.</p>

                                    <!-- Optional / If Needed -->
                                    <div v-if="(dashboardData as HelperData).patientOptionalMedications.length > 0" class="mt-6 border-t pt-4">
                                        <h4 class="mb-3 text-sm font-medium text-muted-foreground">Optional / If Needed</h4>
                                        <div class="space-y-4 opacity-75">
                                            <div v-for="group in (dashboardData as HelperData).patientOptionalMedications" :key="group.patient_id">
                                                <h3 class="mb-1 text-xs font-semibold text-muted-foreground">{{ group.patient_name }}</h3>
                                                <div class="space-y-1">
                                                    <div v-for="med in group.medications" :key="med.id" class="flex items-center gap-3 rounded border border-dashed p-2">
                                                        <Tag :value="formatFrequency(med.frequency)" severity="secondary" />
                                                        <div>
                                                            <span class="text-sm">{{ med.name }}</span>
                                                            <span v-if="med.dosage" class="ml-1 text-sm text-muted-foreground">({{ med.dosage }})</span>
                                                            <p v-if="med.notes" class="text-xs text-muted-foreground">{{ med.notes }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>

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
                            <div v-if="(dashboardData as HelperData).familyInformation" class="prose prose-sm dark:prose-invert max-w-none [&_p]:my-1" v-html="(dashboardData as HelperData).familyInformation" />
                            <p v-else class="text-sm text-muted-foreground">No family information available.</p>
                            <div class="mt-3">
                                <Link href="/family-info">
                                    <Button label="View" severity="secondary" size="small" />
                                </Link>
                            </div>
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
                </template>

                <Card v-else>
                    <template #content>
                        <p class="text-muted-foreground">Your profile has not been set up yet. Please contact your employer.</p>
                    </template>
                </Card>
            </template>
        </div>

        <!-- Day Detail Dialog (Calendar Widget) -->
        <Dialog v-model:visible="showDayDialog" :header="formatSelectedDate()" modal :dismissableMask="true" :style="{ width: '32rem' }">
            <div v-if="selectedDateEvents.length === 0" class="py-4 text-center text-muted-foreground">
                No events on this day.
            </div>
            <div v-else class="space-y-3">
                <div v-for="(evt, idx) in selectedDateEvents" :key="idx" class="flex items-start gap-3 rounded-lg border p-3">
                    <span class="mt-1.5 h-3 w-3 shrink-0 rounded-full" :style="{ background: evt.color }"></span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ evt.title }}</span>
                            <PrimeButton v-if="evt.source === 'appointment'" icon="pi pi-calendar-plus" severity="secondary" text rounded size="small" class="!w-7 !h-7" @click="downloadIcs({ ...evt, date: selectedDate })" v-tooltip.top="'Add to Calendar'" />
                        </div>
                        <div class="text-sm text-muted-foreground">
                            {{ formatTime(evt.time_of_day || evt.time) }}
                            <template v-if="evt.source === 'medication'"> &mdash; Medication</template>
                            <template v-else-if="evt.source === 'appointment'"> &mdash; Appointment</template>
                            <template v-else-if="evt.event_type"> &mdash; {{ evt.event_type }}</template>
                        </div>
                        <div v-if="evt.notes" class="mt-1 text-sm text-muted-foreground">{{ evt.notes }}</div>
                        <div v-if="evt.location" class="mt-1 text-sm text-muted-foreground">Location: {{ evt.location }}</div>
                    </div>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
