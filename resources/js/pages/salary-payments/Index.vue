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

type Payment = {
    id: number;
    helper_id: number;
    month: number;
    year: number;
    base_salary: string;
    total_amount: string;
    payment_method: string;
    paid_at: string | null;
    helper: { id: number; name: string };
};

const props = defineProps<{
    payments: {
        data: Payment[];
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
    { title: 'Salary Payments', href: '/salary-payments' },
];

const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

function filterByHelper(helperId: number | null) {
    router.get('/salary-payments', { helper_id: helperId || undefined }, { preserveState: true, replace: true });
}

function onPage(event: { page: number }) {
    router.get('/salary-payments', {
        page: event.page + 1,
        helper_id: props.filters.helper_id || undefined,
    }, { preserveState: true });
}

function confirmDelete(payment: Payment) {
    if (confirm('Delete this salary record?')) {
        router.delete(`/salary-payments/${payment.id}`, {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Deleted', detail: 'Salary payment deleted.', life: 3000 }),
        });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Salary Payments" />
        <Toast />

        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Salary Payments</h1>
                <Link v-if="isAdmin" href="/salary-payments/create">
                    <Button><i class="pi pi-plus mr-1" /> New Payment</Button>
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
                :value="payments.data"
                :lazy="true"
                :paginator="true"
                :rows="payments.per_page"
                :totalRecords="payments.total"
                :first="(payments.current_page - 1) * payments.per_page"
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
                <Column header="Total">
                    <template #body="{ data }">
                        ${{ Number(data.total_amount).toFixed(2) }}
                    </template>
                </Column>
                <Column header="Status">
                    <template #body="{ data }">
                        <Tag v-if="data.paid_at" severity="success" value="Paid" />
                        <Tag v-else severity="warn" value="Unpaid" />
                    </template>
                </Column>
                <Column header="Actions" style="width: 12rem">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            <Link :href="`/salary-payments/${data.id}`">
                                <PrimeButton icon="pi pi-eye" severity="secondary" text rounded size="small" />
                            </Link>
                            <a :href="`/salary-payments/${data.id}/pdf`">
                                <PrimeButton icon="pi pi-file-pdf" severity="secondary" text rounded size="small" />
                            </a>
                            <template v-if="isAdmin">
                                <Link :href="`/salary-payments/${data.id}/edit`">
                                    <PrimeButton icon="pi pi-pencil" severity="secondary" text rounded size="small" />
                                </Link>
                                <PrimeButton icon="pi pi-trash" severity="danger" text rounded size="small" @click="confirmDelete(data)" />
                            </template>
                        </div>
                    </template>
                </Column>

                <template #empty>
                    <div class="py-8 text-center text-muted-foreground">
                        No salary payments found.
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
