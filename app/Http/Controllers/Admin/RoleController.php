<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Roles that are part of the application's core workflow.
     *
     * These roles may have their permissions edited, but they cannot be
     * renamed or deleted through the admin interface.
     */
    private const SYSTEM_ROLES = [
        'admin',
        'hr',
        'interviewer',
        'employee',
    ];

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'roles.view');

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->withCount([
                'permissions',
                'users',
            ])
            ->orderByRaw(
                "
                CASE name
                    WHEN 'admin' THEN 1
                    WHEN 'hr' THEN 2
                    WHEN 'interviewer' THEN 3
                    WHEN 'employee' THEN 4
                    ELSE 5
                END
                "
            )
            ->orderBy('name')
            ->paginate(20)
            ->through(function (Role $role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'permissions_count' => $role->permissions_count,
                    'users_count' => $role->users_count,
                    'is_system' => in_array(
                        $role->name,
                        self::SYSTEM_ROLES,
                        true
                    ),
                    'can_delete' => ! in_array(
                        $role->name,
                        self::SYSTEM_ROLES,
                        true
                    ) && $role->users_count === 0,
                    'created_at' => $role->created_at?->toDateTimeString(),
                ];
            })
            ->withQueryString();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            'systemRoles' => self::SYSTEM_ROLES,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizePermission($request, 'roles.create');

        return Inertia::render('Admin/Roles/Create', [
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions(
            $request->validated('permissions', [])
        );

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Role {$role->name} created successfully.");
    }

    public function edit(Request $request, Role $role): Response
    {
        $this->authorizePermission($request, 'roles.update');

        $this->ensureWebGuard($role);

        return Inertia::render('Admin/Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'is_system' => $this->isSystemRole($role),
                'permissions' => $role
                    ->permissions()
                    ->pluck('name')
                    ->values(),
            ],

            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function update(
        UpdateRoleRequest $request,
        Role $role
    ): RedirectResponse {
        $this->ensureWebGuard($role);

        $validated = $request->validated();

        /*
         * System roles may not be renamed because role names are used
         * throughout policies, middleware, dashboards, and navigation.
         */
        if ($this->isSystemRole($role)) {
            $validated['name'] = $role->name;
        }

        $role->update([
            'name' => $validated['name'],
        ]);

        /*
         * Prevent administrators from accidentally removing role-management
         * access from the core admin role.
         */
        $permissions = collect($validated['permissions'] ?? []);

        if ($role->name === 'admin') {
            $permissions = $permissions
                ->merge([
                    'roles.view',
                    'roles.create',
                    'roles.update',
                    'roles.delete',
                ])
                ->unique()
                ->values();
        }

        $role->syncPermissions($permissions->all());

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Role {$role->name} updated successfully.");
    }

    public function destroy(
        Request $request,
        Role $role
    ): RedirectResponse {
        $this->authorizePermission($request, 'roles.delete');

        $this->ensureWebGuard($role);

        if ($this->isSystemRole($role)) {
            return back()->with(
                'error',
                'System roles cannot be deleted.'
            );
        }

        if ($role->users()->exists()) {
            return back()->with(
                'error',
                'This role is assigned to users and cannot be deleted.'
            );
        }

        $roleName = $role->name;

        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Role {$roleName} deleted successfully.");
    }

    private function permissionGroups(): array
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->groupBy(function (Permission $permission) {
                return $this->permissionGroupName($permission->name);
            })
            ->map(function (Collection $permissions, string $group) {
                return [
                    'name' => $group,
                    'label' => str($group)
                        ->replace(['-', '_'], ' ')
                        ->title()
                        ->toString(),

                    'permissions' => $permissions
                        ->map(function (Permission $permission) {
                            return [
                                'id' => $permission->id,
                                'name' => $permission->name,
                                'label' => $this->permissionLabel(
                                    $permission->name
                                ),
                            ];
                        })
                        ->values(),
                ];
            })
            ->sortBy('label')
            ->values()
            ->all();
    }

    private function permissionGroupName(string $permission): string
    {
        /*
         * Supports permission formats such as:
         *
         * candidates.view
         * interviews.view-assigned
         * dashboard.hr.view
         */
        return str($permission)
            ->before('.')
            ->toString();
    }

    private function permissionLabel(string $permission): string
    {
        $action = str($permission)
            ->after('.')
            ->replace(['.', '-', '_'], ' ')
            ->title();

        return $action->toString();
    }

    private function isSystemRole(Role $role): bool
    {
        return in_array(
            $role->name,
            self::SYSTEM_ROLES,
            true
        );
    }

    private function ensureWebGuard(Role $role): void
    {
        abort_unless(
            $role->guard_name === 'web',
            404
        );
    }

    private function authorizePermission(
        Request $request,
        string $permission
    ): void {
        abort_unless(
            $request->user()?->can($permission),
            403,
            'You are not authorized to perform this action.'
        );
    }
}