<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem } from '@/types';
import Textarea from 'primevue/textarea';

const props = defineProps<{
    employer_name: string;
    employer_address: string;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Employer settings', href: '/settings/employer' },
];

const form = useForm({
    employer_name: props.employer_name ?? '',
    employer_address: props.employer_address ?? '',
});

function submit() {
    form.put('/settings/employer');
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Employer settings" />

        <h1 class="sr-only">Employer settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Employer information"
                    description="This information appears on salary slips"
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="employer_name">Employer Name</Label>
                        <Input
                            id="employer_name"
                            v-model="form.employer_name"
                            class="mt-1 block w-full"
                            placeholder="Full name"
                        />
                        <InputError class="mt-2" :message="form.errors.employer_name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="employer_address">Employer Address</Label>
                        <Textarea
                            id="employer_address"
                            v-model="form.employer_address"
                            rows="3"
                            class="mt-1 w-full"
                            placeholder="Address"
                        />
                        <InputError class="mt-2" :message="form.errors.employer_address" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
