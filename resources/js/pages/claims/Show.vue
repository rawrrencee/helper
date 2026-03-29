<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PrimeButton from 'primevue/button';
import Image from 'primevue/image';
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
    salary_payments: { id: number; month: number; year: number }[];
};

const props = defineProps<{
    claim: Claim;
    screenshotUrl: string | null;
    paymentScreenshotUrl: string | null;
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).role === 'admin');
const toast = useToast();

const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const breadcrumbs: BreadcrumbItem[] = [
    { title: isAdmin.value ? 'Claims' : 'My Claims', href: '/claims' },
    { title: props.claim.title, href: `/claims/${props.claim.id}` },
];

function statusSeverity(status: string): string {
    switch (status) {
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        default: return 'warn';
    }
}

function updateStatus(status: string) {
    router.put(`/claims/${props.claim.id}`, { status }, {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Updated', detail: `Claim ${status}.`, life: 3000 }),
    });
}

function confirmDelete() {
    if (confirm(`Delete claim "${props.claim.title}"?`)) {
        router.delete(`/claims/${props.claim.id}`);
    }
}

function uploadPaymentScreenshot(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('screenshot', file);

    router.post(`/claims/${props.claim.id}/payment-screenshot`, formData, {
        forceFormData: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Uploaded', detail: 'Payment screenshot uploaded.', life: 3000 }),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="claim.title" />
        <Toast />

        <div class="p-6 space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-2xl font-semibold">{{ claim.title }}</h1>
                    <p class="text-muted-foreground">{{ claim.helper.name }} &mdash; {{ months[claim.month - 1] }} {{ claim.year }}</p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <Tag :severity="statusSeverity(claim.status)" :value="claim.status.charAt(0).toUpperCase() + claim.status.slice(1)" />
                </div>
            </div>

            <section class="rounded-lg border p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm text-muted-foreground">Amount</span>
                        <p class="text-lg font-semibold">${{ Number(claim.amount).toFixed(2) }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-muted-foreground">Period</span>
                        <p class="font-medium">{{ months[claim.month - 1] }} {{ claim.year }}</p>
                    </div>
                </div>

                <div v-if="claim.notes">
                    <span class="text-sm text-muted-foreground">Notes</span>
                    <p class="font-medium">{{ claim.notes }}</p>
                </div>

                <div v-if="claim.salary_payments?.length > 0">
                    <span class="text-sm text-muted-foreground">Included in Salary Payments</span>
                    <div class="mt-1 flex flex-wrap gap-2">
                        <Tag v-for="sp in claim.salary_payments" :key="sp.id" severity="info" :value="`${months[sp.month - 1]} ${sp.year}`" />
                    </div>
                </div>
            </section>

            <section v-if="screenshotUrl" class="rounded-lg border p-6">
                <h2 class="mb-3 text-lg font-medium">Claim Evidence</h2>
                <Image :src="screenshotUrl" preview imageClass="max-h-64 rounded border" />
            </section>

            <section v-if="claim.status === 'approved'" class="rounded-lg border p-6">
                <h2 class="mb-3 text-lg font-medium">Payment Proof</h2>
                <div v-if="paymentScreenshotUrl" class="mb-4">
                    <Image :src="paymentScreenshotUrl" preview imageClass="max-h-64 rounded border" />
                </div>
                <p v-else class="mb-4 text-sm text-muted-foreground">No payment screenshot uploaded yet.</p>
                <div v-if="isAdmin">
                    <input ref="paymentScreenshotInput" type="file" accept="image/*,.heic,.heif" @change="uploadPaymentScreenshot" class="hidden" />
                    <Button variant="outline" size="sm" @click="($refs.paymentScreenshotInput as HTMLInputElement).click()">
                        <i class="pi pi-upload mr-1" /> {{ paymentScreenshotUrl ? 'Replace Screenshot' : 'Upload Screenshot' }}
                    </Button>
                </div>
            </section>

            <div v-if="isAdmin" class="flex gap-2">
                <template v-if="claim.status === 'pending'">
                    <Button @click="updateStatus('approved')">Approve</Button>
                    <Button variant="destructive" @click="updateStatus('rejected')">Reject</Button>
                </template>
                <template v-else-if="claim.status === 'rejected'">
                    <Button @click="updateStatus('approved')">Approve</Button>
                    <PrimeButton label="Reset to Pending" severity="warn" size="small" @click="updateStatus('pending')" />
                </template>
                <template v-else>
                    <PrimeButton label="Reset to Pending" severity="warn" size="small" @click="updateStatus('pending')" />
                </template>
                <PrimeButton label="Delete" severity="danger" size="small" @click="confirmDelete" />
            </div>
        </div>
    </AppLayout>
</template>
