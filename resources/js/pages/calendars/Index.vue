<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import Dialog from 'primevue/dialog';
import PrimeButton from 'primevue/button';
import Card from 'primevue/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

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

type MedicationEvent = {
    id: string;
    title: string;
    time: string;
    type: 'medication';
    is_optional: boolean;
    recurrence_type: string;
    recurrence_days: number[] | null;
};

type AppointmentEvent = {
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
    medicationEvents: MedicationEvent[];
    appointments: AppointmentEvent[];
};

const props = defineProps<{
    patientSchedules: PatientSchedule[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Calendars', href: '/calendars' },
];

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
                    color: '#3b82f6',
                    extendedProps: { ...med, source: 'medication' },
                });
            }
        }
    }

    // Group medications by date when >2 per day
    const medsByDate: Record<string, any[]> = {};
    for (const med of medications) {
        (medsByDate[med.date] ??= []).push(med);
    }
    const groupedMeds: any[] = [];
    for (const [date, meds] of Object.entries(medsByDate)) {
        if (meds.length <= 2) {
            groupedMeds.push(...meds);
        } else {
            groupedMeds.push({
                title: `💊 ${meds.length} medications`,
                date,
                color: '#3b82f6',
                extendedProps: { source: 'medication-group', count: meds.length },
            });
        }
    }

    return [...appointments, ...activities, ...groupedMeds];
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

// Day detail dialog
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
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Calendars" />

        <div class="flex flex-col gap-6 p-6">
            <h1 class="text-2xl font-semibold">Calendars</h1>

            <div class="flex flex-wrap gap-4 text-sm">
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full" style="background: #3b82f6"></span>
                    <span>Medications</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full" style="background: #22c55e"></span>
                    <span>Activities</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full" style="background: #f97316"></span>
                    <span>Appointments</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full" style="background: #6b7280"></span>
                    <span>Custom</span>
                </div>
            </div>

            <div v-if="patientSchedules.length === 0" class="rounded-lg border p-8 text-center text-muted-foreground">
                No patients with schedule data.
            </div>

            <div v-for="schedule in patientSchedules" :key="schedule.patient.id" class="space-y-2">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-medium">{{ schedule.patient.name }}</h2>
                    <Link :href="`/patients/${schedule.patient.id}/schedule`">
                        <Button variant="outline" size="sm">View Full Schedule</Button>
                    </Link>
                </div>
                <div class="rounded-lg border p-4">
                    <FullCalendar :options="calendarOptions(schedule)" />
                </div>
            </div>
        </div>

        <Dialog v-model:visible="showDayDialog" :header="formatSelectedDate()" modal :dismissableMask="true" :style="{ width: '32rem' }">
            <div v-if="selectedDateEvents.length === 0" class="py-4 text-center text-muted-foreground">
                No events on this day.
            </div>
            <div v-else class="space-y-3">
                <div v-for="(evt, idx) in selectedDateEvents" :key="idx" class="flex gap-3 rounded-lg border p-3">
                    <div class="w-16 shrink-0 text-right">
                        <span class="text-sm font-semibold">{{ formatTime(evt.time_of_day || evt.time) }}</span>
                    </div>
                    <div class="h-auto w-0.5 shrink-0 rounded-full" :style="{ background: evt.color }"></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ evt.title }}</span>
                            <PrimeButton v-if="evt.source === 'appointment'" icon="pi pi-calendar-plus" severity="secondary" text rounded size="small" class="!w-7 !h-7" @click="downloadIcs({ ...evt, date: selectedDate })" v-tooltip.top="'Add to Calendar'" />
                        </div>
                        <div class="text-sm text-muted-foreground">
                            <template v-if="evt.source === 'medication'">Medication</template>
                            <template v-else-if="evt.source === 'appointment'">Appointment</template>
                            <template v-else-if="evt.event_type">{{ evt.event_type }}</template>
                            <template v-if="evt.location"> · {{ evt.location }}</template>
                        </div>
                        <div v-if="evt.notes" class="mt-1 text-sm text-muted-foreground">{{ evt.notes }}</div>
                    </div>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
