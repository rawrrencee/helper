<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import DatePicker from 'primevue/datepicker';
import MultiSelect from 'primevue/multiselect';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    helpers: { id: number; name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Patients', href: '/patients' },
    { title: 'Add Patient', href: '/patients/create' },
];

const toast = useToast();

const form = useForm({
    name: '',
    nric: '',
    phone: '',
    address: '',
    date_of_birth: null as Date | null,
    helper_ids: [] as number[],
});

function formatDate(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function submit() {
    form.transform((data) => ({
        ...data,
        date_of_birth: data.date_of_birth ? formatDate(data.date_of_birth) : null,
        phone: data.phone || null,
        address: data.address || null,
    })).post('/patients', {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Created', detail: 'Patient created.', life: 3000 }),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Add Patient" />
        <Toast />

        <div class="mx-auto max-w-5xl p-6">
            <h1 class="mb-6 text-2xl font-semibold">Add Patient</h1>

            <form @submit.prevent="submit" class="space-y-8">
                <section>
                    <h2 class="mb-4 text-lg font-medium">Patient Information</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium">Name *</label>
                            <InputText v-model="form.name" :invalid="!!form.errors.name" />
                            <small v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</small>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium">NRIC *</label>
                            <InputText v-model="form.nric" placeholder="S1234567A" :invalid="!!form.errors.nric" />
                            <small v-if="form.errors.nric" class="text-red-500">{{ form.errors.nric }}</small>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium">Phone</label>
                            <InputText v-model="form.phone" />
                            <small v-if="form.errors.phone" class="text-red-500">{{ form.errors.phone }}</small>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium">Date of Birth</label>
                            <DatePicker v-model="form.date_of_birth" dateFormat="yy-mm-dd" showIcon />
                        </div>
                        <div class="flex flex-col gap-1 md:col-span-2">
                            <label class="text-sm font-medium">Address</label>
                            <InputText v-model="form.address" />
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-lg font-medium">Assigned Helpers</h2>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium">Helpers *</label>
                        <MultiSelect
                            v-model="form.helper_ids"
                            :options="helpers"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select helpers"
                            display="chip"
                            class="w-full"
                            :invalid="!!form.errors.helper_ids"
                        />
                        <small v-if="form.errors.helper_ids" class="text-red-500">{{ form.errors.helper_ids }}</small>
                    </div>
                </section>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">Create Patient</Button>
                    <Button variant="outline" as="a" href="/patients">Cancel</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
