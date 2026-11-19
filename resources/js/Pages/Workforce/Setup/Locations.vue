<script setup>
import { ref } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Modal from "@/Components/Common/Modal.vue";
import PageHeader from "@/Components/Workforce/PageHeader.vue";

defineProps({ locations: Array, clients: Array, branches: Array });

const show = ref(false);
const editing = ref(null);
const locating = ref(false);

const form = useForm({
    name: "",
    latitude: "",
    longitude: "",
    radius_meters: 10,
    max_accuracy_meters: 50,
    client_id: "",
    branch_id: "",
    is_active: true,
});

const add = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    show.value = true;
};

const edit = (l) => {
    editing.value = l;
    form.clearErrors();
    Object.assign(form, { name: l.name, latitude: l.latitude, longitude: l.longitude, radius_meters: l.radius_meters, max_accuracy_meters: l.max_accuracy_meters, client_id: l.client_id ?? "", branch_id: l.branch_id ?? "", is_active: l.is_active });
    show.value = true;
};

const useMyLocation = () => {
    if (!navigator.geolocation) return alert("This browser cannot provide your location.");
    locating.value = true;
    navigator.geolocation.getCurrentPosition(
        (p) => {
            form.latitude = Number(p.coords.latitude.toFixed(7));
            form.longitude = Number(p.coords.longitude.toFixed(7));
            locating.value = false;
        },
        () => {
            locating.value = false;
            alert("Could not read your location. Allow location access or type the coordinates.");
        },
        { enableHighAccuracy: true, timeout: 15000 },
    );
};

const save = () => {
    const options = { preserveScroll: true, onSuccess: () => (show.value = false) };
    const data = form.transform((d) => ({ ...d, client_id: d.client_id || null, branch_id: d.branch_id || null }));
    editing.value ? data.put(route("workforce.setup.locations.update", editing.value.id), options) : data.post(route("workforce.setup.locations.store"), options);
};

const remove = (l) => confirm(`Remove ${l.name}? Employees will no longer be able to punch there.`) && router.delete(route("workforce.setup.locations.destroy", l.id), { preserveScroll: true });
</script>

<template>
    <Head title="Office locations" />

    <AppLayout>
        <PageHeader title="Office locations" subtitle="Employees can punch in and out only within the radius of an authorised location. Leave client and branch empty for a company-wide location.">
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" @click="add">Add location</button>
        </PageHeader>

        <p class="mb-4 rounded-xl bg-sky-50 px-4 py-3 text-xs text-sky-800">
            A browser can only <b>verify</b> location, not prove it is genuine. The proposed 10 m radius is tight for phone GPS, so test it on site and widen it if people are wrongly refused. Every punch stores its distance, so you can review the real numbers here.
        </p>

        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Applies to</th><th class="px-4 py-3">Coordinates</th><th class="px-4 py-3">Radius</th><th class="px-4 py-3">Max GPS error</th><th class="px-4 py-3">Active</th><th class="px-4 py-3" /></tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="l in locations" :key="l.id">
                        <td class="px-4 py-2.5 font-medium text-gray-800">{{ l.name }}</td>
                        <td class="px-4 py-2.5 text-gray-600">{{ l.client ?? l.branch ?? "Everyone" }}</td>
                        <td class="px-4 py-2.5 font-mono text-xs text-gray-500">{{ l.latitude }}, {{ l.longitude }}</td>
                        <td class="px-4 py-2.5">{{ l.radius_meters }} m</td>
                        <td class="px-4 py-2.5">{{ l.max_accuracy_meters }} m</td>
                        <td class="px-4 py-2.5">{{ l.is_active ? "Yes" : "No" }}</td>
                        <td class="px-4 py-2.5 text-right text-xs">
                            <button class="mr-3 font-medium text-indigo-600 hover:underline" @click="edit(l)">Edit</button>
                            <button class="font-medium text-red-600 hover:underline" @click="remove(l)">Remove</button>
                        </td>
                    </tr>
                    <tr v-if="!locations.length"><td colspan="7" class="px-4 py-10 text-center text-gray-500">No locations yet. Employees cannot punch with a geofence until you add one.</td></tr>
                </tbody>
            </table>
        </div>

        <Modal :show="show" :title="editing ? 'Edit location' : 'Add location'" @close="show = false">
            <form class="space-y-4 p-6" @submit.prevent="save">
                <div>
                    <label class="text-sm font-medium text-gray-700">Name</label>
                    <input v-model="form.name" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" placeholder="Head office" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Latitude</label>
                        <input v-model="form.latitude" type="number" step="any" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                        <p v-if="form.errors.latitude" class="mt-1 text-xs text-red-600">{{ form.errors.latitude }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Longitude</label>
                        <input v-model="form.longitude" type="number" step="any" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                        <p v-if="form.errors.longitude" class="mt-1 text-xs text-red-600">{{ form.errors.longitude }}</p>
                    </div>
                </div>
                <button type="button" class="text-sm font-medium text-indigo-600 hover:underline" :disabled="locating" @click="useMyLocation">
                    {{ locating ? "Reading location…" : "📍 Use my current location (stand at the office entrance)" }}
                </button>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Allowed radius (m)</label>
                        <input v-model="form.radius_meters" type="number" min="5" max="5000" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Max GPS error (m)</label>
                        <input v-model="form.max_accuracy_meters" type="number" min="5" max="500" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Client</label>
                        <select v-model="form.client_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="">Any</option><option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option></select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Branch</label>
                        <select v-model="form.branch_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="">Any</option><option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option></select>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.is_active" type="checkbox" class="rounded border-gray-300" /> Active</label>
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600" @click="show = false">Cancel</button>
                    <button type="submit" :disabled="form.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Save</button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
