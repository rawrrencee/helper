<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import DatePicker from 'primevue/datepicker';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Message from 'primevue/message';
import Toast from 'primevue/toast';
import Image from 'primevue/image';
import { useToast } from 'primevue/usetoast';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type HelperOption = {
    id: number;
    name: string;
    monthly_salary: string;
    round_up_rest_day_rate: boolean;
};

const props = defineProps<{
    helpers: HelperOption[];
    existingPayments: Record<number, { month: number; year: number }[]>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Salary Payments', href: '/salary-payments' },
    { title: 'New Payment', href: '/salary-payments/create' },
];

const toast = useToast();
const now = new Date();

const monthOptions = [
    { label: 'January', value: 1 },
    { label: 'February', value: 2 },
    { label: 'March', value: 3 },
    { label: 'April', value: 4 },
    { label: 'May', value: 5 },
    { label: 'June', value: 6 },
    { label: 'July', value: 7 },
    { label: 'August', value: 8 },
    { label: 'September', value: 9 },
    { label: 'October', value: 10 },
    { label: 'November', value: 11 },
    { label: 'December', value: 12 },
];

const currentYear = now.getFullYear();
const yearOptions = Array.from({ length: 4 }, (_, i) => {
    const y = currentYear - 2 + i;
    return { label: String(y), value: y };
});

function getDefaultMonthYear(helperId: number | null): { month: number; year: number } {
    let month = now.getMonth(); // previous month (0-indexed current month = previous month 1-indexed)
    let year = now.getFullYear();
    if (month === 0) {
        month = 12;
        year--;
    }

    if (!helperId) {
        return { month, year };
    }

    const payments = props.existingPayments[helperId] ?? [];
    const exists = (m: number, y: number) => payments.some(p => p.month === m && p.year === y);

    while (exists(month, year)) {
        month++;
        if (month > 12) {
            month = 1;
            year++;
        }
    }

    return { month, year };
}

const defaultMonthYear = getDefaultMonthYear(props.helpers.length === 1 ? props.helpers[0].id : null);

const form = useForm({
    helper_id: props.helpers.length === 1 ? props.helpers[0].id : null as number | null,
    month: defaultMonthYear.month,
    year: defaultMonthYear.year,
    base_salary: 0,
    working_days_start: null as Date | null,
    working_days_end: null as Date | null,
    total_calendar_days: 0,
    sundays_in_period: 0,
    pro_rated_amount: 0,
    extra_rest_days_worked: 0,
    sundays_worked_dates: [] as (Date | null)[],
    rest_day_rate: 0,
    extra_rest_day_pay: 0,
    ad_hoc_payments: [] as { description: string; amount: number }[],
    total_amount: 0,
    payment_method: 'bank_transfer',
    paid_at: null as Date | null,
    notes: '',
    screenshot: null as File | null,
});

const screenshotPreview = ref<string | null>(null);

const paymentMethods = [
    { label: 'Bank Transfer', value: 'bank_transfer' },
    { label: 'PayNow', value: 'paynow' },
];

const selectedHelper = computed(() => props.helpers.find(h => h.id === form.helper_id));

const isDuplicate = computed(() => {
    if (!form.helper_id) return false;
    const payments = props.existingPayments[form.helper_id] ?? [];
    return payments.some(p => p.month === form.month && p.year === form.year);
});

watch(() => form.helper_id, (helperId) => {
    if (selectedHelper.value) {
        form.base_salary = Number(selectedHelper.value.monthly_salary);
        recalculate();
    }
    if (helperId) {
        const { month, year } = getDefaultMonthYear(helperId);
        form.month = month;
        form.year = year;
    }
});

function countSundays(start: Date, end: Date): number {
    let count = 0;
    const current = new Date(start);
    while (current <= end) {
        if (current.getDay() === 0) count++;
        current.setDate(current.getDate() + 1);
    }
    return count;
}

watch([() => form.working_days_start, () => form.working_days_end], () => {
    if (form.working_days_start && form.working_days_end) {
        const start = new Date(form.working_days_start);
        const end = new Date(form.working_days_end);
        const diffTime = Math.abs(end.getTime() - start.getTime());
        form.total_calendar_days = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        form.sundays_in_period = countSundays(start, end);
        recalculate();
    }
});

watch(() => form.extra_rest_days_worked, (count) => {
    const dates = [...form.sundays_worked_dates];
    if (count > dates.length) {
        while (dates.length < count) dates.push(null);
    } else {
        dates.length = count;
    }
    form.sundays_worked_dates = dates;
});

const adHocTotal = computed(() => form.ad_hoc_payments.reduce((sum, item) => sum + (Number(item.amount) || 0), 0));

watch([() => form.base_salary, () => form.extra_rest_days_worked, () => form.total_calendar_days, () => form.sundays_in_period], () => {
    recalculate();
});

