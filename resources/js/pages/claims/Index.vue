<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Select from 'primevue/select';
import PrimeButton from 'primevue/button';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Claim = {
    id: number;
    helper_id: number;
    month: number;
    year: number;
    title: string;
    amount: string;
    status: string;
    notes: string | null;
    screenshot_path: string | null;
    helper: { id: number; name: string };
};

const props = defineProps<{
    claims: {
        data: Claim[];
        current_page: number;
        per_page: number;
        total: number;
    };
    helpers: { id: number; name: string }[];
    filters: { helper_id?: number };
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).role === 'admin');
const toast = useToast();

const breadcrumbs: BreadcrumbItem[] = [
    { title: isAdmin.value ? 'Claims' : 'My Claims', href: '/claims' },
];

const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

function statusSeverity(status: string): string {
    switch (status) {
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        default: return 'warn';
    }
}

function filterByHelper(helperId: number | null) {
    router.get('/claims', { helper_id: helperId || undefined }, { preserveState: true, replace: true });
}

function onPage(event: { page: number }) {
    router.get('/claims', {
        page: event.page + 1,
        helper_id: props.filters.helper_id || undefined,
    }, { preserveState: true });
}

function updateStatus(claim: Claim, status: string) {
    router.put(`/claims/${claim.id}`, { status }, {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Updated', detail: `Claim ${status}.`, life: 3000 }),
    });
}

function confirmDelete(claim: Claim) {
    if (confirm(`Delete claim "${claim.title}"?`)) {
        router.delete(`/claims/${claim.id}`, {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Deleted', detail: 'Claim deleted.', life: 3000 }),
        });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="isAdmin ? 'Claims' : 'My Claims'" />
        <Toast />

        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">{{ isAdmin ? 'Claims' : 'My Claims' }}</h1>
                <Link href="/claims/create">
                    <Button><i class="pi pi-plus mr-1" /> New Claim</Button>
                </Link>
            </div>

            <div v-if="isAdmin && helpers.length > 0" class="flex items-center gap-4">
                <Select
                    :options="[{ id: null, name: 'All Helpers' }, ...helpers]"
                    optionLabel="name"
                    optionValue="id"
                    :modelValue="filters.helper_id ?? null"
                    @update:modelValue="filterByHelper"
                    placeholder="Filter by helper"
                    class="w-64"
                />
            </div>

            <DataTable
                :value="claims.data"
                :lazy="true"
                :paginator="true"
                :rows="claims.per_page"
                :totalRecords="claims.total"
                :first="(claims.current_page - 1) * claims.per_page"
                @page="onPage"
                dataKey="id"
                stripedRows
                class="text-sm"
            >
                <Column v-if="isAdmin" header="Helper">
                    <template #body="{ data }">
                        <Link :href="`/helpers/${data.helper.id}`" class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                            {{ data.helper.name }}
                        </Link>
                    </template>
                </Column>
                <Column header="Period">
                    <template #body="{ data }">
                        {{ months[data.month - 1] }} {{ data.year }}
                    </template>
                </Column>
                <Column field="title" header="Title" />
                <Column header="Amount">
                    <template #body="{ data }">
                        ${{ Number(data.amount).toFixed(2) }}
                    </template>
                </Column>
                <Column header="Status">
                    <template #body="{ data }">
                        <Tag :severity="statusSeverity(data.status)" :value="data.status.charAt(0).toUpperCase() + data.status.slice(1)" />
                    </template>
                </Column>
                <Column header="Actions" style="width: 14rem">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Link :href="`/claims/${data.id}`">
                                <PrimeButton icon="pi pi-eye" severity="secondary" text rounded size="small" />
                            </Link>
                            <template v-if="isAdmin && data.status === 'pending'">
                                <PrimeButton icon="pi pi-check" severity="success" text rounded size="small" @click="updateStatus(data, 'approved')" />
                                <PrimeButton icon="pi pi-times" severity="danger" text rounded size="small" @click="updateStatus(data, 'rejected')" />
                            </template>
                            <PrimeButton v-if="isAdmin" icon="pi pi-trash" severity="danger" text rounded size="small" @click="confirmDelete(data)" />
                        </div>
                    </template>
                </Column>

                <template #empty>
                    <div class="py-8 text-center text-muted-foreground">
                        No claims found.
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
