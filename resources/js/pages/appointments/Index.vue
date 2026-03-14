<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import DatePicker from 'primevue/datepicker';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import PrimeButton from 'primevue/button';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Patient = {
    id: number;
    name: string;
};

type Appointment = {
    id: number;
    patient_id: number | null;
    patient: Patient | null;
    title: string;
    doctor: string | null;
    appointment_date: string;
    appointment_time: string | null;
    location: string | null;
    notes: string | null;
    status: string;
};

const props = defineProps<{
    upcomingAppointments: Appointment[];
    completedAppointments: Appointment[];
    patients?: Patient[];
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).role === 'admin');
const toast = useToast();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Appointments', href: '/appointments' },
];

// Admin appointment dialog
const showAppointmentDialog = ref(false);
const editingAppointment = ref<Appointment | null>(null);

const appointmentForm = useForm({
    patient_id: null as number | null,
    title: '',
    doctor: '',
    appointment_date: null as Date | null,
    appointment_time: '',
    location: '',
    notes: '',
    status: 'scheduled',
});

const appointmentTimeDate = ref<Date | null>(null);

function parseTimeToDate(time: string): Date | null {
    const match = time.match(/^(\d{2}):(\d{2})$/);
    if (!match) return null;
    const d = new Date();
    d.setHours(parseInt(match[1]), parseInt(match[2]), 0, 0);
    return d;
}

function formatTimeFromDate(date: Date): string {
    return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
}

watch(appointmentTimeDate, (val) => {
    appointmentForm.appointment_time = val ? formatTimeFromDate(val) : '';
});

const statusOptions = [
    { label: 'Scheduled', value: 'scheduled' },
    { label: 'Completed', value: 'completed' },
    { label: 'Cancelled', value: 'cancelled' },
];

