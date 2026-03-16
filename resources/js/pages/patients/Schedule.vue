<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Textarea from 'primevue/textarea';
import PrimeButton from 'primevue/button';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
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
    title: string;
    date: string;
    time: string;
    type: 'appointment';
    status: string;
    location: string | null;
    notes: string | null;
};

const props = defineProps<{
    patient: { id: number; name: string };
    scheduleEvents: ScheduleEvent[];
    medicationEvents: MedicationEvent[];
    appointments: AppointmentEvent[];
    isAdmin: boolean;
}>();

const toast = useToast();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Patients', href: '/patients' },
    { title: props.patient.name, href: `/patients/${props.patient.id}` },
    { title: 'Schedule', href: `/patients/${props.patient.id}/schedule` },
];

const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const eventTypeOptions = [
    { label: 'Activity', value: 'activity' },
    { label: 'Custom', value: 'custom' },
];

const recurrenceTypeOptions = [
    { label: 'Daily', value: 'daily' },
    { label: 'Weekly', value: 'weekly' },
];

const dayOptions = [
    { label: 'Sun', value: 0 },
    { label: 'Mon', value: 1 },
    { label: 'Tue', value: 2 },
    { label: 'Wed', value: 3 },
    { label: 'Thu', value: 4 },
    { label: 'Fri', value: 5 },
    { label: 'Sat', value: 6 },
];

function eventColor(type: string): string {
    switch (type) {
        case 'medication': return '#3b82f6';
        case 'activity': return '#22c55e';
        case 'appointment': return '#f97316';
        default: return '#6b7280';
    }
}

function generateCalendarEvents(start: Date, end: Date) {
    const appointments: any[] = [];
    const activities: any[] = [];
    const medications: any[] = [];

    // Appointments (highest priority)
    for (const apt of props.appointments) {
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

        // Activities (second priority)
        for (const evt of props.scheduleEvents) {
            if (evt.recurrence_type === 'daily' || (evt.recurrence_days && evt.recurrence_days.includes(dow))) {
                activities.push({
                    title: `${evt.time_of_day.substring(0, 5)} ${evt.title}`,
                    date: dateStr,
                    color: eventColor(evt.event_type),
                    extendedProps: { ...evt, source: 'schedule' },
                });
            }
        }

        // Medications (lowest priority — repeated daily)
        for (const med of props.medicationEvents) {
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

function formatDateStr(d: Date): string {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function fetchEvents(info: any, successCallback: (events: any[]) => void) {
    successCallback(generateCalendarEvents(info.start, info.end));
}

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin],
    initialView: 'dayGridMonth',
    events: fetchEvents,
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: '',
    },
    height: 'auto',
    dayMaxEvents: 4,
    dateClick: (info: any) => showDayDetail(info.dateStr),
    eventClick: (info: any) => showDayDetail(info.event.startStr),
}));

// Day detail dialog
const showDayDialog = ref(false);
const selectedDate = ref('');
const selectedDateEvents = ref<any[]>([]);

function showDayDetail(dateStr: string) {
    selectedDate.value = dateStr;
    const dow = new Date(dateStr + 'T00:00:00').getDay();

    const events: any[] = [];

    for (const evt of props.scheduleEvents) {
        if (evt.recurrence_type === 'daily' || (evt.recurrence_days && evt.recurrence_days.includes(dow))) {
            events.push({ ...evt, source: 'schedule', color: eventColor(evt.event_type) });
        }
    }

    for (const med of props.medicationEvents) {
        if (med.recurrence_type === 'daily' || (med.recurrence_days && med.recurrence_days.includes(dow))) {
            events.push({ ...med, time_of_day: med.time, source: 'medication', color: eventColor('medication') });
        }
    }

    for (const apt of props.appointments) {
        if (apt.date === dateStr && apt.status !== 'cancelled') {
            events.push({ ...apt, time_of_day: apt.time, source: 'appointment', color: eventColor('appointment') });
        }
    }

    events.sort((a, b) => (a.time_of_day || a.time || '').localeCompare(b.time_of_day || b.time || ''));
    selectedDateEvents.value = events;
    showDayDialog.value = true;
}

// Appointment notes editing
const editingNotesId = ref<number | null>(null);
const notesForm = useForm({ notes: '' });

function startEditNotes(apt: any) {
    const aptId = parseInt(apt.id.replace('apt-', ''));
    editingNotesId.value = aptId;
    notesForm.notes = apt.notes ?? '';
    notesForm.clearErrors();
}

function cancelEditNotes() {
    editingNotesId.value = null;
    notesForm.reset();
}

