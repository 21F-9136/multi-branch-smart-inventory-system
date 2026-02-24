<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { api } from '../lib/api';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();

const state = reactive({
    search: '',
    perPage: 10,
    page: 1,
    sortBy: 'id',
    sortDir: 'desc',
});

const isLoading = ref(false);
const error = ref('');
const products = ref([]);
const pagination = ref(null);

const canWrite = computed(() => auth.isSuperAdmin);

const createForm = reactive({
    name: '',
    sku: '',
    cost_price: '',
    sale_price: '',
    tax_percentage: '',
    status: 'active',
});
const createError = ref('');
const isCreating = ref(false);

async function load() {
    isLoading.value = true;
    error.value = '';

    try {
        const res = await api.get('/products', {
            params: {
                search: state.search || undefined,
                per_page: state.perPage,
                page: state.page,
                sort_by: state.sortBy,
                sort_dir: state.sortDir,
            },
        });

        products.value = res.data?.data ?? [];
        pagination.value = res.data;
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load products.';
    } finally {
        isLoading.value = false;
    }
}

function nextPage() {
    if (!pagination.value) return;
    if (pagination.value.current_page >= pagination.value.last_page) return;
    state.page += 1;
    load();
}

function prevPage() {
    if (!pagination.value) return;
    if (pagination.value.current_page <= 1) return;
    state.page -= 1;
    load();
}

async function createProduct() {
    createError.value = '';
    isCreating.value = true;
    try {
        await api.post('/products', {
            name: createForm.name,
            sku: createForm.sku,
            cost_price: Number(createForm.cost_price),
            sale_price: Number(createForm.sale_price),
            tax_percentage: createForm.tax_percentage === '' ? null : Number(createForm.tax_percentage),
            status: createForm.status,
        });

        createForm.name = '';
        createForm.sku = '';
        createForm.cost_price = '';
        createForm.sale_price = '';
        createForm.tax_percentage = '';
        createForm.status = 'active';

        state.page = 1;
        await load();
    } catch (err) {
        const message = err?.response?.data?.message;
        const errors = err?.response?.data?.errors;
        if (errors) {
            createError.value = Object.values(errors).flat().join(' ');
        } else {
            createError.value = message || 'Failed to create product.';
        }
    } finally {
        isCreating.value = false;
    }
}

async function deleteProduct(productId) {
    if (!confirm('Delete this product?')) return;

    try {
        await api.delete(`/products/${productId}`);
        await load();
    } catch (err) {
        alert(err?.response?.data?.message || 'Failed to delete product.');
    }
}

onMounted(load);
</script>

<template>
    <div>
        <div class="flex items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold">Products</h1>
                <p class="mt-1 text-sm text-gray-600">Browse and manage products.</p>
            </div>

            <div class="flex items-end gap-2">
                <div>
                    <label class="text-xs text-gray-600">Search</label>
                    <input
                        v-model="state.search"
                        class="mt-1 w-64 rounded-md border px-3 py-2 text-sm"
                        placeholder="Name or SKU"
                        @keyup.enter="state.page = 1; load()"
                    />
                </div>
                <button
                    class="rounded-md border px-3 py-2 text-sm hover:bg-gray-50"
                    type="button"
                    :disabled="isLoading"
                    @click="state.page = 1; load()"
                >
                    Search
                </button>
            </div>
        </div>

        <div v-if="error" class="mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ error }}
        </div>

        <section v-if="canWrite" class="mt-6 rounded-lg border bg-white p-4">
            <h2 class="font-medium">Create Product</h2>
            <form class="mt-3 grid gap-3 md:grid-cols-2" @submit.prevent="createProduct">
                <div>
                    <label class="text-xs text-gray-600">Name</label>
                    <input v-model="createForm.name" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-xs text-gray-600">SKU</label>
                    <input v-model="createForm.sku" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-xs text-gray-600">Cost price</label>
                    <input v-model="createForm.cost_price" type="number" min="0" step="0.01" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-xs text-gray-600">Sale price</label>
                    <input v-model="createForm.sale_price" type="number" min="0" step="0.01" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-xs text-gray-600">Tax %</label>
                    <input v-model="createForm.tax_percentage" type="number" min="0" max="100" step="0.01" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="text-xs text-gray-600">Status</label>
                    <select v-model="createForm.status" class="mt-1 w-full rounded-md border px-3 py-2 text-sm">
                        <option value="active">active</option>
                        <option value="inactive">inactive</option>
                    </select>
                </div>

                <div v-if="createError" class="md:col-span-2 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    {{ createError }}
                </div>

                <div class="md:col-span-2">
                    <button
                        type="submit"
                        class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
                        :disabled="isCreating"
                    >
                        {{ isCreating ? 'Creating…' : 'Create' }}
                    </button>
                </div>
            </form>
        </section>

        <section class="mt-6 overflow-hidden rounded-lg border bg-white">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs text-gray-600">
                    <tr>
                        <th class="px-3 py-2">Name</th>
                        <th class="px-3 py-2">SKU</th>
                        <th class="px-3 py-2">Sale</th>
                        <th class="px-3 py-2">Tax %</th>
                        <th class="px-3 py-2">Status</th>
                        <th v-if="canWrite" class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in products" :key="p.id" class="border-t">
                        <td class="px-3 py-2 font-medium">{{ p.name }}</td>
                        <td class="px-3 py-2">{{ p.sku }}</td>
                        <td class="px-3 py-2">{{ p.sale_price }}</td>
                        <td class="px-3 py-2">{{ p.tax_percentage ?? '—' }}</td>
                        <td class="px-3 py-2">{{ p.status }}</td>
                        <td v-if="canWrite" class="px-3 py-2 text-right">
                            <button class="text-red-700 hover:underline" type="button" @click="deleteProduct(p.id)">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="products.length === 0" class="border-t">
                        <td class="px-3 py-6 text-center text-gray-500" :colspan="canWrite ? 6 : 5">No products found.</td>
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
