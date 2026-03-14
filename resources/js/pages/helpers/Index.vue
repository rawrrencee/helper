<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import PrimeButton from 'primevue/button';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Helper = {
    id: number;
    name: string;
    fin: string;
    nationality: string | null;
    monthly_salary: string;
    created_at: string;
};

type PaginatedHelpers = {
    data: Helper[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

const props = defineProps<{
    helpers: PaginatedHelpers;
    filters: { search?: string };
}>();

const toast = useToast();
const search = ref(props.filters.search ?? '');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Helpers', href: '/helpers' },
];

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/helpers', { search: value || undefined }, { preserveState: true, replace: true });
    }, 300);
});

function onPage(event: { page: number }) {
    router.get('/helpers', {
        page: event.page + 1,
        search: search.value || undefined,
    }, { preserveState: true });
}

function confirmDelete(helper: Helper) {
    if (confirm(`Are you sure you want to delete ${helper.name}?`)) {
        router.delete(`/helpers/${helper.id}`, {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Deleted', detail: 'Helper deleted.', life: 3000 }),
        });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Helpers" />
        <Toast />

        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Helpers</h1>
                <Link href="/helpers/create">
                    <Button><i class="pi pi-plus mr-1" /> Add Helper</Button>
                </Link>
            </div>

            <div class="flex items-center gap-4">
                <InputText
                    v-model="search"
                    placeholder="Search by name or FIN..."
                    class="w-full max-w-sm"
                />
            </div>

            <DataTable
                :value="helpers.data"
                :lazy="true"
                :paginator="true"
                :rows="helpers.per_page"
                :totalRecords="helpers.total"
                :first="(helpers.current_page - 1) * helpers.per_page"
                @page="onPage"
                dataKey="id"
                stripedRows
                class="text-sm"
            >
                <Column field="name" header="Name">
                    <template #body="{ data }">
                        <Link :href="`/helpers/${data.id}`" class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                            {{ data.name }}
                        </Link>
                    </template>
                </Column>
                <Column field="fin" header="FIN" />
                <Column field="nationality" header="Nationality" />
                <Column field="monthly_salary" header="Salary">
                    <template #body="{ data }">
                        ${{ Number(data.monthly_salary).toFixed(2) }}
                    </template>
                </Column>
                <Column header="Actions" style="width: 10rem">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            <Link :href="`/helpers/${data.id}/edit`">
                                <PrimeButton icon="pi pi-pencil" severity="secondary" text rounded size="small" />
                            </Link>
                            <PrimeButton icon="pi pi-trash" severity="danger" text rounded size="small" @click="confirmDelete(data)" />
                        </div>
                    </template>
                </Column>

                <template #empty>
                    <div class="py-8 text-center text-muted-foreground">
                        No helpers found.
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
