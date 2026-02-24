<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { api } from '../lib/api';

const isLoading = ref(false);
const error = ref('');

const orders = ref([]);
const pagination = ref(null);

const state = reactive({
    page: 1,
    perPage: 10,
});

const productsOptions = ref([]);

const create = reactive({
    items: [{ product_id: '', quantity: 1 }],
});

const isCreating = ref(false);
const createError = ref('');
const createMessage = ref('');

async function loadProducts() {
    try {
        const res = await api.get('/products', {
            params: { per_page: 1000, sort_by: 'name', sort_dir: 'asc' },
        });
        productsOptions.value = res.data?.data ?? [];
    } catch {
        productsOptions.value = [];
    }
}

async function loadOrders() {
    isLoading.value = true;
    error.value = '';

    try {
        const res = await api.get('/orders', {
            params: { per_page: state.perPage, page: state.page },
        });
        orders.value = res.data?.data ?? [];
        pagination.value = res.data;
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load orders.';
    } finally {
        isLoading.value = false;
    }
}

function nextPage() {
    if (!pagination.value) return;
    if (pagination.value.current_page >= pagination.value.last_page) return;
    state.page += 1;
    loadOrders();
}

function prevPage() {
    if (!pagination.value) return;
    if (pagination.value.current_page <= 1) return;
    state.page -= 1;
    loadOrders();
}

function addItem() {
    create.items.push({ product_id: '', quantity: 1 });
}

function removeItem(idx) {
    if (create.items.length <= 1) return;
    create.items.splice(idx, 1);
}

const canCreate = computed(() => true);

async function createOrder() {
    createError.value = '';
    createMessage.value = '';
    isCreating.value = true;

    try {
        const items = create.items
            .map((i) => ({
                product_id: Number(i.product_id),
                quantity: Number(i.quantity),
            }))
            .filter((i) => i.product_id && i.quantity >= 1);

        if (items.length === 0) {
            throw new Error('Add at least one valid item.');
        }

        await api.post('/orders', { items });

        create.items = [{ product_id: '', quantity: 1 }];
        createMessage.value = 'Order created.';
        state.page = 1;
        await loadOrders();
    } catch (err) {
        const apiErrors = err?.response?.data?.errors;
        if (apiErrors) {
            createError.value = Object.values(apiErrors).flat().join(' ');
        } else {
            createError.value = err?.response?.data?.message || err?.message || 'Failed to create order.';
        }
    } finally {
        isCreating.value = false;
    }
}

function allowedNextStatuses(status) {
    if (status === 'draft') return ['confirmed', 'cancelled'];
    if (status === 'confirmed') return ['completed', 'cancelled'];
    return [];
}

async function updateStatus(orderId, newStatus) {
    try {
        await api.post(`/orders/${orderId}/status`, { status: newStatus });
        await loadOrders();
    } catch (err) {
        alert(err?.response?.data?.message || 'Failed to update status.');
    }
}

onMounted(async () => {
    await Promise.all([loadProducts(), loadOrders()]);
});
</script>

<template>
    <div>
        <div class="flex items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold">Orders</h1>
                <p class="mt-1 text-sm text-gray-600">Create draft orders and manage status transitions.</p>
            </div>
        </div>

        <div v-if="error" class="mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ error }}
        </div>

        <section v-if="canCreate" class="mt-6 rounded-lg border bg-white p-4">
            <h2 class="font-medium">Create Order</h2>

            <form class="mt-3 space-y-3" @submit.prevent="createOrder">
                <div v-for="(item, idx) in create.items" :key="idx" class="grid gap-3 md:grid-cols-6">
                    <div class="md:col-span-4">
                        <label class="text-xs text-gray-600">Product</label>
                        <select v-model="item.product_id" class="mt-1 w-full rounded-md border px-3 py-2 text-sm">
                            <option value="">Select product</option>
                            <option v-for="p in productsOptions" :key="p.id" :value="String(p.id)">{{ p.name }} ({{ p.sku }})</option>
                        </select>
                    </div>

                    <div class="md:col-span-1">
                        <label class="text-xs text-gray-600">Qty</label>
                        <input v-model.number="item.quantity" type="number" min="1" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" />
                    </div>

                    <div class="md:col-span-1 flex items-end">
                        <button
                            type="button"
                            class="w-full rounded-md border px-3 py-2 text-sm hover:bg-gray-50 disabled:opacity-50"
                            :disabled="create.items.length <= 1"
                            @click="removeItem(idx)"
                        >
                            Remove
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" class="rounded-md border px-3 py-2 text-sm hover:bg-gray-50" @click="addItem">Add item</button>
                    <button
                        type="submit"
                        class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
                        :disabled="isCreating"
                    >
                        {{ isCreating ? 'Creating…' : 'Create order' }}
                    </button>
                </div>

                <div v-if="createError" class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    {{ createError }}
                </div>
                <div v-if="createMessage" class="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
                    {{ createMessage }}
                </div>
            </form>
        </section>

        <section class="mt-6 overflow-hidden rounded-lg border bg-white">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs text-gray-600">
                    <tr>
                        <th class="px-3 py-2">Order</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Totals</th>
                        <th class="px-3 py-2">Items</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="o in orders" :key="o.id" class="border-t align-top">
                        <td class="px-3 py-2">
                            <div class="font-medium">{{ o.order_number }}</div>
                            <div class="text-xs text-gray-500">{{ o.created_at }}</div>
                        </td>
                        <td class="px-3 py-2">{{ o.status }}</td>
                        <td class="px-3 py-2">
                            <div class="text-xs text-gray-600">Subtotal: {{ o.subtotal }}</div>
                            <div class="text-xs text-gray-600">Tax: {{ o.tax_total }}</div>
                            <div class="font-medium">Grand: {{ o.grand_total }}</div>
                        </td>
                        <td class="px-3 py-2">
                            <ul class="space-y-1">
                                <li v-for="it in o.items" :key="it.id" class="text-xs">
                                    <span class="font-medium">{{ it.product?.name ?? 'Product' }}</span> — qty {{ it.quantity }}
                                </li>
                            </ul>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <div v-if="allowedNextStatuses(o.status).length" class="flex items-center justify-end gap-2">
                                <select
                                    class="rounded-md border px-2 py-1 text-xs"
                                    @change="updateStatus(o.id, $event.target.value)"
                                >
                                    <option value="" selected disabled>Change status</option>
                                    <option v-for="s in allowedNextStatuses(o.status)" :key="s" :value="s">{{ s }}</option>
                                </select>
                            </div>
                            <div v-else class="text-xs text-gray-500">—</div>
                        </td>
                    </tr>
                    <tr v-if="!isLoading && orders.length === 0" class="border-t">
                        <td class="px-3 py-6 text-center text-gray-500" colspan="5">No orders found.</td>
                    </tr>
                </tbody>
            </table>

            <div class="flex items-center justify-between border-t px-3 py-2 text-sm">
                <div class="text-gray-600">
                    Page {{ pagination?.current_page ?? 1 }} of {{ pagination?.last_page ?? 1 }}
                </div>
                <div class="flex gap-2">
                    <button class="rounded-md border px-3 py-1.5 hover:bg-gray-50" type="button" @click="prevPage">Prev</button>
                    <button class="rounded-md border px-3 py-1.5 hover:bg-gray-50" type="button" @click="nextPage">Next</button>
                </div>
            </div>
        </section>
    </div>
</template>
