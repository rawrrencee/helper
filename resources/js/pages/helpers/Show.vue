<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Password from 'primevue/password';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import FileUpload from 'primevue/fileupload';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Document = {
    id: number;
    name: string;
    mime_type: string | null;
    file_size: number;
    created_at: string;
};

type HelperDetail = {
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
    documents: Document[];
};

const props = defineProps<{
    helper: HelperDetail;
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).role === 'admin');
const toast = useToast();

const breadcrumbs: BreadcrumbItem[] = [
    ...(isAdmin.value ? [{ title: 'Helpers', href: '/helpers' }] : []),
    { title: props.helper.name, href: `/helpers/${props.helper.id}` },
];

const showResetDialog = ref(false);
const resetPasswordForm = useForm({ password: '' });

function openResetDialog() {
    resetPasswordForm.reset();
    resetPasswordForm.clearErrors();
    showResetDialog.value = true;
}

function submitResetPassword() {
    resetPasswordForm.post(`/helpers/${props.helper.id}/reset-password`, {
        onSuccess: () => {
            showResetDialog.value = false;
            resetPasswordForm.reset();
            toast.add({ severity: 'success', summary: 'Password Reset', detail: 'Password has been reset successfully.', life: 5000 });
        },
    });
}

function field(label: string, value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') return '-';
    return String(value);
}

