<script setup>
import { onMounted, reactive, ref } from 'vue';
import { api } from '../../lib/api';

const users = ref([]);
const pagination = ref(null);
const error = ref('');
const isLoading = ref(false);

const branches = ref([]);

const state = reactive({
    page: 1,
    perPage: 10,
});

const form = reactive({
    name: '',
    email: '',
    password: '',
    role_id: '3',
    branch_id: '',
});

const isCreating = ref(false);
const formError = ref('');

const editingId = ref(null);
const editForm = reactive({
    name: '',
    email: '',
    role_id: '',
    branch_id: '',
});

const roleOptions = [
    { id: 1, name: 'Super Admin' },
    { id: 2, name: 'Branch Manager' },
    { id: 3, name: 'Sales User' },
];

async function loadBranches() {
    try {
        const res = await api.get('/branches', { params: { per_page: 1000 } });
        branches.value = res.data?.data ?? [];
    } catch {
        branches.value = [];
    }
}

async function loadUsers() {
    isLoading.value = true;
    error.value = '';

    try {
        const res = await api.get('/users', {
            params: { page: state.page, per_page: state.perPage },
        });
        users.value = res.data?.data ?? [];
        pagination.value = res.data;
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load users.';
    } finally {
        isLoading.value = false;
    }
}

function nextPage() {
    if (!pagination.value) return;
    if (pagination.value.current_page >= pagination.value.last_page) return;
    state.page += 1;
    loadUsers();
}

function prevPage() {
    if (!pagination.value) return;
    if (pagination.value.current_page <= 1) return;
    state.page -= 1;
    loadUsers();
}

async function createUser() {
    formError.value = '';
    isCreating.value = true;

    try {
        await api.post('/users', {
            name: form.name,
            email: form.email,
            password: form.password,
            role_id: Number(form.role_id),
            branch_id: form.branch_id === '' ? null : Number(form.branch_id),
        });

        form.name = '';
        form.email = '';
        form.password = '';
        form.role_id = '3';
        form.branch_id = '';

        state.page = 1;
        await loadUsers();
    } catch (err) {
        const apiErrors = err?.response?.data?.errors;
        if (apiErrors) {
            formError.value = Object.values(apiErrors).flat().join(' ');
        } else {
            formError.value = err?.response?.data?.message || 'Failed to create user.';
        }
    } finally {
        isCreating.value = false;
    }
}

function startEdit(u) {
    editingId.value = u.id;
    editForm.name = u.name;
    editForm.email = u.email;
    editForm.role_id = String(u.role_id ?? '');
    editForm.branch_id = u.branch_id ? String(u.branch_id) : '';
}

function cancelEdit() {
    editingId.value = null;
}

async function saveEdit(u) {
    try {
        await api.put(`/users/${u.id}`, {
            name: editForm.name,
            email: editForm.email,
            role_id: editForm.role_id === '' ? undefined : Number(editForm.role_id),
            branch_id: editForm.branch_id === '' ? null : Number(editForm.branch_id),
        });
        editingId.value = null;
        await loadUsers();
    } catch (err) {
        alert(err?.response?.data?.message || 'Failed to update user.');
    }
}

async function deleteUser(u) {
    if (!confirm(`Delete user ${u.email}?`)) return;

    try {
        await api.delete(`/users/${u.id}`);
        await loadUsers();
    } catch (err) {
        alert(err?.response?.data?.message || 'Failed to delete user.');
    }
}

onMounted(async () => {
    await Promise.all([loadBranches(), loadUsers()]);
});
</script>

<template>
    <div>
        <div>
            <h1 class="text-xl font-semibold">Users</h1>
            <p class="mt-1 text-sm text-gray-600">Super Admin only.</p>
        </div>

        <div v-if="error" class="mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ error }}
        </div>

        <section class="mt-6 rounded-lg border bg-white p-4">
            <h2 class="font-medium">Create User</h2>
            <form class="mt-3 grid gap-3 md:grid-cols-2" @submit.prevent="createUser">
                <div>
                    <label class="text-xs text-gray-600">Name</label>
                    <input v-model="form.name" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-xs text-gray-600">Email</label>
                    <input v-model="form.email" type="email" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-xs text-gray-600">Password</label>
                    <input v-model="form.password" type="password" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" required />
                </div>
                <div>
                    <label class="text-xs text-gray-600">Role</label>
                    <select v-model="form.role_id" class="mt-1 w-full rounded-md border px-3 py-2 text-sm">
                        <option v-for="r in roleOptions" :key="r.id" :value="String(r.id)">{{ r.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-600">Branch (optional)</label>
                    <select v-model="form.branch_id" class="mt-1 w-full rounded-md border px-3 py-2 text-sm">
                        <option value="">—</option>
                        <option v-for="b in branches" :key="b.id" :value="String(b.id)">{{ b.name }}</option>
                    </select>
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
                        <th class="px-3 py-2">Email</th>
                        <th class="px-3 py-2">Role</th>
                        <th class="px-3 py-2">Branch</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="u in users" :key="u.id" class="border-t">
                        <template v-if="editingId === u.id">
                            <td class="px-3 py-2">
                                <input v-model="editForm.name" class="w-full rounded-md border px-2 py-1 text-sm" />
                            </td>
                            <td class="px-3 py-2">
                                <input v-model="editForm.email" type="email" class="w-full rounded-md border px-2 py-1 text-sm" />
                            </td>
                            <td class="px-3 py-2">
                                <select v-model="editForm.role_id" class="w-full rounded-md border px-2 py-1 text-sm">
                                    <option v-for="r in roleOptions" :key="r.id" :value="String(r.id)">{{ r.name }}</option>
                                </select>
                            </td>
                            <td class="px-3 py-2">
                                <select v-model="editForm.branch_id" class="w-full rounded-md border px-2 py-1 text-sm">
                                    <option value="">—</option>
                                    <option v-for="b in branches" :key="b.id" :value="String(b.id)">{{ b.name }}</option>
                                </select>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <button class="mr-2 text-sm hover:underline" type="button" @click="saveEdit(u)">Save</button>
                                <button class="text-sm text-gray-600 hover:underline" type="button" @click="cancelEdit">Cancel</button>
                            </td>
                        </template>
                        <template v-else>
                            <td class="px-3 py-2 font-medium">{{ u.name }}</td>
                            <td class="px-3 py-2">{{ u.email }}</td>
                            <td class="px-3 py-2">{{ u.role?.name ?? u.role_id }}</td>
                            <td class="px-3 py-2">{{ u.branch?.name ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">
                                <button class="mr-3 text-sm hover:underline" type="button" @click="startEdit(u)">Edit</button>
                                <button class="text-sm text-red-700 hover:underline" type="button" @click="deleteUser(u)">Delete</button>
                            </td>
                        </template>
                    </tr>
                    <tr v-if="!isLoading && users.length === 0" class="border-t">
                        <td class="px-3 py-6 text-center text-gray-500" colspan="5">No users found.</td>
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
