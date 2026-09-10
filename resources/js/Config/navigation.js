const icons = {
    dashboard:
        "M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6",

    branches:
        "M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4",

    employees:
        "M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z",

    candidates:
        "M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z",

    rounds:
        "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01",

    forms:
        "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z",

    qr:
        "M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z",

    calendar:
        "M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z",
    
    roles:
        "M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm3-11a3 3 0 100-6 3 3 0 000 6zm2 8h4v-1a5 5 0 00-6.713-4.7",
};

export const panelConfigurations = {
    admin: {
        title: "Admin Panel",
        roleLabel: "Administrator",
        dashboardRoute: "admin.dashboard",

        colors: {
            sidebar: "bg-indigo-800",
            sidebarBorder: "border-indigo-700/50",
            mutedText: "text-indigo-300",
            navText: "text-indigo-200",
            headerText: "text-indigo-700",
            linkText: "text-indigo-600",
        },

        navigation: [
            {
                name: "Dashboard",
                route: "admin.dashboard",
                permission: "dashboard.admin.view",
                icon: icons.dashboard,
                exact: true,
            },
            {
                name: "Branches",
                route: "admin.branches.index",
                permission: "branches.view",
                icon: icons.branches,
            },
            {
                name: "Roles & Permissions",
                route: "admin.roles.index",
                permission: "roles.view",
                activePaths: [
                    "/admin/roles",
                ],
                icon: icons.roles,
            },
            {
                name: "Employees",
                route: "admin.employees.index",
                permission: "employees.view",
                icon: icons.employees,
            },
            {
                name: "Interview Rounds",
                route: "admin.rounds.index",
                permission: "interview-rounds.view",
                icon: icons.rounds,
            },
            {
                name: "Forms",
                route: "admin.forms.index",
                permission: "registration-forms.view",
                icon: icons.forms,
            },
            {
                name: "QR Codes",
                route: "admin.qrcodes.index",
                permission: "qr-codes.view",
                icon: icons.qr,
            },
            {
                name: "Candidates",
                route: "recruitment.candidates.index",
                permission: "candidates.view",
                icon: icons.candidates,
            },
        ],
    },

    hr: {
        title: "HR Panel",
        roleLabel: "HR Manager",
        dashboardRoute: "hr.dashboard",

        colors: {
            sidebar: "bg-violet-800",
            sidebarBorder: "border-violet-700/50",
            mutedText: "text-violet-300",
            navText: "text-violet-200",
            headerText: "text-violet-700",
            linkText: "text-violet-600",
        },

        navigation: [
            {
                name: "Dashboard",
                route: "hr.dashboard",
                permission: "dashboard.hr.view",
                icon: icons.dashboard,
                exact: true,
            },
            {
                name: "Candidates",
                route: "recruitment.candidates.index",
                permission: "candidates.view",
                icon: icons.candidates,
            },
            {
                name: "My Interviews",
                route: "interviewer.candidates.index",
                permission: "interviews.view-assigned",
                icon: icons.rounds,
            },
        ],
    },

    interviewer: {
        title: "Interviewer Panel",
        roleLabel: "Interviewer",
        dashboardRoute: "interviewer.dashboard",

        colors: {
            sidebar: "bg-teal-700",
            sidebarBorder: "border-teal-800",
            mutedText: "text-teal-300",
            navText: "text-teal-100",
            headerText: "text-teal-700",
            linkText: "text-teal-600",
        },

        navigation: [
            {
                name: "Dashboard",
                route: "interviewer.dashboard",
                permission: "dashboard.interviewer.view",
                icon: icons.dashboard,
                exact: true,
            },
            {
                name: "My Candidates",
                route: "interviewer.candidates.index",
                permission: "candidates.view",
                icon: icons.candidates,
            },
        ],
    },

    employee: {
        title: "Employee Panel",
        roleLabel: "Employee",
        dashboardRoute: "employee.dashboard",

        colors: {
            sidebar: "bg-slate-800",
            sidebarBorder: "border-slate-700",
            mutedText: "text-slate-300",
            navText: "text-slate-200",
            headerText: "text-slate-700",
            linkText: "text-slate-600",
        },

        navigation: [
            {
                name: "Dashboard",
                route: "employee.dashboard",
                permission: "dashboard.employee.view",
                icon: icons.dashboard,
                exact: true,
            },
        ],
    },
};