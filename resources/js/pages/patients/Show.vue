<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import DatePicker from 'primevue/datepicker';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import Checkbox from 'primevue/checkbox';
import PrimeButton from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Medication = {
    id: number;
    name: string;
    dosage: string | null;
    frequency: string;
    is_optional: boolean;
    notes: string | null;
};

type Patient = {
    id: number;
    name: string;
    nric?: string;
    masked_nric: string;
    age: number | null;
    phone: string | null;
    address: string | null;
    date_of_birth: string | null;
    helpers: { id: number; name: string }[];
    medications: Medication[];
};

const props = defineProps<{
    patient: Patient;
    isAdmin: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Patients', href: '/patients' },
    { title: props.patient.name, href: `/patients/${props.patient.id}` },
];

const toast = useToast();

const frequencyOptions = [
    { label: '2 Times a Day', value: '2 Times a Day' },
    { label: '3 Times a Day', value: '3 Times a Day' },
    { label: 'After Breakfast', value: 'After Breakfast' },
    { label: 'After Lunch', value: 'After Lunch' },
    { label: 'After Dinner', value: 'After Dinner' },
    { label: 'Before Sleep', value: 'Before Sleep' },
];

const scheduledMedications = computed(() => props.patient.medications.filter(m => !m.is_optional));
const optionalMedications = computed(() => props.patient.medications.filter(m => m.is_optional));

const showMedicationDialog = ref(false);
const editingMedication = ref<Medication | null>(null);
const useCustomFrequency = ref(false);
const customTime = ref<Date | null>(null);

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

watch(customTime, (val) => {
    if (val && useCustomFrequency.value) {
        medicationForm.frequency = formatTimeFromDate(val);
    }
});

const medicationForm = useForm({
    name: '',
    dosage: '',
    frequency: '',
    is_optional: false,
    notes: '',
});

function openAddMedicationDialog() {
    editingMedication.value = null;
    useCustomFrequency.value = false;
    customTime.value = null;
    medicationForm.reset();
    medicationForm.clearErrors();
    showMedicationDialog.value = true;
}

function openEditMedicationDialog(medication: Medication) {
    editingMedication.value = medication;
    medicationForm.name = medication.name;
    medicationForm.dosage = medication.dosage ?? '';
    medicationForm.frequency = medication.frequency;
    medicationForm.is_optional = medication.is_optional;
    medicationForm.notes = medication.notes ?? '';

    const isPreset = frequencyOptions.some(opt => opt.value === medication.frequency);
    useCustomFrequency.value = !isPreset;
    customTime.value = isPreset ? null : parseTimeToDate(medication.frequency);

    medicationForm.clearErrors();
    showMedicationDialog.value = true;
}

function submitMedication() {
    const data = {
        ...medicationForm.data(),
        dosage: medicationForm.dosage || null,
        notes: medicationForm.notes || null,
    };

    if (editingMedication.value) {
        medicationForm.transform(() => data).put(`/patients/${props.patient.id}/medications/${editingMedication.value.id}`, {
            onSuccess: () => {
                showMedicationDialog.value = false;
                toast.add({ severity: 'success', summary: 'Updated', detail: 'Medication updated.', life: 3000 });
            },
        });
    } else {
        medicationForm.transform(() => data).post(`/patients/${props.patient.id}/medications`, {
            onSuccess: () => {
                showMedicationDialog.value = false;
                medicationForm.reset();
                customTime.value = null;
                useCustomFrequency.value = false;
                toast.add({ severity: 'success', summary: 'Added', detail: 'Medication added.', life: 3000 });
            },
        });
    }
}

function confirmDeleteMedication(medication: Medication) {
    if (confirm(`Delete "${medication.name}"?`)) {
        medicationForm.delete(`/patients/${props.patient.id}/medications/${medication.id}`, {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Deleted', detail: 'Medication deleted.', life: 3000 }),
        });
    }
}

function confirmDeletePatient() {
    if (confirm(`Delete patient "${props.patient.name}"? This will also delete all their medications.`)) {
        medicationForm.delete(`/patients/${props.patient.id}`);
    }
}

function formatDateOfBirth(dateStr: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-SG', { day: 'numeric', month: 'short', year: 'numeric' });
}

function formatFrequency(freq: string): string {
    const match = freq.match(/^(\d{2}):(\d{2})$/);
    if (!match) return freq;
    const h = parseInt(match[1]);
    const m = match[2];
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h % 12 || 12;
    return `${h12}:${m} ${ampm}`;
}