watch(() => form.ad_hoc_payments, () => {
    recalculate();
}, { deep: true });

function isFullMonth(): boolean {
    if (!form.working_days_start || !form.working_days_end) return false;
    const start = new Date(form.working_days_start);
    const end = new Date(form.working_days_end);
    const isFirstDay = start.getDate() === 1;
    const lastDayOfMonth = new Date(end.getFullYear(), end.getMonth() + 1, 0).getDate();
    const isLastDay = end.getDate() === lastDayOfMonth;
    return isFirstDay && isLastDay && start.getMonth() === end.getMonth() && start.getFullYear() === end.getFullYear();
}

function recalculate() {
    const roundUp = selectedHelper.value?.round_up_rest_day_rate ?? false;
    const rawRate = form.base_salary / 26;
    form.rest_day_rate = roundUp ? Math.ceil(rawRate) : Math.round(rawRate * 100) / 100;

    if (isFullMonth()) {
        form.pro_rated_amount = form.base_salary;
    } else {
        const workingDays = form.total_calendar_days - form.sundays_in_period;
        form.pro_rated_amount = Math.round(form.rest_day_rate * Math.max(workingDays, 0) * 100) / 100;
    }
    form.extra_rest_day_pay = Math.round(form.rest_day_rate * form.extra_rest_days_worked * 100) / 100;
    form.total_amount = Math.round((form.pro_rated_amount + form.extra_rest_day_pay + adHocTotal.value) * 100) / 100;
}