function saveNotes(apt: any) {
    const aptId = parseInt(apt.id.replace('apt-', ''));
    notesForm.patch(`/appointments/${aptId}/notes`, {
        preserveScroll: true,
        onSuccess: () => {
            editingNotesId.value = null;
            apt.notes = notesForm.notes;
            toast.add({ severity: 'success', summary: 'Saved', detail: 'Notes saved.', life: 3000 });
        },
    });
}

// ICS download
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

// Schedule event CRUD
const showEventDialog = ref(false);
const editingEvent = ref<ScheduleEvent | null>(null);
const eventTimeDate = ref<Date | null>(null);

const eventForm = useForm({
    title: '',
    event_type: 'activity',
    recurrence_type: 'weekly',
    recurrence_days: [] as number[],
    time_of_day: '',
    notes: '',
});

function openAddEventDialog() {
    editingEvent.value = null;
    eventForm.reset();
    eventForm.clearErrors();
    eventTimeDate.value = null;
    showEventDialog.value = true;
}

function openEditEventDialog(evt: ScheduleEvent) {
    editingEvent.value = evt;
    eventForm.title = evt.title;
    eventForm.event_type = evt.event_type;
    eventForm.recurrence_type = evt.recurrence_type;
    eventForm.recurrence_days = evt.recurrence_days ?? [];
    eventForm.time_of_day = evt.time_of_day;
    eventForm.notes = evt.notes ?? '';
    const match = evt.time_of_day.match(/^(\d{2}):(\d{2})/);
    if (match) {
        const d = new Date();
        d.setHours(parseInt(match[1]), parseInt(match[2]), 0, 0);
        eventTimeDate.value = d;
    }
    eventForm.clearErrors();
    showEventDialog.value = true;
}

function formatTimeFromDate(date: Date): string {
    return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
}

function submitEvent() {
    if (eventTimeDate.value) {
        eventForm.time_of_day = formatTimeFromDate(eventTimeDate.value);
    }

    if (editingEvent.value) {
        eventForm.put(`/schedule-events/${editingEvent.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                showEventDialog.value = false;
                toast.add({ severity: 'success', summary: 'Updated', detail: 'Schedule event updated.', life: 3000 });
            },
        });
    } else {
        eventForm.post(`/patients/${props.patient.id}/schedule-events`, {
            preserveScroll: true,
            onSuccess: () => {
                showEventDialog.value = false;
                eventForm.reset();
                toast.add({ severity: 'success', summary: 'Created', detail: 'Schedule event created.', life: 3000 });
            },
        });
    }
}

function confirmDeleteEvent(evt: ScheduleEvent) {
    if (confirm(`Delete "${evt.title}"?`)) {
        router.delete(`/schedule-events/${evt.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.add({ severity: 'success', summary: 'Deleted', detail: 'Schedule event deleted.', life: 3000 }),
        });
    }
}

