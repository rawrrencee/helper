<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import PrimeButton from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem } from '@/types';

type AdminUser = {
    id: number;
    name: string;
    username: string;
    email: string | null;
    created_at: string;
};

const props = defineProps<{
    adminUsers: AdminUser[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Admin Users', href: '/settings/admin-users' },
];

const toast = useToast();
const showAddDialog = ref(false);

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function openAddDialog() {
    form.reset();
    form.clearErrors();
    showAddDialog.value = true;
}

function submitForm() {
    form.post('/settings/admin-users', {
        onSuccess: () => {
            showAddDialog.value = false;
            toast.add({ severity: 'success', summary: 'Created', detail: 'Admin user created.', life: 3000 });
        },
    });
}

function confirmDelete(user: AdminUser) {
    if (confirm(`Delete admin "${user.name}"?`)) {
        form.delete(`/settings/admin-users/${user.id}`, {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Deleted', detail: 'Admin user deleted.', life: 3000 }),
            onError: (errors: Record<string, string>) => {
                if (errors.general) {
                    toast.add({ severity: 'error', summary: 'Error', detail: errors.general, life: 5000 });
                }
            },
        });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Admin Users" />
        <Toast />

        <h1 class="sr-only">Admin Users</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <div class="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Admin Users"
                        description="Manage administrator accounts"
                    />
                    <Button @click="openAddDialog">Add Admin</Button>
                </div>

                <DataTable :value="adminUsers" stripedRows>
                    <Column field="name" header="Name" />
                    <Column field="username" header="Username" />
                    <Column field="email" header="Email">
                        <template #body="{ data }">
                            {{ data.email || '-' }}
                        </template>
                    </Column>
                    <Column header="" style="width: 4rem">
                        <template #body="{ data }">
                            <PrimeButton
                                icon="pi pi-trash"
                                severity="danger"
                                text
                                rounded
                                size="small"
                                @click="confirmDelete(data)"
                            />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </SettingsLayout>

        <Dialog v-model:visible="showAddDialog" header="Add Admin User" modal :style="{ width: '28rem' }">
            <form @submit.prevent="submitForm" class="space-y-4">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Name *</label>
                    <InputText v-model="form.name" :invalid="!!form.errors.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Username *</label>
                    <InputText v-model="form.username" :invalid="!!form.errors.username" />
                    <InputError :message="form.errors.username" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Email</label>
                    <InputText v-model="form.email" type="email" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Password *</label>
                    <InputText v-model="form.password" type="password" :invalid="!!form.errors.password" />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Confirm Password *</label>
                    <InputText v-model="form.password_confirmation" type="password" />
                </div>

                <div class="flex justify-end gap-2">
                    <PrimeButton label="Cancel" severity="secondary" @click="showAddDialog = false" />
                    <PrimeButton type="submit" label="Create" :loading="form.processing" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
