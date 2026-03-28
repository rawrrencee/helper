<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/login';
import { ref } from 'vue';

defineProps<{
    status?: string;
}>();

const page = usePage();
const demoLoading = ref<string | null>(null);

function demoLogin(username: string, password: string) {
    demoLoading.value = username;
    router.post(store(), { username, password, remember: true }, {
        onFinish: () => {
            demoLoading.value = null;
        },
    });
}
</script>

<template>
    <AuthBase
        title="Log in to your account"
        description="Enter your username or FIN and password below to log in"
    >
        <Head title="Log in" />

        <div
            v-if="page.props.demo"
            class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950"
        >
            <p class="mb-3 text-center text-sm font-semibold text-blue-800 dark:text-blue-200">
                Demo Mode
            </p>
            <div class="flex gap-2">
                <Button
                    v-for="account in page.props.demo"
                    :key="account.username"
                    type="button"
                    variant="outline"
                    class="flex-1"
                    :disabled="demoLoading !== null"
                    @click="demoLogin(account.username, account.password)"
                >
                    <Spinner v-if="demoLoading === account.username" />
                    Log in as {{ account.label }}
                </Button>
            </div>
        </div>

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="username">Username / FIN</Label>
                    <Input
                        id="username"
                        type="text"
                        name="username"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="username"
                        placeholder="Username or FIN"
                    />
                    <InputError :message="errors.username" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span>Remember me</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" />
                    Log in
                </Button>
            </div>
        </Form>
    </AuthBase>
</template>
