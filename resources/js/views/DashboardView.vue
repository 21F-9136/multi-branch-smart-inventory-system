<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../lib/api';

const branchSummary = ref(null);
const salesSummary = ref(null);
const error = ref('');
const isLoading = ref(false);

async function load() {
    isLoading.value = true;
    error.value = '';

    try {
        const [branchRes, salesRes] = await Promise.allSettled([
            api.get('/dashboard/branch-summary'),
            api.get('/dashboard/sales-summary'),
        ]);

        if (branchRes.status === 'fulfilled') {
            branchSummary.value = branchRes.value.data;
        } else {
            const msg = branchRes.reason?.response?.data?.message;
            if (msg) error.value = msg;
        }

        if (salesRes.status === 'fulfilled') {
            salesSummary.value = salesRes.value.data;
        }
    } catch {
        error.value = 'Failed to load dashboard.';
    } finally {
        isLoading.value = false;
    }
}

onMounted(load);
</script>

<template>
    <div>
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Dashboard</h1>
            <button
                class="rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50"
                type="button"
                :disabled="isLoading"
                @click="load"
            >
                Refresh
            </button>
        </div>

        <div v-if="error" class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
            {{ error }}
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <section class="rounded-lg border bg-white p-4">
                <h2 class="font-medium">Branch Summary</h2>
                <div v-if="!branchSummary" class="mt-2 text-sm text-gray-500">No data.</div>
                <dl v-else class="mt-3 grid grid-cols-1 gap-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Total products</dt>
                        <dd class="font-medium">{{ branchSummary.total_products }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Available stock</dt>
                        <dd class="font-medium">{{ branchSummary.total_available_stock }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Low stock items</dt>
                        <dd class="font-medium">{{ branchSummary.low_stock_count }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-lg border bg-white p-4">
                <h2 class="font-medium">Sales Summary</h2>
                <div v-if="!salesSummary" class="mt-2 text-sm text-gray-500">No data.</div>
                <dl v-else class="mt-3 grid grid-cols-1 gap-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Total orders</dt>
                        <dd class="font-medium">{{ salesSummary.total_orders }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Completed orders</dt>
                        <dd class="font-medium">{{ salesSummary.completed_orders }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Pending orders</dt>
                        <dd class="font-medium">{{ salesSummary.pending_orders }}</dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>
</template>