function toggleDay(day: number) {
    const idx = eventForm.recurrence_days.indexOf(day);
    if (idx === -1) {
        eventForm.recurrence_days.push(day);
    } else {
        eventForm.recurrence_days.splice(idx, 1);
    }
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
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Schedule - ${patient.name}`" />
        <Toast />

        <div class="mx-auto max-w-6xl p-6 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-2xl font-semibold">{{ patient.name }} &mdash; Schedule</h1>
                <Button v-if="isAdmin" @click="openAddEventDialog">
                    <i class="pi pi-plus mr-1" /> Add Event
                </Button>
            </div>

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

            <div class="rounded-lg border p-4">
                <FullCalendar :options="calendarOptions" />
            </div>

            <!-- Schedule Events List -->
            <div v-if="scheduleEvents.length > 0">
                <h2 class="mb-3 text-lg font-medium">Schedule Events</h2>
                <div class="space-y-2">
                    <div v-for="evt in scheduleEvents" :key="evt.id" class="flex items-center gap-3 rounded-lg border p-3">
                        <span class="h-3 w-3 shrink-0 rounded-full" :style="{ background: eventColor(evt.event_type) }"></span>
                        <div class="flex-1 min-w-0">
                            <span class="font-medium">{{ evt.title }}</span>
                            <span class="ml-2 text-sm text-muted-foreground">
                                {{ formatTime(evt.time_of_day) }} &mdash;
                                {{ evt.recurrence_type === 'daily' ? 'Daily' : evt.recurrence_days?.map(d => dayNames[d]).join(', ') }}
                            </span>
                        </div>
                        <div v-if="isAdmin" class="flex gap-1">
                            <PrimeButton icon="pi pi-pencil" severity="secondary" text rounded size="small" @click="openEditEventDialog(evt)" />
                            <PrimeButton icon="pi pi-trash" severity="danger" text rounded size="small" @click="confirmDeleteEvent(evt)" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Day Detail Dialog -->
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
                        <div class="flex items-center gap-1">
                            <span class="font-medium">{{ evt.title }}</span>
                            <PrimeButton v-if="evt.source === 'appointment'" icon="pi pi-calendar-plus" severity="secondary" text rounded size="small" class="!w-7 !h-7" @click="downloadIcs({ ...evt, date: selectedDate })" v-tooltip.top="'Add to Calendar'" />
                        </div>
                        <div class="text-sm text-muted-foreground">
                            <template v-if="evt.source === 'medication'">Medication</template>
                            <template v-else-if="evt.source === 'appointment'">Appointment</template>
                            <template v-else-if="evt.event_type">{{ evt.event_type }}</template>
                            <template v-if="evt.location"> · {{ evt.location }}</template>
                        </div>

                        <template v-if="evt.source === 'appointment'">
                            <template v-if="editingNotesId === parseInt(evt.id.replace('apt-', ''))">
                                <Textarea v-model="notesForm.notes" rows="2" class="mt-2 w-full" placeholder="Add notes..." />
                                <div class="mt-1 flex gap-2">
                                    <PrimeButton label="Save" size="small" :loading="notesForm.processing" @click="saveNotes(evt)" />
                                    <PrimeButton label="Cancel" severity="secondary" size="small" @click="cancelEditNotes" />
                                </div>
                            </template>
                            <template v-else>
                                <div v-if="evt.notes" class="mt-1 text-sm text-muted-foreground">{{ evt.notes }}</div>
                                <PrimeButton label="Edit Notes" severity="secondary" text size="small" icon="pi pi-pencil" class="mt-1" @click="startEditNotes(evt)" />
                            </template>
                        </template>
                        <div v-else-if="evt.notes" class="mt-1 text-sm text-muted-foreground">{{ evt.notes }}</div>
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- Add/Edit Event Dialog -->
        <Dialog v-model:visible="showEventDialog" :header="editingEvent ? 'Edit Schedule Event' : 'Add Schedule Event'" modal :style="{ width: '28rem' }">
            <form @submit.prevent="submitEvent" class="space-y-4">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Title *</label>
                    <InputText v-model="eventForm.title" placeholder="e.g., DDC, Active Aging Center" :invalid="!!eventForm.errors.title" />
                    <small v-if="eventForm.errors.title" class="text-red-500">{{ eventForm.errors.title }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Event Type *</label>
                    <Select v-model="eventForm.event_type" :options="eventTypeOptions" optionLabel="label" optionValue="value" :invalid="!!eventForm.errors.event_type" />
                    <small v-if="eventForm.errors.event_type" class="text-red-500">{{ eventForm.errors.event_type }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Time *</label>
                    <DatePicker v-model="eventTimeDate" timeOnly hourFormat="12" :invalid="!!eventForm.errors.time_of_day" />
                    <small v-if="eventForm.errors.time_of_day" class="text-red-500">{{ eventForm.errors.time_of_day }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Recurrence *</label>
                    <Select v-model="eventForm.recurrence_type" :options="recurrenceTypeOptions" optionLabel="label" optionValue="value" :invalid="!!eventForm.errors.recurrence_type" />
                    <small v-if="eventForm.errors.recurrence_type" class="text-red-500">{{ eventForm.errors.recurrence_type }}</small>
                </div>

                <div v-if="eventForm.recurrence_type === 'weekly'" class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Days *</label>
                    <div class="flex flex-wrap gap-2">
                        <PrimeButton
                            v-for="day in dayOptions"
                            :key="day.value"
                            :label="day.label"
                            :severity="eventForm.recurrence_days.includes(day.value) ? undefined : 'secondary'"
                            size="small"
                            type="button"
                            @click="toggleDay(day.value)"
                        />
                    </div>
                    <small v-if="eventForm.errors.recurrence_days" class="text-red-500">{{ eventForm.errors.recurrence_days }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Notes</label>
                    <Textarea v-model="eventForm.notes" rows="2" />
                </div>

                <div class="flex justify-end gap-2">
                    <PrimeButton label="Cancel" severity="secondary" @click="showEventDialog = false" />
                    <PrimeButton type="submit" :label="editingEvent ? 'Update' : 'Add'" :loading="eventForm.processing" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
