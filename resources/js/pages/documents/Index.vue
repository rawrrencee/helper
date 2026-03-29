<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import FileUpload from 'primevue/fileupload';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Document = {
    id: number;
    name: string;
    mime_type: string | null;
    file_size: number;
    hidden_from_helper: boolean;
    created_at: string;
};

type HelperSummary = {
    id: number;
    name: string;
};

const props = defineProps<{
    helper: HelperSummary;
    documents: {
        data: Document[];
        current_page: number;
        per_page: number;
        total: number;
    };
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).role === 'admin');
const toast = useToast();
const hideFromHelper = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    ...(isAdmin.value ? [{ title: 'Helpers', href: '/helpers' }] : []),
    { title: props.helper.name, href: `/helpers/${props.helper.id}` },
    { title: 'Documents', href: `/helpers/${props.helper.id}/documents` },
];

function formatFileSize(bytes: number): string {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function onUpload(event: any) {
    const file = event.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);
    formData.append('hidden_from_helper', hideFromHelper.value ? '1' : '0');

    router.post(`/helpers/${props.helper.id}/documents`, formData, {
        forceFormData: true,
        onSuccess: () => {
            hideFromHelper.value = false;
            toast.add({ severity: 'success', summary: 'Uploaded', detail: 'Document uploaded.', life: 3000 });
        },
        onError: (errors: Record<string, string>) => {
            if (errors.demo) {
                toast.add({ severity: 'warn', summary: 'Demo Mode', detail: errors.demo, life: 3000 });
            }
        },
    });
}

function toggleVisibility(doc: Document) {
    router.patch(`/documents/${doc.id}/toggle-visibility`, {}, {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Updated', detail: 'Document visibility updated.', life: 3000 }),
    });
}

function confirmDelete(doc: Document) {
    if (confirm(`Delete "${doc.name}"?`)) {
        router.delete(`/documents/${doc.id}`, {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Deleted', detail: 'Document deleted.', life: 3000 }),
        });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Documents - ${helper.name}`" />
        <Toast />

        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Documents - {{ helper.name }}</h1>
            </div>

            <div v-if="isAdmin" class="rounded-lg border p-4">
                <h2 class="mb-3 text-sm font-medium">Upload Document</h2>
                <FileUpload
                    mode="basic"
                    :auto="true"
                    :maxFileSize="10485760"
                    chooseLabel="Choose File"
                    @select="onUpload"
                />
                <div class="mt-3 flex items-center gap-2">
                    <Checkbox v-model="hideFromHelper" :binary="true" inputId="hideFromHelper" />
                    <label for="hideFromHelper" class="text-sm">Hide from helper</label>
                </div>
            </div>

            <DataTable
                :value="documents.data"
                dataKey="id"
                stripedRows
                class="text-sm"
            >
                <Column field="name" header="Name">
                    <template #body="{ data }">
                        <span :class="{ 'text-muted-foreground': data.hidden_from_helper }">{{ data.name }}</span>
                        <Tag v-if="data.hidden_from_helper && isAdmin" value="Hidden" severity="warn" class="ml-2" />
                    </template>
                </Column>
                <Column field="file_size" header="Size">
                    <template #body="{ data }">
                        {{ formatFileSize(data.file_size) }}
                    </template>
                </Column>
                <Column field="created_at" header="Uploaded">
                    <template #body="{ data }">
                        {{ new Date(data.created_at).toLocaleDateString() }}
                    </template>
                </Column>
                <Column header="Actions" style="width: 12rem">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            <a :href="`/documents/${data.id}/download`">
                                <Button icon="pi pi-download" severity="secondary" text rounded size="small" />
                            </a>
                            <Button
                                v-if="isAdmin"
                                :icon="data.hidden_from_helper ? 'pi pi-eye-slash' : 'pi pi-eye'"
                                :severity="data.hidden_from_helper ? 'warn' : 'secondary'"
                                text
                                rounded
                                size="small"
                                @click="toggleVisibility(data)"
                                v-tooltip.top="data.hidden_from_helper ? 'Show to helper' : 'Hide from helper'"
                            />
                            <Button v-if="isAdmin" icon="pi pi-trash" severity="danger" text rounded size="small" @click="confirmDelete(data)" />
                        </div>
                    </template>
                </Column>

                <template #empty>
                    <div class="py-8 text-center text-muted-foreground">
                        No documents uploaded yet.
                    </div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