function formatDate(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function isSunday(date: Date): boolean {
    return date.getDay() === 0;
}

function addAdHocItem() {
    form.ad_hoc_payments.push({ description: '', amount: 0 });
}

function removeAdHocItem(index: number) {
    form.ad_hoc_payments.splice(index, 1);
}

function handleScreenshot(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.screenshot = file;
    if (file) {
        screenshotPreview.value = URL.createObjectURL(file);
    } else {
        screenshotPreview.value = null;
    }
}

function submit() {
    form.transform((data) => ({
        ...data,
        working_days_start: data.working_days_start ? formatDate(data.working_days_start) : null,
        working_days_end: data.working_days_end ? formatDate(data.working_days_end) : null,
        paid_at: data.paid_at ? formatDate(data.paid_at) : null,
        sundays_worked_dates: data.sundays_worked_dates.filter((d): d is Date => d !== null).map(formatDate),
        ad_hoc_payments: data.ad_hoc_payments.length > 0 ? data.ad_hoc_payments : null,
    })).post('/salary-payments', {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Created', detail: 'Salary payment created.', life: 3000 }),
    });
}

// Trigger initial recalculation if helper is pre-selected
if (form.helper_id && selectedHelper.value) {
    form.base_salary = Number(selectedHelper.value.monthly_salary);
    recalculate();
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="New Salary Payment" />
        <Toast />

        <div class="mx-auto max-w-5xl p-6">
            <h1 class="mb-6 text-2xl font-semibold">New Salary Payment</h1>

            <form @submit.prevent="submit" class="space-y-8">
                <section>
                    <h2 class="mb-4 text-lg font-medium">Helper & Period</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium">Helper *</label>
                            <Select
                                v-model="form.helper_id"
                                :options="helpers"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select helper"
                                :invalid="!!form.errors.helper_id"
                            />
                            <small v-if="form.errors.helper_id" class="text-red-500">{{ form.errors.helper_id }}</small>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium">Month *</label>
                            <Select
                                v-model="form.month"
                                :options="monthOptions"
                                optionLabel="label"
                                optionValue="value"
                                :invalid="!!form.errors.month"
                            />
                            <small v-if="form.errors.month" class="text-red-500">{{ form.errors.month }}</small>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium">Year *</label>
                            <Select
                                v-model="form.year"
                                :options="yearOptions"
                                optionLabel="label"
                                optionValue="value"
                                :invalid="!!form.errors.year"
                            />
                            <small v-if="form.errors.year" class="text-red-500">{{ form.errors.year }}</small>
                        </div>
                    </div>
                    <Message v-if="isDuplicate" severity="warn" class="mt-3">
                        A salary payment already exists for this helper in the selected month and year.
                    </Message>
                </section>

                <section>
                    <h2 class="mb-4 text-lg font-medium">Working Period</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium">Start Date</label>
                            <DatePicker v-model="form.working_days_start" dateFormat="yy-mm-dd" showIcon />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium">End Date</label>
                            <DatePicker v-model="form.working_days_end" dateFormat="yy-mm-dd" showIcon />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium">Total Calendar Days</label>
                            <InputNumber v-model="form.total_calendar_days" :min="0" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium">Sundays in Period</label>
                            <InputNumber v-model="form.sundays_in_period" :min="0" />
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-lg font-medium">Salary Inputs</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium">Base Salary *</label>
                            <InputNumber v-model="form.base_salary" mode="currency" currency="SGD" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium">Sundays Worked</label>
                            <InputNumber v-model="form.extra_rest_days_worked" :min="0" />
                        </div>
                    </div>

                    <div v-if="form.extra_rest_days_worked > 0" class="mt-4">
                        <label class="mb-2 block text-sm font-medium">Sunday Dates Worked</label>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div v-for="(_, index) in form.sundays_worked_dates" :key="index" class="flex flex-col gap-1">
                                <label class="text-xs text-muted-foreground">Sunday {{ index + 1 }}</label>
                                <DatePicker
                                    v-model="form.sundays_worked_dates[index]"
                                    dateFormat="yy-mm-dd"
                                    showIcon
                                    :minDate="form.working_days_start ?? undefined"
                                    :maxDate="form.working_days_end ?? undefined"
                                    :disabledDays="[1, 2, 3, 4, 5, 6]"
                                />
                            </div>
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-lg font-medium">Calculated Fields</h2>
                    <div class="rounded-lg bg-muted/30 p-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-muted-foreground">Daily Rate</label>
                                <InputNumber v-model="form.rest_day_rate" mode="currency" currency="SGD" disabled />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-muted-foreground">Pro-Rated Amount</label>
                                <InputNumber v-model="form.pro_rated_amount" mode="currency" currency="SGD" disabled />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-muted-foreground">Sundays Worked Pay</label>
                                <InputNumber v-model="form.extra_rest_day_pay" mode="currency" currency="SGD" disabled />
                            </div>
                        </div>
                    </div>

                </section>

                <section>
                    <h2 class="mb-4 text-lg font-medium">Ad-Hoc Payments</h2>
                    <p class="mb-3 text-sm text-muted-foreground">Reimbursements for groceries, transport, medical, etc.</p>

                    <div v-if="form.ad_hoc_payments.length > 0" class="mb-4 space-y-3">
                        <div v-for="(item, index) in form.ad_hoc_payments" :key="index" class="flex items-start gap-3">
                            <div class="flex-1">
                                <InputText
                                    v-model="item.description"
                                    placeholder="Description"
                                    class="w-full"
                                    :invalid="!!form.errors[`ad_hoc_payments.${index}.description` as keyof typeof form.errors]"
                                />
                            </div>
                            <div class="w-40">
                                <InputNumber
                                    v-model="item.amount"
                                    mode="currency"
                                    currency="SGD"
                                    :min="0"
                                    :invalid="!!form.errors[`ad_hoc_payments.${index}.amount` as keyof typeof form.errors]"
                                />
                            </div>
                            <Button variant="ghost" size="icon" class="shrink-0" @click="removeAdHocItem(index)">
                                <i class="pi pi-trash text-red-500" />
                            </Button>
                        </div>
                    </div>

                    <Button type="button" variant="outline" size="sm" @click="addAdHocItem">
                        <i class="pi pi-plus mr-1" /> Add Item
                    </Button>

                    <div v-if="form.ad_hoc_payments.length > 0" class="mt-3 text-sm text-muted-foreground">
                        Ad-hoc subtotal: ${{ adHocTotal.toFixed(2) }}
                    </div>
                </section>

                <div class="rounded-lg border bg-muted/50 p-4">
                    <div class="text-lg font-semibold">
                        Total: ${{ form.total_amount.toFixed(2) }}
                    </div>
                </div>

                <section>
                    <h2 class="mb-4 text-lg font-medium">Payment Details</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium">Payment Method</label>
                            <Select v-model="form.payment_method" :options="paymentMethods" optionLabel="label" optionValue="value" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium">Paid At</label>
                            <DatePicker v-model="form.paid_at" dateFormat="yy-mm-dd" showIcon />
                        </div>
                        <div class="flex flex-col gap-1 md:col-span-2">
                            <label class="text-sm font-medium">Payment Screenshot</label>
                            <div>
                                <input ref="screenshotInput" type="file" accept="image/*,.heic,.heif" @change="handleScreenshot" class="hidden" />
                                <Button type="button" variant="outline" size="sm" @click="($refs.screenshotInput as HTMLInputElement).click()">
                                    <i class="pi pi-upload mr-1" /> {{ form.screenshot ? form.screenshot.name : 'Choose File' }}
                                </Button>
                            </div>
                            <small v-if="form.errors.screenshot" class="text-red-500">{{ form.errors.screenshot }}</small>
                            <Image v-if="screenshotPreview" :src="screenshotPreview" preview imageClass="max-h-48 rounded border object-contain" />
                        </div>
                        <div class="flex flex-col gap-1 md:col-span-2">
                            <label class="text-sm font-medium">Notes</label>
                            <Textarea v-model="form.notes" rows="3" />
                        </div>
                    </div>
                </section>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">Create Payment</Button>
                    <Button variant="outline" as="a" href="/salary-payments">Cancel</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
