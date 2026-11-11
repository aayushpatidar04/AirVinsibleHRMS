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
        "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 002-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01",

    forms:
        "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z",

    qr:
        "M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 001 1v2a1 1 0 001 1z",

    calendar:
        "M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z",

    export:
        "M12 3v12m0 0l4-4m-4 4l-4-4M5 17v3h14v-3",

    roles:
        "M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm3-11a3 3 0 100-6 3 3 0 000 6zm2 8h4v-1a5 5 0 00-6.713-4.7",

    clients:
        "M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2",

    attendance:
        "M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z",

    leave:
        "M19 14l-7 7v-4a6 6 0 00-6-6V3m6 11V5a6 6 0 016 6v8m0 0l-7-7",

    payroll:
        "M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z",
};

/**
 * Single master navigation list. Every possible nav item lives here with
 * its required permission. The sidebar component filters by the user's
 * actual permissions, so any role gets exactly the items it's allowed to see.
 */
export const masterNavigation = [
    {
        name: "Dashboard",
        route: "admin.dashboard",
        permission: "dashboard.admin.view",
        icon: icons.dashboard,
        exact: true,
        group: "admin",
    },
    {
        name: "Dashboard",
        route: "hr.dashboard",
        permission: "dashboard.hr.view",
        icon: icons.dashboard,
        exact: true,
        group: "hr",
    },
    {
        name: "Dashboard",
        route: "interviewer.dashboard",
        permission: "dashboard.interviewer.view",
        icon: icons.dashboard,
        exact: true,
        group: "interviewer",
    },
    {
        name: "Dashboard",
        route: "employee.dashboard",
        permission: "dashboard.employee.view",
        icon: icons.dashboard,
        exact: true,
        group: "employee",
    },
    {
        name: "Branches",
        route: "admin.branches.index",
        permission: "branches.view",
        icon: icons.branches,
        group: "admin",
    },
    {
        name: "Roles & Permissions",
        route: "admin.roles.index",
        permission: "roles.view",
        activePaths: ["/admin/roles"],
        icon: icons.roles,
        group: "admin",
    },
    {
        name: "Employees",
        route: "admin.employees.index",
        permission: "employees.view",
        icon: icons.employees,
        group: "admin",
    },
    {
        name: "Interview Rounds",
        route: "admin.rounds.index",
        permission: "interview-rounds.view",
        icon: icons.rounds,
        group: "admin",
    },
    {
        name: "Interview Dashboard",
        route: "recruitment.interviews.dashboard",
        permission: "interviews.view",
        icon: icons.calendar,
        group: ["admin", "hr"],
    },
    {
        name: "Forms",
        route: "admin.forms.index",
        permission: "registration-forms.view",
        icon: icons.forms,
        group: "admin",
    },
    {
        name: "Document Requirements",
        route: "admin.document-requirements.index",
        permission: "documents.view",
        icon: icons.forms,
        group: "admin",
    },
    {
        name: "Clients",
        route: "admin.clients.index",
        permission: "clients.view",
        icon: icons.clients,
        group: "admin",
    },
    {
        name: "Attendance Policies",
        route: "admin.attendance-policies.index",
        permission: "attendance-policies.view",
        icon: icons.attendance,
        group: "admin",
    },
    {
        name: "Leave Policies",
        route: "admin.leave-policies.index",
        permission: "leave-policies.view",
        icon: icons.leave,
        group: "admin",
    },
    {
        name: "Shifts",
        route: "admin.shifts.index",
        permission: "shifts.view",
        icon: icons.calendar,
        group: "admin",
    },
    {
        name: "Export Records",
        route: "records.export.index",
        permission: "documents.export",
        icon: icons.export,
        group: ["admin", "hr"],
    },
    {
        name: "QR Codes",
        route: "admin.qrcodes.index",
        permission: "qr-codes.view",
        icon: icons.qr,
        group: "admin",
    },
    {
        name: "Candidates",
        route: "recruitment.candidates.index",
        permission: "candidates.view",
        icon: icons.candidates,
        group: ["admin", "hr"],
    },
    {
        name: "My Interviews",
        route: "interviewer.candidates.index",
        permission: "interviews.view-assigned",
        icon: icons.rounds,
        group: ["admin", "hr", "interviewer"],
    },
    {
        name: "Attendance",
        route: "hr.attendance.index",
        permission: "attendance.view",
        icon: icons.attendance,
        group: "hr",
    },
    {
        name: "Leave",
        route: "hr.leave.index",
        permission: "leave.view",
        icon: icons.leave,
        group: "hr",
    },
    {
        name: "Payroll",
        route: "hr.payroll.index",
        permission: "payroll.view",
        icon: icons.payroll,
        group: "hr",
    },
];

/**
 * Role panel configuration — only styling + dashboard route.
 * Navigation is now driven by masterNavigation + user permissions.
 */
export const panelConfigurations = {
    admin: {
        title: "Admin Panel",
        roleLabel: "Administrator",
        dashboardRoute: "admin.dashboard",
        colors: {
            sidebar: "bg-white",
            sidebarBorder: "border-indigo-100",
            mutedText: "text-ink-mute",
            navText: "text-ink-soft",
            headerText: "text-indigo-700",
            linkText: "text-indigo-600",
            activeItem: "bg-indigo-50 text-indigo-700",
            hoverItem: "hover:bg-paper-warm hover:text-ink",
            accentBar: "bg-indigo-600",
            avatar: "bg-indigo-600 text-white",
        },
    },

    hr_recruiter: {
        title: "HR Panel",
        roleLabel: "HR Manager",
        dashboardRoute: "hr.dashboard",
        colors: {
            sidebar: "bg-white",
            sidebarBorder: "border-violet-100",
            mutedText: "text-ink-mute",
            navText: "text-ink-soft",
            headerText: "text-violet-700",
            linkText: "text-violet-600",
            activeItem: "bg-violet-50 text-violet-700",
            hoverItem: "hover:bg-paper-warm hover:text-ink",
            accentBar: "bg-violet-600",
            avatar: "bg-violet-600 text-white",
        },
    },

    interviewer: {
        title: "Interviewer Panel",
        roleLabel: "Interviewer",
        dashboardRoute: "interviewer.dashboard",
        colors: {
            sidebar: "bg-white",
            sidebarBorder: "border-teal-100",
            mutedText: "text-ink-mute",
            navText: "text-ink-soft",
            headerText: "text-teal-700",
            linkText: "text-teal-600",
            activeItem: "bg-teal-50 text-teal-700",
            hoverItem: "hover:bg-paper-warm hover:text-ink",
            accentBar: "bg-teal-600",
            avatar: "bg-teal-600 text-white",
        },
    },

    employee: {
        title: "Employee Panel",
        roleLabel: "Employee",
        dashboardRoute: "employee.dashboard",
        colors: {
            sidebar: "bg-white",
            sidebarBorder: "border-slate-200",
            mutedText: "text-ink-mute",
            navText: "text-ink-soft",
            headerText: "text-slate-700",
            linkText: "text-slate-600",
            activeItem: "bg-slate-100 text-slate-800",
            hoverItem: "hover:bg-paper-warm hover:text-ink",
            accentBar: "bg-slate-700",
            avatar: "bg-slate-700 text-white",
        },
    },
};

export { panelConfigurations as default };
