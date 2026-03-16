<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Image from 'primevue/image';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    helpers: { id: number; name: string }[];
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).role === 'admin');
const toast = useToast();

const breadcrumbs: BreadcrumbItem[] = [
    { title: isAdmin.value ? 'Claims' : 'My Claims', href: '/claims' },
    { title: 'New Claim', href: '/claims/create' },
];

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

const form = useForm({
    helper_id: props.helpers.length === 1 ? props.helpers[0].id : null as number | null,
    month: now.getMonth() + 1,
    year: now.getFullYear(),
    title: '',
    amount: null as number | null,
    notes: '',
    screenshot: null as File | null,
});

const screenshotPreview = ref<string | null>(null);

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
    form.post('/claims', {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Submitted', detail: 'Claim submitted.', life: 3000 }),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="New Claim" />
        <Toast />

        <div class="p-6">
            <h1 class="mb-6 text-2xl font-semibold">New Claim</h1>

            <form @submit.prevent="submit" class="space-y-6">
                <div v-if="isAdmin && helpers.length > 0" class="flex flex-col gap-1">
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

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Title *</label>
                    <InputText v-model="form.title" placeholder="e.g., Groceries, Transport, Medical" :invalid="!!form.errors.title" />
                    <small v-if="form.errors.title" class="text-red-500">{{ form.errors.title }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Amount *</label>
                    <InputNumber v-model="form.amount" mode="currency" currency="SGD" :min="0.01" :invalid="!!form.errors.amount" />
                    <small v-if="form.errors.amount" class="text-red-500">{{ form.errors.amount }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Notes</label>
                    <Textarea v-model="form.notes" rows="3" :invalid="!!form.errors.notes" />
                    <small v-if="form.errors.notes" class="text-red-500">{{ form.errors.notes }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Screenshot</label>
                    <div>
                        <input ref="screenshotInput" type="file" accept="image/*,.heic,.heif" @change="handleScreenshot" class="hidden" />
                        <Button type="button" variant="outline" size="sm" @click="($refs.screenshotInput as HTMLInputElement).click()">
                            <i class="pi pi-upload mr-1" /> {{ form.screenshot ? form.screenshot.name : 'Choose File' }}
                        </Button>
                    </div>
                    <small v-if="form.errors.screenshot" class="text-red-500">{{ form.errors.screenshot }}</small>
                    <Image v-if="screenshotPreview" :src="screenshotPreview" preview imageClass="max-h-48 rounded border object-contain" />
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">Submit Claim</Button>
                    <Button variant="outline" as="a" href="/claims">Cancel</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
