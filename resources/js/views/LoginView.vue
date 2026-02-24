<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const email = ref('admin@erp.com');
const password = ref('password123');
const error = ref('');
const isSubmitting = ref(false);

async function onSubmit() {
    error.value = '';
    isSubmitting.value = true;
    try {
        await auth.login({ email: email.value, password: password.value });
        const next = typeof route.query.next === 'string' ? route.query.next : '/';
        router.push(next);
    } catch (err) {
        const message = err?.response?.data?.message;
        error.value = message || 'Login failed.';
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <div class="mx-auto flex min-h-screen max-w-md items-center px-4">
            <div class="w-full rounded-lg border bg-white p-6">
                <h1 class="text-xl font-semibold">Sign in</h1>
                <p class="mt-1 text-sm text-gray-600">Use your ERP credentials to continue.</p>

                <form class="mt-6 space-y-4" @submit.prevent="onSubmit">
                    <div>
                        <label class="text-sm font-medium">Email</label>
                        <input
                            v-model="email"
                            type="email"
                            class="mt-1 w-full rounded-md border px-3 py-2"
                            autocomplete="email"
                            required
                        />
                    </div>

                    <div>
                        <label class="text-sm font-medium">Password</label>
                        <input
                            v-model="password"
                            type="password"
                            class="mt-1 w-full rounded-md border px-3 py-2"
                            autocomplete="current-password"
                            required
                        />
                    </div>

                    <div v-if="error" class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                        {{ error }}
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-md bg-black px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
                        :disabled="isSubmitting"
                    >
                        {{ isSubmitting ? 'Signing in…' : 'Sign in' }}
                    </button>

                    <div class="text-xs text-gray-500">
                        Default seeded admin: <span class="font-mono">admin@erp.com</span> / <span class="font-mono">password123</span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
