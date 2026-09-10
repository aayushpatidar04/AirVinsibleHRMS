<script setup>
// Admin/Candidates/Index.vue
import AppLayout from "@/Layouts/AppLayout.vue";
import StatusBadge from "@/Components/Common/StatusBadge.vue";
import Pagination from "@/Components/Common/Pagination.vue";
import { Link, router } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

const props = defineProps({
    candidates: Object,
    branches: Array,
    filters: Object,
    statusOptions: Object,
    finalStatusOptions: Object,
    profileOptions: Object,
    applicantTypeOptions: Object,
});

const selectedCandidates = ref([]);

const search = ref(props.filters?.search ?? "");
const branchId = ref(props.filters?.branch_id ?? "");
const status = ref(props.filters?.status ?? "");
const finalStatus = ref(props.filters?.final_status ?? "");
const profile = ref(props.filters?.profile ?? "");
const applicantType = ref(props.filters?.applicant_type ?? "");

watch([search, branchId, status, finalStatus, profile, applicantType], () => {
    router.get(
        route("recruitment.candidates.index"),
        {
            search: search.value,
            branch_id: branchId.value,
            status: status.value,
            final_status: finalStatus.value,
            profile: profile.value,
            applicant_type: applicantType.value,
        },
        { preserveState: true, replace: true },
    );
});

const canCompare = computed(() => {
    return (
        selectedCandidates.value.length >= 2 &&
        selectedCandidates.value.length <= 5
    );
});

const compareCandidates = () => {
    if (!canCompare.value) {
        return;
    }

    router.get(route("recruitment.candidates.compare"), {
        candidates: selectedCandidates.value,
    });
};
</script>

<template>
    <AppLayout>
        <div class="space-y-5">
            <h1 class="text-2xl font-bold text-gray-900">Candidates</h1>

            <!-- Filters -->
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3"
            >
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search name, email, phone..."
                    class="input-field w-56"
                />
                <select v-model="branchId" class="input-field w-40">
                    <option value="">All Branches</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">
                        {{ b.name }}
                    </option>
                </select>
                <select v-model="profile" class="input-field w-44">
                    <option value="">All Profiles</option>
                    <option
                        v-for="(l, k) in profileOptions"
                        :key="k"
                        :value="k"
                    >
                        {{ l }}
                    </option>
                </select>
                <select v-model="status" class="input-field w-44">
                    <option value="">All Status</option>
                    <option v-for="(l, k) in statusOptions" :key="k" :value="k">
                        {{ l }}
                    </option>
                </select>
                <select v-model="finalStatus" class="input-field w-40">
                    <option value="">All Final</option>
                    <option
                        v-for="(l, k) in finalStatusOptions"
                        :key="k"
                        :value="k"
                    >
                        {{ l }}
                    </option>
                </select>
                <select v-model="applicantType" class="input-field w-56">
                    <option value="">All Applicant Types</option>
                    <option
                        v-for="(l, k) in applicantTypeOptions"
                        :key="k"
                        :value="k"
                    >
                        {{ l }}
                    </option>
                </select>
                <button
                    type="button"
                    class="btn-primary"
                    :disabled="!canCompare"
                    @click="compareCandidates"
                >
                    Compare Candidates
                    <span v-if="selectedCandidates.length">
                        ({{ selectedCandidates.length }})
                    </span>
                </button>
            </div>

            <!-- Table -->
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
            >
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="w-12 px-5 py-3"></th>
                            <th
                                class="px-5 py-3 text-left font-medium text-gray-500"
                            >
                                Candidate
                            </th>
                            <th
                                class="px-5 py-3 text-left font-medium text-gray-500"
                            >
                                Position / Profile
                            </th>
                            <th
                                class="px-5 py-3 text-left font-medium text-gray-500"
                            >
                                Branch
                            </th>
                            <th
                                class="px-5 py-3 text-left font-medium text-gray-500"
                            >
                                Status
                            </th>
                            <th
                                class="px-5 py-3 text-left font-medium text-gray-500"
                            >
                                Final
                            </th>
                            <th
                                class="px-5 py-3 text-left font-medium text-gray-500"
                            >
                                Round
                            </th>
                            <th
                                class="px-5 py-3 text-left font-medium text-gray-500"
                            >
                                Registered
                            </th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr
                            v-for="c in candidates.data"
                            :key="c.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-5 py-4">
                                <input
                                    v-model="selectedCandidates"
                                    :value="c.id"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600"
                                    :disabled="
                                        selectedCandidates.length >= 5 &&
                                        !selectedCandidates.includes(c.id)
                                    "
                                />
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-gray-900">
                                    {{ c.first_name }} {{ c.last_name }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ c.email }}
                                </p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-gray-700">
                                    {{ c.position_applied }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ c.profile_category }}
                                </p>
                            </td>
                            <td class="px-5 py-4 text-gray-600">
                                {{ c.branch?.name }}
                            </td>
                            <td class="px-5 py-4">
                                <StatusBadge :status="c.current_status" />
                            </td>
                            <td class="px-5 py-4">
                                <StatusBadge
                                    :status="c.final_status"
                                    type="final"
                                />
                            </td>
                            <td class="px-5 py-4 text-gray-500 text-xs">
                                {{ c.current_round?.name ?? "—" }}
                            </td>
                            <td class="px-5 py-4 text-gray-400 text-xs">
                                {{ c.registration_date?.split("T")[0] }}
                            </td>
                            <td class="px-5 py-4">
                                <Link
                                    :href="
                                        route(
                                            'recruitment.candidates.show',
                                            c.id,
                                        )
                                    "
                                    class="text-indigo-600 hover:underline text-xs"
                                    >View</Link
                                >
                            </td>
                        </tr>
                        <tr v-if="!candidates.data?.length">
                            <td
                                colspan="9"
                                class="px-5 py-10 text-center text-gray-400"
                            >
                                No candidates found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="candidates.links" />
        </div>
    </AppLayout>
</template>
