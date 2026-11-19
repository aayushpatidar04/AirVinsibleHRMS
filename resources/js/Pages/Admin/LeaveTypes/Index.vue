<script setup>
import { ref } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Modal from "@/Components/Common/Modal.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";

defineProps({ types: Array, clients: Array });

const show = ref(false);
const editing = ref(null);
const form = useForm({ name: "", code: "", description: "", client_id: "", is_paid: true, is_active: true });

const add = () => { editing.value = null; form.reset(); form.clearErrors(); show.value = true; };
const edit = (t) => {
    editing.value = t;
    form.clearErrors();
    Object.assign(form, { name: t.name, code: t.code, description: t.description ?? "", client_id: t.client_id ?? "", is_paid: t.is_paid, is_active: t.is_active });
    show.value = true;
};
const save = () => {
    const data = form.transform((d) => ({ ...d, client_id: d.client_id || null }));
    const options = { preserveScroll: true, onSuccess: () => (show.value = false) };
    editing.value ? data.put(route("admin.leave-types.update", editing.value.id), options) : data.post(route("admin.leave-types.store"), options);
};
const remove = (t) => confirm(`Delete ${t.name}?`) && router.delete(route("admin.leave-types.destroy", t.id), { preserveScroll: true });
</script>

<template>
    <Head title="Leave types" />

    <AppLayout>
        <PageHeader title="Leave types" subtitle="Casual, sick, earned, unpaid… A type only reaches employees once a leave policy exists for it.">
            <Link :href="route('admin.leave-policies.index')" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Leave policies</Link>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" @click="add">Add leave type</button>
        </PageHeader>

        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Code</th><th class="px-4 py-3">Client</th><th class="px-4 py-3">Pay</th><th class="px-4 py-3">Policies</th><th class="px-4 py-3">Active</th><th class="px-4 py-3" /></tr></thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="t in types" :key="t.id">
                        <td class="px-4 py-2.5 font-medium text-gray-800">{{ t.name }}<p v-if="t.description" class="text-xs font-normal text-gray-400">{{ t.description }}</p></td>
                        <td class="px-4 py-2.5 font-mono text-xs text-gray-600">{{ t.code }}</td>
                        <td class="px-4 py-2.5 text-gray-600">{{ t.client }}</td>
                        <td class="px-4 py-2.5"><span :class="['rounded px-2 py-0.5 text-xs font-semibold', t.is_paid ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500']">{{ t.is_paid ? "Paid" : "Unpaid (LOP)" }}</span></td>
                        <td class="px-4 py-2.5" :class="t.policies ? 'text-gray-600' : 'text-orange-600'">{{ t.policies || "none yet" }}</td>
                        <td class="px-4 py-2.5">{{ t.is_active ? "Yes" : "No" }}</td>
                        <td class="px-4 py-2.5 text-right text-xs"><button class="mr-3 font-medium text-indigo-600 hover:underline" @click="edit(t)">Edit</button><button class="font-medium text-red-600 hover:underline" @click="remove(t)">Delete</button></td>
                    </tr>
                    <tr v-if="!types.length"><td colspan="7" class="px-4 py-10 text-center text-gray-500">No leave types yet.</td></tr>
                </tbody>
            </table>
        </div>

        <Modal :show="show" :title="editing ? 'Edit leave type' : 'Add leave type'" @close="show = false">
            <form class="space-y-4 p-6" @submit.prevent="save">
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-2"><label class="text-sm font-medium text-gray-700">Name</label><input v-model="form.name" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" placeholder="Casual Leave" /><p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p></div>
                    <div><label class="text-sm font-medium text-gray-700">Code</label><input v-model="form.code" required maxlength="12" class="mt-1 w-full rounded-lg border-gray-300 text-sm uppercase" placeholder="CL" /><p v-if="form.errors.code" class="mt-1 text-xs text-red-600">{{ form.errors.code }}</p></div>
                </div>
                <div><label class="text-sm font-medium text-gray-700">Description</label><input v-model="form.description" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></div>
                <div><label class="text-sm font-medium text-gray-700">Client</label><select v-model="form.client_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="">All clients</option><option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option></select></div>
                <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.is_paid" type="checkbox" class="rounded border-gray-300" /> Paid leave (unpaid leave becomes loss of pay in payroll)</label>
                <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.is_active" type="checkbox" class="rounded border-gray-300" /> Active</label>
                <p v-if="form.errors.leave_type" class="text-xs text-red-600">{{ form.errors.leave_type }}</p>
                <div class="flex justify-end gap-3"><button type="button" class="rounded-lg border px-4 py-2 text-sm" @click="show = false">Cancel</button><button type="submit" :disabled="form.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Save</button></div>
            </form>
        </Modal>
    </AppLayout>
</template>
