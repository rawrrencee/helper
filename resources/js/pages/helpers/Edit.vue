<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import DatePicker from 'primevue/datepicker';
import ToggleSwitch from 'primevue/toggleswitch';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    helper: {
        id: number;
        name: string;
        fin: string;
        passport_no: string | null;
        date_of_birth: string | null;
        nationality: string | null;
        occupation: string | null;
        date_of_application: string | null;
        work_permit_no: string | null;
        sb_transmission_ref_no: string | null;
        employer_name: string | null;
        employment_agency: string | null;
        monthly_salary: string;
        monthly_levy_rate: string;
        rest_days_per_month: number;
        round_up_rest_day_rate: boolean;
        fees_payable_to_ea: string;
        bank_name: string | null;
        bank_account_no: string | null;
        paynow_enabled: boolean;
        paynow_identifier: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Helpers', href: '/helpers' },
    { title: props.helper.name, href: `/helpers/${props.helper.id}` },
    { title: 'Edit', href: `/helpers/${props.helper.id}/edit` },
];

const toast = useToast();

const form = useForm({
    name: props.helper.name,
    fin: props.helper.fin,
    passport_no: props.helper.passport_no ?? '',
    date_of_birth: props.helper.date_of_birth ? new Date(props.helper.date_of_birth) : null,
    nationality: props.helper.nationality ?? '',
    occupation: props.helper.occupation ?? '',
    date_of_application: props.helper.date_of_application ? new Date(props.helper.date_of_application) : null,
    work_permit_no: props.helper.work_permit_no ?? '',
    sb_transmission_ref_no: props.helper.sb_transmission_ref_no ?? '',
    employer_name: props.helper.employer_name ?? '',
    employment_agency: props.helper.employment_agency ?? '',
    monthly_salary: Number(props.helper.monthly_salary),
    monthly_levy_rate: Number(props.helper.monthly_levy_rate),
    rest_days_per_month: props.helper.rest_days_per_month,
    round_up_rest_day_rate: props.helper.round_up_rest_day_rate,
    fees_payable_to_ea: Number(props.helper.fees_payable_to_ea),
    bank_name: props.helper.bank_name ?? '',
    bank_account_no: props.helper.bank_account_no ?? '',
    paynow_enabled: props.helper.paynow_enabled,
    paynow_identifier: props.helper.paynow_identifier ?? '',
});

function formatDate(date: Date): string {
    return date.toISOString().split('T')[0];
}

function submit() {
    form.transform((data) => ({
        ...data,
        date_of_birth: data.date_of_birth ? formatDate(data.date_of_birth) : null,
        date_of_application: data.date_of_application ? formatDate(data.date_of_application) : null,
    })).put(`/helpers/${props.helper.id}`, {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Updated', detail: 'Helper updated.', life: 3000 }),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${helper.name}`" />
        <Toast />

        <div class="mx-auto max-w-5xl p-6">
            <h1 class="mb-6 text-2xl font-semibold">Edit {{ helper.name }}</h1>

            <form @submit.prevent="submit" class="space-y-8">
                <section>
                    <h2 class="mb-4 text-lg font-medium">Personal Information</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <label for="name" class="text-sm font-medium">Name *</label>
                            <InputText id="name" v-model="form.name" :invalid="!!form.errors.name" />
                            <small v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</small>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="fin" class="text-sm font-medium">FIN *</label>
                            <InputText id="fin" v-model="form.fin" placeholder="G1234567X" :invalid="!!form.errors.fin" />
                            <small v-if="form.errors.fin" class="text-red-500">{{ form.errors.fin }}</small>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="passport_no" class="text-sm font-medium">Passport No.</label>
                            <InputText id="passport_no" v-model="form.passport_no" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="date_of_birth" class="text-sm font-medium">Date of Birth</label>
                            <DatePicker id="date_of_birth" v-model="form.date_of_birth" dateFormat="yy-mm-dd" showIcon />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="nationality" class="text-sm font-medium">Nationality</label>
                            <InputText id="nationality" v-model="form.nationality" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="occupation" class="text-sm font-medium">Occupation</label>
                            <InputText id="occupation" v-model="form.occupation" />
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-lg font-medium">Work Permit Details</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <label for="date_of_application" class="text-sm font-medium">Date of Application</label>
                            <DatePicker id="date_of_application" v-model="form.date_of_application" dateFormat="yy-mm-dd" showIcon />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="work_permit_no" class="text-sm font-medium">Work Permit No.</label>
                            <InputText id="work_permit_no" v-model="form.work_permit_no" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="sb_transmission_ref_no" class="text-sm font-medium">SB Transmission Ref No.</label>
                            <InputText id="sb_transmission_ref_no" v-model="form.sb_transmission_ref_no" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="employer_name" class="text-sm font-medium">Employer Name</label>
                            <InputText id="employer_name" v-model="form.employer_name" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="employment_agency" class="text-sm font-medium">Employment Agency</label>
                            <InputText id="employment_agency" v-model="form.employment_agency" />
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-lg font-medium">Salary & Compensation</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <label for="monthly_salary" class="text-sm font-medium">Monthly Salary *</label>
                            <InputNumber id="monthly_salary" v-model="form.monthly_salary" mode="currency" currency="SGD" :invalid="!!form.errors.monthly_salary" />
                            <small v-if="form.errors.monthly_salary" class="text-red-500">{{ form.errors.monthly_salary }}</small>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="monthly_levy_rate" class="text-sm font-medium">Monthly Levy Rate</label>
                            <InputNumber id="monthly_levy_rate" v-model="form.monthly_levy_rate" mode="currency" currency="SGD" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="rest_days_per_month" class="text-sm font-medium">Rest Days Per Month</label>
                            <InputNumber id="rest_days_per_month" v-model="form.rest_days_per_month" :min="0" :max="31" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="fees_payable_to_ea" class="text-sm font-medium">Fees Payable to EA</label>
                            <InputNumber id="fees_payable_to_ea" v-model="form.fees_payable_to_ea" mode="currency" currency="SGD" />
                        </div>
                        <div class="flex items-center gap-3 pt-5">
                            <ToggleSwitch v-model="form.round_up_rest_day_rate" inputId="round_up_rest_day_rate" />
                            <label for="round_up_rest_day_rate" class="text-sm font-medium">Round Up Rest Day Rate</label>
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-lg font-medium">Bank & Payment Details</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <label for="bank_name" class="text-sm font-medium">Bank Name</label>
                            <InputText id="bank_name" v-model="form.bank_name" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="bank_account_no" class="text-sm font-medium">Bank Account No.</label>
                            <InputText id="bank_account_no" v-model="form.bank_account_no" />
                        </div>
                        <div class="flex items-center gap-3 pt-5">
                            <ToggleSwitch v-model="form.paynow_enabled" inputId="paynow_enabled" />
                            <label for="paynow_enabled" class="text-sm font-medium">PayNow Enabled</label>
                        </div>
                        <div v-if="form.paynow_enabled" class="flex flex-col gap-1">
                            <label for="paynow_identifier" class="text-sm font-medium">PayNow Identifier</label>
                            <InputText id="paynow_identifier" v-model="form.paynow_identifier" />
                        </div>
                    </div>
                </section>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">Update Helper</Button>
                    <Button variant="outline" as="a" :href="`/helpers/${helper.id}`">Cancel</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