function formatFileSize(bytes: number): string {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function onUpload(event: any) {
    const file = event.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    router.post(`/helpers/${props.helper.id}/documents`, formData, {
        forceFormData: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Uploaded', detail: 'Document uploaded.', life: 3000 }),
    });
}

function confirmDelete(doc: Document) {
    if (confirm(`Delete "${doc.name}"?`)) {
        router.delete(`/documents/${doc.id}`, {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Deleted', detail: 'Document deleted.', life: 3000 }),
        });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="helper.name" />
        <Toast />

        <div class="p-6">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-semibold">{{ helper.name }}</h1>
                <div v-if="isAdmin" class="flex shrink-0 gap-2">
                    <Link :href="`/helpers/${helper.id}/edit`">
                        <Button label="Edit" icon="pi pi-pencil" severity="secondary" />
                    </Link>
                    <Button label="Reset Password" icon="pi pi-key" severity="warn" @click="openResetDialog" />
                </div>
            </div>

            <Tabs value="profile" :pt="{ tabpanel: { style: 'padding: 0' } }">
                <TabList>
                    <Tab value="profile">Profile</Tab>
                    <Tab value="documents">Documents</Tab>
                </TabList>
                <TabPanels>
                    <TabPanel value="profile">
                        <div class="space-y-8 pt-4">
                            <section class="rounded-lg border p-6">
                                <h2 class="mb-4 text-lg font-medium">Personal Information</h2>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div><span class="text-sm text-muted-foreground">Name</span><p class="font-medium">{{ field('Name', helper.name) }}</p></div>
                                    <div><span class="text-sm text-muted-foreground">FIN</span><p class="font-medium">{{ helper.fin }}</p></div>
                                    <div><span class="text-sm text-muted-foreground">Passport No.</span><p class="font-medium">{{ field('Passport', helper.passport_no) }}</p></div>
                                    <div><span class="text-sm text-muted-foreground">Date of Birth</span><p class="font-medium">{{ field('DOB', helper.date_of_birth) }}</p></div>
                                    <div><span class="text-sm text-muted-foreground">Nationality</span><p class="font-medium">{{ field('Nationality', helper.nationality) }}</p></div>
                                    <div><span class="text-sm text-muted-foreground">Occupation</span><p class="font-medium">{{ field('Occupation', helper.occupation) }}</p></div>
                                </div>
                            </section>

                            <section class="rounded-lg border p-6">
                                <h2 class="mb-4 text-lg font-medium">Work Permit Details</h2>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div><span class="text-sm text-muted-foreground">Date of Application</span><p class="font-medium">{{ field('', helper.date_of_application) }}</p></div>
                                    <div><span class="text-sm text-muted-foreground">Work Permit No.</span><p class="font-medium">{{ field('', helper.work_permit_no) }}</p></div>
                                    <div><span class="text-sm text-muted-foreground">SB Transmission Ref No.</span><p class="font-medium">{{ field('', helper.sb_transmission_ref_no) }}</p></div>
                                    <div><span class="text-sm text-muted-foreground">Employer Name</span><p class="font-medium">{{ field('', helper.employer_name) }}</p></div>
                                    <div><span class="text-sm text-muted-foreground">Employment Agency</span><p class="font-medium">{{ field('', helper.employment_agency) }}</p></div>
                                </div>
                            </section>

                            <section class="rounded-lg border p-6">
                                <h2 class="mb-4 text-lg font-medium">Salary & Compensation</h2>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div><span class="text-sm text-muted-foreground">Monthly Salary</span><p class="font-medium">${{ Number(helper.monthly_salary).toFixed(2) }}</p></div>
                                    <div><span class="text-sm text-muted-foreground">Monthly Levy Rate</span><p class="font-medium">${{ Number(helper.monthly_levy_rate).toFixed(2) }}</p></div>
                                    <div><span class="text-sm text-muted-foreground">Rest Days Per Month</span><p class="font-medium">{{ helper.rest_days_per_month }}</p></div>
                                    <div><span class="text-sm text-muted-foreground">Round Up Rest Day Rate</span><p class="font-medium">{{ helper.round_up_rest_day_rate ? 'Yes' : 'No' }}</p></div>
                                    <div><span class="text-sm text-muted-foreground">Fees Payable to EA</span><p class="font-medium">${{ Number(helper.fees_payable_to_ea).toFixed(2) }}</p></div>
                                </div>
                            </section>

                            <section class="rounded-lg border p-6">
                                <h2 class="mb-4 text-lg font-medium">Bank & Payment Details</h2>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div><span class="text-sm text-muted-foreground">Bank Name</span><p class="font-medium">{{ field('', helper.bank_name) }}</p></div>
                                    <div><span class="text-sm text-muted-foreground">Bank Account No.</span><p class="font-medium">{{ field('', helper.bank_account_no) }}</p></div>
                                    <div><span class="text-sm text-muted-foreground">PayNow Enabled</span><p class="font-medium">{{ helper.paynow_enabled ? 'Yes' : 'No' }}</p></div>
                                    <div v-if="helper.paynow_enabled"><span class="text-sm text-muted-foreground">PayNow Identifier</span><p class="font-medium">{{ field('', helper.paynow_identifier) }}</p></div>
                                </div>
                            </section>
                        </div>
                    </TabPanel>

                    <TabPanel value="documents">
                        <div class="space-y-6 pt-4">
                            <div v-if="isAdmin" class="rounded-lg border p-4">
                                <h2 class="mb-3 text-sm font-medium">Upload Document</h2>
                                <FileUpload
                                    mode="basic"
                                    :auto="true"
                                    :maxFileSize="10485760"
                                    chooseLabel="Choose File"
                                    @select="onUpload"
                                />
                            </div>

                            <DataTable
                                :value="helper.documents"
                                dataKey="id"
                                stripedRows
                                class="text-sm"
                            >
                                <Column field="name" header="Name" />
                                <Column field="file_size" header="Size">
                                    <template #body="{ data }">
                                        {{ formatFileSize(data.file_size) }}
                                    </template>
                                </Column>
                                <Column field="created_at" header="Uploaded">
                                    <template #body="{ data }">
                                        {{ new Date(data.created_at).toLocaleDateString() }}
                                    </template>
                                </Column>
                                <Column header="Actions" style="width: 10rem">
                                    <template #body="{ data }">
                                        <div class="flex gap-2">
                                            <a :href="`/documents/${data.id}/download`">
                                                <Button icon="pi pi-download" severity="secondary" text rounded size="small" />
                                            </a>
                                            <Button v-if="isAdmin" icon="pi pi-trash" severity="danger" text rounded size="small" @click="confirmDelete(data)" />
                                        </div>
                                    </template>
                                </Column>

                                <template #empty>
                                    <div class="py-8 text-center text-muted-foreground">
                                        No documents uploaded yet.
                                    </div>
                                </template>
                            </DataTable>
                        </div>
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
        <Dialog v-model:visible="showResetDialog" header="Reset Password" modal :style="{ width: '25rem' }">
            <form @submit.prevent="submitResetPassword" class="space-y-4">
                <div>
                    <label for="reset-password" class="mb-1 block text-sm font-medium">New Password</label>
                    <Password
                        id="reset-password"
                        v-model="resetPasswordForm.password"
                        :feedback="false"
                        toggleMask
                        fluid
                        :invalid="!!resetPasswordForm.errors.password"
                    />
                    <small v-if="resetPasswordForm.errors.password" class="text-red-500">{{ resetPasswordForm.errors.password }}</small>
                </div>
                <div class="flex justify-end gap-2">
                    <Button label="Cancel" severity="secondary" @click="showResetDialog = false" />
                    <Button type="submit" label="Reset Password" :loading="resetPasswordForm.processing" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
