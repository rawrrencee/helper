<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Editor from 'primevue/editor';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    family_information: string | null;
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).role === 'admin');
const toast = useToast();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Family Info', href: '/family-info' },
];

const familyForm = useForm({
    family_information: props.family_information ?? '',
});

function submitFamilyInfo() {
    familyForm.put('/family-info', {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Saved', detail: 'Family information updated.', life: 3000 }),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Family Info" />
        <Toast />

        <div class="p-6">
            <h1 class="mb-6 text-2xl font-semibold">Family Information</h1>

            <template v-if="isAdmin">
                <form @submit.prevent="submitFamilyInfo" class="space-y-6">
                    <Editor v-model="familyForm.family_information" editorStyle="height: 320px" />
                    <small v-if="familyForm.errors.family_information" class="text-red-500">{{ familyForm.errors.family_information }}</small>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="familyForm.processing">Save</Button>
                    </div>
                </form>
            </template>

            <template v-else>
                <div
                    v-if="family_information"
                    class="prose dark:prose-invert max-w-none rounded-lg border p-6"
                    v-html="family_information"
                />
                <div v-else class="rounded-lg border p-6 text-center text-muted-foreground">
                    No family information available.
                </div>
            </template>
        </div>
    </AppLayout>
</template>