function statusSeverity(status: string): "success" | "warn" | "danger" | "secondary" | "info" | "contrast" | undefined {
    switch (status) {
        case 'scheduled': return 'info';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function openAddDialog() {
    editingAppointment.value = null;
    appointmentForm.reset();
    appointmentForm.clearErrors();
    appointmentTimeDate.value = null;
    showAppointmentDialog.value = true;
}

function openEditDialog(appointment: Appointment) {
    editingAppointment.value = appointment;
    appointmentForm.patient_id = appointment.patient_id;
    appointmentForm.title = appointment.title;
    appointmentForm.doctor = appointment.doctor ?? '';
    appointmentForm.appointment_date = new Date(appointment.appointment_date);
    appointmentForm.appointment_time = appointment.appointment_time ?? '';
    appointmentForm.location = appointment.location ?? '';
    appointmentForm.notes = appointment.notes ?? '';
    appointmentForm.status = appointment.status;
    appointmentTimeDate.value = appointment.appointment_time ? parseTimeToDate(appointment.appointment_time) : null;
    appointmentForm.clearErrors();
    showAppointmentDialog.value = true;
}

function formatDate(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function submitAppointment() {
    const data = {
        ...appointmentForm.data(),
        patient_id: appointmentForm.patient_id || null,
        appointment_date: appointmentForm.appointment_date ? formatDate(appointmentForm.appointment_date) : null,
        appointment_time: appointmentForm.appointment_time || null,
        location: appointmentForm.location || null,
        notes: appointmentForm.notes || null,
        doctor: appointmentForm.doctor || null,
    };

    if (editingAppointment.value) {
        appointmentForm.transform(() => data).put(`/appointments/${editingAppointment.value!.id}`, {
            onSuccess: () => {
                showAppointmentDialog.value = false;
                toast.add({ severity: 'success', summary: 'Updated', detail: 'Appointment updated.', life: 3000 });
            },
        });
    } else {
        appointmentForm.transform(() => data).post('/appointments', {
            onSuccess: () => {
                showAppointmentDialog.value = false;
                appointmentForm.reset();
                appointmentTimeDate.value = null;
                toast.add({ severity: 'success', summary: 'Created', detail: 'Appointment created.', life: 3000 });
            },
        });
    }
}

function confirmDeleteAppointment(appointment: Appointment) {
    if (confirm(`Delete "${appointment.title}"?`)) {
        appointmentForm.delete(`/appointments/${appointment.id}`, {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Deleted', detail: 'Appointment deleted.', life: 3000 }),
        });
    }
}

function capitalize(str: string): string {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatDisplayDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-SG', { day: 'numeric', month: 'short', year: 'numeric' });
}

function formatDisplayTime(timeStr: string | null): string {
    if (!timeStr) return '';
    const [hours, minutes] = timeStr.split(':');
    const h = parseInt(hours);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h % 12 || 12;
    return `${h12}:${minutes} ${ampm}`;
}

// Helper notes editing
const editingNotesId = ref<number | null>(null);
const notesForm = useForm({
    notes: '',
});

function startEditNotes(appointment: Appointment) {
    editingNotesId.value = appointment.id;
    notesForm.notes = appointment.notes ?? '';
    notesForm.clearErrors();
}

function cancelEditNotes() {
    editingNotesId.value = null;
    notesForm.reset();
}

function saveNotes(appointment: Appointment) {
    notesForm.patch(`/appointments/${appointment.id}/notes`, {
        onSuccess: () => {
            editingNotesId.value = null;
            toast.add({ severity: 'success', summary: 'Saved', detail: 'Notes saved.', life: 3000 });
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Appointments" />
        <Toast />

        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Appointments</h1>
                <Button v-if="isAdmin" @click="openAddDialog">
                    <i class="pi pi-plus mr-1" /> Add Appointment
                </Button>
            </div>

            <Tabs value="upcoming" :pt="{ tabpanel: { style: 'padding: 0' } }">
                <TabList>
                    <Tab value="upcoming">Upcoming</Tab>
                    <Tab value="completed">Completed</Tab>
                </TabList>
                <TabPanels>
                    <TabPanel value="upcoming">
                        <div class="pt-4">
                            <div v-if="upcomingAppointments.length === 0" class="rounded-lg border p-8 text-center text-muted-foreground">
                                No upcoming appointments.
                            </div>
                            <div v-else class="grid gap-4 md:grid-cols-2">
                                <div v-for="appointment in upcomingAppointments" :key="appointment.id" class="rounded-lg border p-4 space-y-2">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h3 class="font-semibold">{{ appointment.title }}</h3>
                                            <p v-if="appointment.doctor" class="text-sm text-muted-foreground">Dr. {{ appointment.doctor }}</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Tag v-if="appointment.patient" :value="appointment.patient.name" severity="secondary" />
                                            <Tag :value="capitalize(appointment.status)" :severity="statusSeverity(appointment.status)" />
                                        </div>
                                    </div>

                                    <div class="text-sm text-muted-foreground space-y-1">
                                        <p><i class="pi pi-calendar mr-1" />{{ formatDisplayDate(appointment.appointment_date) }}<span v-if="appointment.appointment_time"> at {{ formatDisplayTime(appointment.appointment_time) }}</span></p>
                                        <p v-if="appointment.location"><i class="pi pi-map-marker mr-1" />{{ appointment.location }}</p>
                                    </div>

                                    <template v-if="editingNotesId === appointment.id">
                                        <Textarea v-model="notesForm.notes" rows="2" class="w-full" placeholder="Add notes..." />
                                        <small v-if="notesForm.errors.notes" class="text-red-500">{{ notesForm.errors.notes }}</small>
                                        <div class="flex gap-2">
                                            <PrimeButton label="Save" size="small" :loading="notesForm.processing" @click="saveNotes(appointment)" />
                                            <PrimeButton label="Cancel" severity="secondary" size="small" @click="cancelEditNotes" />
                                        </div>
                                    </template>
                                    <template v-else>
                                        <p v-if="appointment.notes" class="text-sm">{{ appointment.notes }}</p>
                                        <PrimeButton v-if="!isAdmin" label="Edit Notes" severity="secondary" text size="small" icon="pi pi-pencil" @click="startEditNotes(appointment)" />
                                    </template>

                                    <div v-if="isAdmin" class="flex gap-2 border-t pt-2">
                                        <PrimeButton icon="pi pi-pencil" severity="secondary" text rounded size="small" @click="openEditDialog(appointment)" />
                                        <PrimeButton icon="pi pi-trash" severity="danger" text rounded size="small" @click="confirmDeleteAppointment(appointment)" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                    <TabPanel value="completed">
                        <div class="pt-4">
                            <div v-if="completedAppointments.length === 0" class="rounded-lg border p-8 text-center text-muted-foreground">
                                No completed appointments.
                            </div>
                            <div v-else class="grid gap-4 md:grid-cols-2">
                                <div v-for="appointment in completedAppointments" :key="appointment.id" class="rounded-lg border p-4 space-y-2">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h3 class="font-semibold">{{ appointment.title }}</h3>
                                            <p v-if="appointment.doctor" class="text-sm text-muted-foreground">Dr. {{ appointment.doctor }}</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Tag v-if="appointment.patient" :value="appointment.patient.name" severity="secondary" />
                                            <Tag :value="capitalize(appointment.status)" :severity="statusSeverity(appointment.status)" />
                                        </div>
                                    </div>

                                    <div class="text-sm text-muted-foreground space-y-1">
                                        <p><i class="pi pi-calendar mr-1" />{{ formatDisplayDate(appointment.appointment_date) }}<span v-if="appointment.appointment_time"> at {{ formatDisplayTime(appointment.appointment_time) }}</span></p>
                                        <p v-if="appointment.location"><i class="pi pi-map-marker mr-1" />{{ appointment.location }}</p>
                                    </div>

                                    <p v-if="appointment.notes" class="text-sm">{{ appointment.notes }}</p>

                                    <div v-if="isAdmin" class="flex gap-2 border-t pt-2">
                                        <PrimeButton icon="pi pi-pencil" severity="secondary" text rounded size="small" @click="openEditDialog(appointment)" />
                                        <PrimeButton icon="pi pi-trash" severity="danger" text rounded size="small" @click="confirmDeleteAppointment(appointment)" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>

        <Dialog v-model:visible="showAppointmentDialog" :header="editingAppointment ? 'Edit Appointment' : 'Add Appointment'" modal :style="{ width: '30rem' }">
            <form @submit.prevent="submitAppointment" class="space-y-4">
                <div v-if="patients && patients.length > 0" class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Patient</label>
                    <Select v-model="appointmentForm.patient_id" :options="patients" optionLabel="name" optionValue="id" placeholder="Select patient (optional)" showClear />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Title *</label>
                    <InputText v-model="appointmentForm.title" :invalid="!!appointmentForm.errors.title" />
                    <small v-if="appointmentForm.errors.title" class="text-red-500">{{ appointmentForm.errors.title }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Doctor</label>
                    <InputText v-model="appointmentForm.doctor" placeholder="Doctor name" />
                    <small v-if="appointmentForm.errors.doctor" class="text-red-500">{{ appointmentForm.errors.doctor }}</small>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium">Date *</label>
                        <DatePicker v-model="appointmentForm.appointment_date" dateFormat="yy-mm-dd" showIcon :invalid="!!appointmentForm.errors.appointment_date" />
                        <small v-if="appointmentForm.errors.appointment_date" class="text-red-500">{{ appointmentForm.errors.appointment_date }}</small>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium">Time</label>
                        <DatePicker v-model="appointmentTimeDate" timeOnly hourFormat="24" placeholder="Select time" />
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Location</label>
                    <InputText v-model="appointmentForm.location" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Notes</label>
                    <Textarea v-model="appointmentForm.notes" rows="3" />
                </div>

                <div v-if="editingAppointment" class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Status</label>
                    <Select v-model="appointmentForm.status" :options="statusOptions" optionLabel="label" optionValue="value" />
                </div>

                <div class="flex justify-end gap-2">
                    <PrimeButton label="Cancel" severity="secondary" @click="showAppointmentDialog = false" />
                    <PrimeButton type="submit" :label="editingAppointment ? 'Update' : 'Create'" :loading="appointmentForm.processing" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
