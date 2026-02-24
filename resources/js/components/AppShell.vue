<script setup>
import { computed } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();

const roleLabel = computed(() => {
    const id = auth.roleId;
    if (id === 1) return 'Super Admin';
    if (id === 2) return 'Branch Manager';
    if (id === 3) return 'Sales User';
    return 'User';
});

async function onLogout() {
    await auth.logout();
    router.push('/login');
}
</script>

<template>
    <div class="min-h-screen">
        <header class="border-b bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
                <div class="flex items-center gap-4">
                    <RouterLink to="/" class="text-lg font-semibold">ERP Inventory</RouterLink>
                    <nav class="flex items-center gap-3 text-sm">
                        <RouterLink to="/" class="text-gray-700 hover:text-gray-900">Dashboard</RouterLink>
                        <RouterLink to="/products" class="text-gray-700 hover:text-gray-900">Products</RouterLink>
                        <RouterLink to="/inventory" class="text-gray-700 hover:text-gray-900">Inventory</RouterLink>
                        <RouterLink to="/orders" class="text-gray-700 hover:text-gray-900">Orders</RouterLink>
                        <RouterLink
                            v-if="auth.isSuperAdmin"
                            to="/admin/users"
                            class="text-gray-700 hover:text-gray-900"
                        >
                            Users
                        </RouterLink>
                        <RouterLink
                            v-if="auth.isSuperAdmin"
                            to="/admin/branches"
                            class="text-gray-700 hover:text-gray-900"
                        >
                            Branches
                        </RouterLink>
                    </nav>
                </div>

                <div class="flex items-center gap-3 text-sm">
                    <div class="text-right">
                        <div class="font-medium">{{ auth.user?.name ?? '—' }}</div>
                        <div class="text-gray-500">{{ roleLabel }}</div>
                    </div>
                    <button
                        type="button"
                        class="rounded-md border px-3 py-1.5 hover:bg-gray-50"
                        @click="onLogout"
                    >
                        Logout
                    </button>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-6">
            <slot />
        </main>
    </div>
</template>
