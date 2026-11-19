import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { masterNavigation, panelConfigurations } from "@/Config/navigation";

const validPanels = ["admin", "hr_recruiter", "interviewer", "employee"];

export function usePanel() {
    const page = usePage();

    const currentPath = computed(() => {
        return page.url?.split("?")[0] ?? "";
    });

    const primaryRole = computed(() => {
        const role = page.props.auth?.primary_role;

        return validPanels.includes(role) ? role : "employee";
    });

    const resolvedPanel = computed(() => primaryRole.value);

    const configuration = computed(() => {
        const role = primaryRole.value;
        return panelConfigurations[role] ?? panelConfigurations.employee;
    });

    /**
     * Navigation items filtered by the user's actual permissions.
     * Any role gets exactly the items its permissions allow —
     * no hardcoded role-based navigation lists.
     */
    const navigation = computed(() => {
        const permissions = page.props.auth?.permissions ?? [];
        // A nav item can need one permission, or any one of several (array).
        const hasPermission = (permission) =>
            !permission ||
            (Array.isArray(permission)
                ? permission.some((p) => permissions.includes(p))
                : permissions.includes(permission));
        const routeExists = (name) =>
            typeof route === "function" && route().has(name);

        const allItems = masterNavigation
            .filter((item) => hasPermission(item.permission))
            .filter((item) => routeExists(item.route));

        // Only show ONE dashboard — the one matching the user's primary role
        // (highest priority: admin > hr > interviewer > employee).
        // All other dashboard entries are hidden even if the user has permission.
        const dashboardRoutes = [
            "admin.dashboard",
            "hr.dashboard",
            "interviewer.dashboard",
            "employee.dashboard",
        ];
        const targetDashboard = configuration.value?.dashboardRoute;

        const nonDashboards = allItems.filter(
            (item) => !dashboardRoutes.includes(item.route)
        );
        const activeDashboard = allItems.find(
            (item) =>
                dashboardRoutes.includes(item.route) &&
                item.route === targetDashboard
        );

        return [
            ...(activeDashboard ? [activeDashboard] : []),
            ...nonDashboards,
        ].map((item) => ({ ...item, href: route(item.route) }));
    });

    return {
        resolvedPanel,
        primaryRole,
        configuration,
        navigation,
        currentPath,
        masterNavigation,
        panelConfigurations,
    };
}
