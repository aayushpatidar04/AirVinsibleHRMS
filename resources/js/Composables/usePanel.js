import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

const validPanels = ["admin", "hr", "interviewer", "employee"];

export function usePanel() {
    const page = usePage();

    const currentPath = computed(() => {
        return page.url?.split("?")[0] ?? "";
    });

    const primaryRole = computed(() => {
        const role = page.props.auth?.primary_role;

        return validPanels.includes(role)
            ? role
            : "employee";
    });

    const resolvedPanel = computed(() => primaryRole.value);

    return {
        resolvedPanel,
        primaryRole,
        currentPath,
    };
}