<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { api } from '../lib/api';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();

const inventories = ref([]);
const isLoading = ref(false);
const error = ref('');

const productsOptions = ref([]);
const branchesOptions = ref([]);

const filter = reactive({
    branchId: '',
});

const form = reactive({
    action: 'add',
    branch_id: '',
    product_id: '',
    quantity: 1,
    from_branch_id: '',
    to_branch_id: '',
});

const message = ref('');
const formError = ref('');
const isSubmitting = ref(false);

const isSuperAdmin = computed(() => auth.isSuperAdmin);

function effectiveBranchId() {
    if (isSuperAdmin.value) return form.branch_id;
    return auth.user?.branch_id ?? '';
}

async function loadInventory() {
    isLoading.value = true;
    error.value = '';
    try {
        const res = await api.get('/inventory', {
            params: {
                branch_id: isSuperAdmin.value ? (filter.branchId || undefined) : undefined,
            },
        });
        inventories.value = res.data?.data ?? [];
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load inventory.';
    } finally {
        isLoading.value = false;
    }
}

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

async function loadBranches() {
    if (!isSuperAdmin.value) return;
    try {
        const res = await api.get('/branches', { params: { per_page: 1000 } });
        branchesOptions.value = res.data?.data ?? [];
    } catch {
        branchesOptions.value = [];
    }
}

async function submit() {
    message.value = '';
    formError.value = '';
    isSubmitting.value = true;

    try {
        const product_id = Number(form.product_id);
        const quantity = Number(form.quantity);

        if (!product_id) throw new Error('Select a product.');
        if (!quantity || quantity < 1) throw new Error('Quantity must be at least 1.');

        if (form.action === 'transfer') {
            if (!isSuperAdmin.value) throw new Error('Only Super Admin can transfer stock.');
            const from_branch_id = Number(form.from_branch_id);
            const to_branch_id = Number(form.to_branch_id);
            if (!from_branch_id || !to_branch_id) throw new Error('Select both branches.');

            await api.post('/inventory/transfer', {
                from_branch_id,
                to_branch_id,
                product_id,
                quantity,
            });
            message.value = 'Stock transferred successfully.';
        } else {
            const branch_id = Number(effectiveBranchId());
            if (!branch_id) throw new Error('Branch is required.');

            const endpoint =
                form.action === 'add'
                    ? '/inventory/add'
                    : form.action === 'remove'
                        ? '/inventory/remove'
                        : form.action === 'reserve'
                            ? '/inventory/reserve'
                            : '/inventory/release';

            await api.post(endpoint, {
                branch_id,
                product_id,
                quantity,
            });

            message.value = 'Inventory updated.';
        }

        await loadInventory();
    } catch (err) {
        const apiErrors = err?.response?.data?.errors;
        if (apiErrors) {
            formError.value = Object.values(apiErrors).flat().join(' ');
        } else {
            formError.value = err?.response?.data?.message || err?.message || 'Request failed.';
        }
    } finally {
        isSubmitting.value = false;
    }
}

onMounted(async () => {
    await Promise.all([loadProducts(), loadBranches()]);
    if (isSuperAdmin.value) {
        form.branch_id = branchesOptions.value?.[0]?.id ?? '';
        filter.branchId = '';
    }
    await loadInventory();
});
</script>

