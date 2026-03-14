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

type Payment = {
    id: number;
    helper_id: number;
    month: number;
    year: number;
    base_salary: string;
    working_days_start: string | null;
    working_days_end: string | null;
    total_calendar_days: number;
    sundays_in_period: number;
    pro_rated_amount: string;
    extra_rest_days_worked: number;
    rest_day_rate: string;
    extra_rest_day_pay: string;
    sundays_worked_dates: string[] | null;
    ad_hoc_payments: { description: string; amount: number }[] | null;
    total_amount: string;
    payment_method: string;
    payment_screenshot_path: string | null;
    paid_at: string | null;
    notes: string | null;
    helper: { id: number; name: string; fin: string };
};

const props = defineProps<{
    payment: Payment;
    screenshotUrl: string | null;
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).role === 'admin');
const toast = useToast();

const isFullMonth = computed(() => Number(props.payment.pro_rated_amount) === Number(props.payment.base_salary));

const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Salary Payments', href: '/salary-payments' },
    { title: `${months[props.payment.month - 1]} ${props.payment.year}`, href: `/salary-payments/${props.payment.id}` },
];

function uploadScreenshot(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('screenshot', file);

    router.post(`/salary-payments/${props.payment.id}/screenshot`, formData, {
        forceFormData: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Uploaded', detail: 'Screenshot uploaded.', life: 3000 }),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Salary - ${months[payment.month - 1]} ${payment.year}`" />
        <Toast />

        <div class="mx-auto max-w-5xl p-6">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-semibold">
                    Salary Slip - {{ payment.helper.name }}
                </h1>
                <div class="flex shrink-0 gap-2">
                    <a :href="`/salary-payments/${payment.id}/pdf`">
                        <PrimeButton label="Download PDF" icon="pi pi-file-pdf" severity="secondary" />
                    </a>
                    <a v-if="isAdmin" :href="`/salary-payments/${payment.id}/edit`">
                        <PrimeButton label="Edit" icon="pi pi-pencil" severity="secondary" />
                    </a>
                </div>
            </div>

            <div class="space-y-6">
                <section class="rounded-lg border p-6">
                    <div class="mb-2 flex items-center justify-between">
                        <h2 class="text-lg font-medium">Period</h2>
                        <Tag v-if="payment.paid_at" severity="success" value="Paid" />
                        <Tag v-else severity="warn" value="Unpaid" />
                    </div>
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                        <div><span class="text-sm text-muted-foreground">Month/Year</span><p class="font-medium">{{ months[payment.month - 1] }} {{ payment.year }}</p></div>
                        <div v-if="payment.working_days_start && payment.working_days_end">
                            <span class="text-sm text-muted-foreground">Working Period</span>
                            <p class="font-medium">
                                {{ new Date(payment.working_days_start).toLocaleDateString('en-GB') }}
                                –
                                {{ new Date(payment.working_days_end).toLocaleDateString('en-GB') }}
                            </p>
                        </div>
                        <div><span class="text-sm text-muted-foreground">Calendar Days</span><p class="font-medium">{{ payment.total_calendar_days }}</p></div>
                        <div><span class="text-sm text-muted-foreground">Sundays</span><p class="font-medium">{{ payment.sundays_in_period }}</p></div>
                    </div>
                </section>

                <section class="rounded-lg border p-6">
                    <h2 class="mb-4 text-lg font-medium">Calculation Breakdown</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between"><span class="text-muted-foreground">Base Salary</span><span class="font-medium">${{ Number(payment.base_salary).toFixed(2) }}</span></div>
                        <template v-if="!isFullMonth">
                            <div class="flex justify-between"><span class="text-muted-foreground">Daily Rate (Salary / 26)</span><span class="font-medium">${{ Number(payment.rest_day_rate).toFixed(2) }}</span></div>
                            <div class="flex justify-between"><span class="text-muted-foreground">Pro-Rated Amount</span><span class="font-medium">${{ Number(payment.pro_rated_amount).toFixed(2) }}</span></div>
                        </template>
                        <template v-if="payment.extra_rest_days_worked > 0">
                            <div class="flex justify-between"><span class="text-muted-foreground">Sundays Worked</span><span class="font-medium">{{ payment.extra_rest_days_worked }}</span></div>
                            <div v-if="payment.sundays_worked_dates?.length" class="flex justify-between">
                                <span class="text-muted-foreground">Dates Worked</span>
                                <span class="font-medium">{{ payment.sundays_worked_dates.join(', ') }}</span>
                            </div>
                            <div class="flex justify-between"><span class="text-muted-foreground">Sundays Worked Pay</span><span class="font-medium">${{ Number(payment.extra_rest_day_pay).toFixed(2) }}</span></div>
                        </template>
                        <template v-if="payment.ad_hoc_payments?.length">
                            <div class="flex justify-between" v-for="(item, index) in payment.ad_hoc_payments" :key="index">
                                <span class="text-muted-foreground">{{ item.description }}</span>
                                <span class="font-medium">${{ Number(item.amount).toFixed(2) }}</span>
                            </div>
                        </template>
                        <div class="flex justify-between border-t pt-3 text-lg font-semibold">
                            <span>Total</span>
                            <span>${{ Number(payment.total_amount).toFixed(2) }}</span>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border p-6">
                    <h2 class="mb-4 text-lg font-medium">Payment Details</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div><span class="text-sm text-muted-foreground">Method</span><p class="font-medium">{{ payment.payment_method === 'bank_transfer' ? 'Bank Transfer' : 'PayNow' }}</p></div>
                        <div><span class="text-sm text-muted-foreground">Paid At</span><p class="font-medium">{{ payment.paid_at ? new Date(payment.paid_at).toLocaleDateString() : 'Not yet paid' }}</p></div>
                        <div v-if="payment.notes" class="col-span-2"><span class="text-sm text-muted-foreground">Notes</span><p class="font-medium">{{ payment.notes }}</p></div>
                    </div>

                    <div v-if="screenshotUrl" class="mt-4">
                        <label class="text-sm font-medium">Payment Screenshot</label>
                        <div class="mt-2">
                            <Image :src="screenshotUrl" preview imageClass="max-h-64 rounded border" />
                        </div>
                    </div>

                    <div v-if="isAdmin" class="mt-4">
                        <input ref="screenshotInput" type="file" accept="image/*,.heic,.heif" @change="uploadScreenshot" class="hidden" />
                        <Button variant="outline" size="sm" @click="($refs.screenshotInput as HTMLInputElement).click()">
                            <i class="pi pi-upload mr-1" /> {{ screenshotUrl ? 'Replace Screenshot' : 'Upload Screenshot' }}
                        </Button>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
