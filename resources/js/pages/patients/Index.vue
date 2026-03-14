<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Patient = {
    id: number;
    name: string;
    masked_nric: string;
    age: number | null;
    phone: string | null;
    helpers: { id: number; name: string }[];
};

const props = defineProps<{
    patients: Patient[];
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).role === 'admin');

const expandedRows = ref({});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Patients', href: '/patients' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Patients" />

        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Patients</h1>
                <Link v-if="isAdmin" href="/patients/create">
                    <Button><i class="pi pi-plus mr-1" /> Add Patient</Button>
                </Link>
            </div>

            <DataTable
                v-model:expandedRows="expandedRows"
                :value="patients"
                dataKey="id"
                stripedRows
                class="text-sm"
            >
                <Column expander headerClass="md:hidden" class="md:hidden" style="width: 3rem" />
                <Column field="name" header="Name">
                    <template #body="{ data }">
                        <Link :href="`/patients/${data.id}`" class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                            {{ data.name }}
                        </Link>
                    </template>
                </Column>
                <Column field="masked_nric" header="NRIC" headerClass="hidden md:table-cell" class="hidden md:table-cell" />
                <Column field="age" header="Age">
                    <template #body="{ data }">
                        {{ data.age ?? '-' }}
                    </template>
                </Column>
                <Column field="phone" header="Phone" headerClass="hidden md:table-cell" class="hidden md:table-cell">
                    <template #body="{ data }">
                        {{ data.phone ?? '-' }}
                    </template>
                </Column>
                <Column header="Helpers">
                    <template #body="{ data }">
                        <div class="flex flex-wrap gap-1">
                            <Tag v-for="helper in data.helpers" :key="helper.id" :value="helper.name" severity="info" />
                        </div>
                    </template>
                </Column>

                <template #expansion="{ data }">
                    <div class="md:hidden p-3 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">NRIC</span>
                            <span>{{ data.masked_nric }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Phone</span>
                            <span>{{ data.phone ?? '-' }}</span>
                        </div>
                    </div>
                </template>

                <template #empty>
                    <div class="py-8 text-center text-muted-foreground">
                        No patients found.
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
