<script setup>
import { onMounted, reactive, ref } from 'vue';
import { api } from '../../lib/api';

const branches = ref([]);
const pagination = ref(null);
const error = ref('');
const isLoading = ref(false);

const state = reactive({
    page: 1,
    perPage: 10,
});

const form = reactive({
    name: '',
    location: '',
});
const isCreating = ref(false);
const formError = ref('');

const editingId = ref(null);
const editForm = reactive({ name: '', location: '' });

async function loadBranches() {
    isLoading.value = true;
    error.value = '';

    try {
        const res = await api.get('/branches', {
            params: { page: state.page, per_page: state.perPage },
        });
        branches.value = res.data?.data ?? [];
        pagination.value = res.data;
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load branches.';
    } finally {
        isLoading.value = false;
    }
}

function nextPage() {
    if (!pagination.value) return;
    if (pagination.value.current_page >= pagination.value.last_page) return;
    state.page += 1;
    loadBranches();
}

function prevPage() {
    if (!pagination.value) return;
    if (pagination.value.current_page <= 1) return;
    state.page -= 1;
    loadBranches();
}

async function createBranch() {
    formError.value = '';
    isCreating.value = true;

    try {
        await api.post('/branches', {
            name: form.name,
            location: form.location || null,
        });
        form.name = '';
        form.location = '';
        state.page = 1;
        await loadBranches();
    } catch (err) {
        const apiErrors = err?.response?.data?.errors;
        if (apiErrors) {
            formError.value = Object.values(apiErrors).flat().join(' ');
        } else {
            formError.value = err?.response?.data?.message || 'Failed to create branch.';
        }
    } finally {
        isCreating.value = false;
    }
}

function startEdit(b) {
    editingId.value = b.id;
    editForm.name = b.name;
    editForm.location = b.location ?? '';
}

function cancelEdit() {
    editingId.value = null;
}

async function saveEdit(b) {
    try {
        await api.put(`/branches/${b.id}`, {
            name: editForm.name,
            location: editForm.location || null,
        });
        editingId.value = null;
        await loadBranches();
    } catch (err) {
        alert(err?.response?.data?.message || 'Failed to update branch.');
    }
}

async function deleteBranch(b) {
    if (!confirm(`Delete branch ${b.name}?`)) return;
    try {
        await api.delete(`/branches/${b.id}`);
        await loadBranches();
    } catch (err) {
        alert(err?.response?.data?.message || 'Failed to delete branch.');
    }
}

onMounted(loadBranches);
</script>

<template>
    <div>
        <div>
            <h1 class="text-xl font-semibold">Branches</h1>
            <p class="mt-1 text-sm text-gray-600">Super Admin only.</p>
        </div>

        <div v-if="error" class="mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ error }}
        </div>

        <section class="mt-6 rounded-lg border bg-white p-4">
            <h2 class="font-medium">Create Branch</h2>
            <form class="mt-3 grid gap-3 md:grid-cols-2" @submit.prevent="createBranch">
                <div>
                    <label class="text-xs text-gray-600">Name</label>
                    <input v-model="form.name" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-xs text-gray-600">Location</label>
                    <input v-model="form.location" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" />
                </div>

                <div v-if="formError" class="md:col-span-2 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    {{ formError }}
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
                        <th class="px-3 py-2">Location</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="b in branches" :key="b.id" class="border-t">
                        <template v-if="editingId === b.id">
                            <td class="px-3 py-2">
                                <input v-model="editForm.name" class="w-full rounded-md border px-2 py-1 text-sm" />
                            </td>
                            <td class="px-3 py-2">
                                <input v-model="editForm.location" class="w-full rounded-md border px-2 py-1 text-sm" />
                            </td>
                            <td class="px-3 py-2 text-right">
                                <button class="mr-2 text-sm hover:underline" type="button" @click="saveEdit(b)">Save</button>
                                <button class="text-sm text-gray-600 hover:underline" type="button" @click="cancelEdit">Cancel</button>
                            </td>
                        </template>
                        <template v-else>
                            <td class="px-3 py-2 font-medium">{{ b.name }}</td>
                            <td class="px-3 py-2">{{ b.location ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">
                                <button class="mr-3 text-sm hover:underline" type="button" @click="startEdit(b)">Edit</button>
                                <button class="text-sm text-red-700 hover:underline" type="button" @click="deleteBranch(b)">Delete</button>
                            </td>
                        </template>
                    </tr>
                    <tr v-if="!isLoading && branches.length === 0" class="border-t">
                        <td class="px-3 py-6 text-center text-gray-500" colspan="3">No branches found.</td>
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