function toggleCustomFrequency() {
    useCustomFrequency.value = !useCustomFrequency.value;
    medicationForm.frequency = '';
    customTime.value = null;
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="patient.name" />
        <Toast />

        <div class="mx-auto max-w-5xl p-6 space-y-8">
            <!-- Patient Details -->
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">{{ patient.name }}</h1>
                    <p class="text-muted-foreground">{{ isAdmin && patient.nric ? patient.nric : patient.masked_nric }}</p>
                </div>
                <div v-if="isAdmin" class="flex gap-2">
                    <Link :href="`/patients/${patient.id}/edit`">
                        <Button variant="outline">Edit</Button>
                    </Link>
                    <Button variant="destructive" @click="confirmDeletePatient">Delete</Button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="rounded-lg border p-4 space-y-3">
                    <h2 class="font-medium">Details</h2>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <span class="text-muted-foreground">Date of Birth</span>
                        <span>{{ formatDateOfBirth(patient.date_of_birth) }}</span>
                        <span class="text-muted-foreground">Age</span>
                        <span>{{ patient.age ?? '-' }}</span>
                        <span class="text-muted-foreground">Phone</span>
                        <span>{{ patient.phone ?? '-' }}</span>
                        <span class="text-muted-foreground">Address</span>
                        <span>{{ patient.address ?? '-' }}</span>
                    </div>
                </div>

                <div class="rounded-lg border p-4 space-y-3">
                    <h2 class="font-medium">Assigned Helpers</h2>
                    <div class="flex flex-wrap gap-2">
                        <Tag v-for="helper in patient.helpers" :key="helper.id" :value="helper.name" severity="info" />
                        <span v-if="patient.helpers.length === 0" class="text-sm text-muted-foreground">No helpers assigned</span>
                    </div>
                </div>
            </div>

            <!-- Scheduled Medications -->
            <div>
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-semibold">Scheduled Medications</h2>
                    <Button v-if="isAdmin" @click="openAddMedicationDialog">
                        <i class="pi pi-plus mr-1" /> Add Medication
                    </Button>
                </div>

                <DataTable :value="scheduledMedications" dataKey="id" stripedRows class="text-sm">
                    <Column field="name" header="Medication" />
                    <Column field="dosage" header="Dosage">
                        <template #body="{ data }">
                            {{ data.dosage ?? '-' }}
                        </template>
                    </Column>
                    <Column field="frequency" header="Frequency">
                        <template #body="{ data }">
                            {{ formatFrequency(data.frequency) }}
                        </template>
                    </Column>
                    <Column field="notes" header="Notes">
                        <template #body="{ data }">
                            {{ data.notes ?? '-' }}
                        </template>
                    </Column>
                    <Column v-if="isAdmin" header="" style="width: 8rem">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <PrimeButton icon="pi pi-pencil" severity="secondary" text rounded size="small" @click="openEditMedicationDialog(data)" />
                                <PrimeButton icon="pi pi-trash" severity="danger" text rounded size="small" @click="confirmDeleteMedication(data)" />
                            </div>
                        </template>
                    </Column>

                    <template #empty>
                        <div class="py-8 text-center text-muted-foreground">
                            No scheduled medications recorded.
                        </div>
                    </template>
                </DataTable>
            </div>

            <!-- Optional / If Needed Medications -->
            <div v-if="optionalMedications.length > 0">
                <h2 class="mb-4 text-xl font-semibold text-muted-foreground">Optional / If Needed</h2>

                <DataTable :value="optionalMedications" dataKey="id" stripedRows class="text-sm opacity-80">
                    <Column field="name" header="Medication" />
                    <Column field="dosage" header="Dosage">
                        <template #body="{ data }">
                            {{ data.dosage ?? '-' }}
                        </template>
                    </Column>
                    <Column field="frequency" header="Frequency">
                        <template #body="{ data }">
                            {{ formatFrequency(data.frequency) }}
                        </template>
                    </Column>
                    <Column field="notes" header="Notes">
                        <template #body="{ data }">
                            {{ data.notes ?? '-' }}
                        </template>
                    </Column>
                    <Column v-if="isAdmin" header="" style="width: 8rem">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <PrimeButton icon="pi pi-pencil" severity="secondary" text rounded size="small" @click="openEditMedicationDialog(data)" />
                                <PrimeButton icon="pi pi-trash" severity="danger" text rounded size="small" @click="confirmDeleteMedication(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- Medication Dialog -->
        <Dialog v-model:visible="showMedicationDialog" :header="editingMedication ? 'Edit Medication' : 'Add Medication'" modal :style="{ width: '28rem' }">
            <form @submit.prevent="submitMedication" class="space-y-4">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Medication Name *</label>
                    <InputText v-model="medicationForm.name" :invalid="!!medicationForm.errors.name" />
                    <small v-if="medicationForm.errors.name" class="text-red-500">{{ medicationForm.errors.name }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Dosage</label>
                    <InputText v-model="medicationForm.dosage" placeholder="e.g., 500mg" />
                </div>

                <div class="flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium">Frequency *</label>
                        <PrimeButton
                            :label="useCustomFrequency ? 'Use preset' : 'Enter time'"
                            text
                            size="small"
                            @click="toggleCustomFrequency"
                        />
                    </div>
                    <Select
                        v-if="!useCustomFrequency"
                        v-model="medicationForm.frequency"
                        :options="frequencyOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Select time"
                        :invalid="!!medicationForm.errors.frequency"
                    />
                    <DatePicker
                        v-else
                        v-model="customTime"
                        timeOnly
                        hourFormat="12"
                        :invalid="!!medicationForm.errors.frequency"
                    />
                    <small v-if="medicationForm.errors.frequency" class="text-red-500">{{ medicationForm.errors.frequency }}</small>
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox v-model="medicationForm.is_optional" :binary="true" inputId="is_optional" />
                    <label for="is_optional" class="text-sm font-medium">If Needed / Optional</label>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Notes</label>
                    <Textarea v-model="medicationForm.notes" rows="2" />
                </div>

                <div class="flex justify-end gap-2">
                    <PrimeButton label="Cancel" severity="secondary" @click="showMedicationDialog = false" />
                    <PrimeButton type="submit" :label="editingMedication ? 'Update' : 'Add'" :loading="medicationForm.processing" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