<template>
    <div>
        <div class="flex items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold">Inventory</h1>
                <p class="mt-1 text-sm text-gray-600">View stock per branch and perform stock movements.</p>
            </div>

            <div v-if="isSuperAdmin" class="flex items-end gap-2">
                <div>
                    <label class="text-xs text-gray-600">Filter branch</label>
                    <select v-model="filter.branchId" class="mt-1 w-64 rounded-md border px-3 py-2 text-sm" @change="loadInventory">
                        <option value="">All branches</option>
                        <option v-for="b in branchesOptions" :key="b.id" :value="String(b.id)">{{ b.name }}</option>
                    </select>
                </div>
                <button class="rounded-md border px-3 py-2 text-sm hover:bg-gray-50" type="button" @click="loadInventory">
                    Refresh
                </button>
            </div>
        </div>

        <div v-if="error" class="mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ error }}
        </div>

        <section class="mt-6 overflow-hidden rounded-lg border bg-white">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs text-gray-600">
                    <tr>
                        <th class="px-3 py-2">Branch</th>
                        <th class="px-3 py-2">Product</th>
                        <th class="px-3 py-2">Qty</th>
                        <th class="px-3 py-2">Reserved</th>
                        <th class="px-3 py-2">Available</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in inventories" :key="row.id" class="border-t">
                        <td class="px-3 py-2">{{ row.branch }}</td>
                        <td class="px-3 py-2 font-medium">{{ row.product }}</td>
                        <td class="px-3 py-2">{{ row.quantity }}</td>
                        <td class="px-3 py-2">{{ row.reserved_quantity }}</td>
                        <td class="px-3 py-2">{{ row.available_stock }}</td>
                    </tr>
                    <tr v-if="!isLoading && inventories.length === 0" class="border-t">
                        <td class="px-3 py-6 text-center text-gray-500" colspan="5">No inventory records.</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="mt-6 rounded-lg border bg-white p-4">
            <h2 class="font-medium">Stock Action</h2>

            <form class="mt-3 grid gap-3 md:grid-cols-2" @submit.prevent="submit">
                <div>
                    <label class="text-xs text-gray-600">Action</label>
                    <select v-model="form.action" class="mt-1 w-full rounded-md border px-3 py-2 text-sm">
                        <option value="add">Add stock</option>
                        <option value="remove">Remove stock</option>
                        <option value="reserve">Reserve stock</option>
                        <option value="release">Release reserved stock</option>
                        <option v-if="isSuperAdmin" value="transfer">Transfer stock</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs text-gray-600">Product</label>
                    <select v-model="form.product_id" class="mt-1 w-full rounded-md border px-3 py-2 text-sm">
                        <option value="">Select product</option>
                        <option v-for="p in productsOptions" :key="p.id" :value="String(p.id)">{{ p.name }} ({{ p.sku }})</option>
                    </select>
                </div>

                <div v-if="form.action !== 'transfer'">
                    <label class="text-xs text-gray-600">Branch</label>
                    <div v-if="isSuperAdmin">
                        <select v-model="form.branch_id" class="mt-1 w-full rounded-md border px-3 py-2 text-sm">
                            <option value="">Select branch</option>
                            <option v-for="b in branchesOptions" :key="b.id" :value="String(b.id)">{{ b.name }}</option>
                        </select>
                    </div>
                    <div v-else class="mt-1 rounded-md border bg-gray-50 px-3 py-2 text-sm">
                        Branch ID: {{ auth.user?.branch_id ?? '—' }}
                    </div>
                </div>

                <div v-else>
                    <label class="text-xs text-gray-600">From branch</label>
                    <select v-model="form.from_branch_id" class="mt-1 w-full rounded-md border px-3 py-2 text-sm">
                        <option value="">Select branch</option>
                        <option v-for="b in branchesOptions" :key="b.id" :value="String(b.id)">{{ b.name }}</option>
                    </select>
                </div>

                <div v-if="form.action === 'transfer'">
                    <label class="text-xs text-gray-600">To branch</label>
                    <select v-model="form.to_branch_id" class="mt-1 w-full rounded-md border px-3 py-2 text-sm">
                        <option value="">Select branch</option>
                        <option v-for="b in branchesOptions" :key="b.id" :value="String(b.id)">{{ b.name }}</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs text-gray-600">Quantity</label>
                    <input v-model.number="form.quantity" type="number" min="1" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" />
                </div>

                <div v-if="formError" class="md:col-span-2 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    {{ formError }}
                </div>
                <div v-if="message" class="md:col-span-2 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
                    {{ message }}
                </div>

                <div class="md:col-span-2">
                    <button
                        type="submit"
                        class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
                        :disabled="isSubmitting"
                    >
                        {{ isSubmitting ? 'Submitting…' : 'Submit' }}
                    </button>
                </div>
            </form>
        </section>
    </div>
</template>
