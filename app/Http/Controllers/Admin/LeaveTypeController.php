<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveTypeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/LeaveTypes/Index', [
            'types' => LeaveType::query()->with('client:id,name')->withCount('policies')->orderBy('name')->get()
                ->map(fn ($t) => [
                    'id' => $t->id, 'name' => $t->name, 'code' => $t->code, 'description' => $t->description,
                    'is_paid' => $t->is_paid, 'is_active' => $t->is_active, 'client_id' => $t->client_id,
                    'client' => $t->client?->name ?? 'All clients', 'policies' => $t->policies_count,
                ]),
            'clients' => Client::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $type = LeaveType::create($data);
        AuditLog::record('leave_type.created', $type, [], $data, $request->user()->id);

        return back()->with('success', 'Leave type added. Add a leave policy to make it available to employees.');
    }

    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $data = $this->validated($request, $leaveType->id);
        $old = $leaveType->only(['name', 'code', 'is_paid', 'is_active']);
        $leaveType->update($data);
        AuditLog::record('leave_type.updated', $leaveType, $old, $data, $request->user()->id);

        return back()->with('success', 'Leave type updated.');
    }

    public function destroy(Request $request, LeaveType $leaveType): RedirectResponse
    {
        if (LeaveApplication::query()->where('leave_type_id', $leaveType->id)->exists()) {
            return back()->withErrors(['leave_type' => 'This type has leave requests. Mark it inactive instead of deleting it.']);
        }

        AuditLog::record('leave_type.deleted', $leaveType, $leaveType->only(['name', 'code']), [], $request->user()->id);
        $leaveType->delete();

        return back()->with('success', 'Leave type deleted.');
    }

    private function validated(Request $request, ?int $ignore = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'code' => ['required', 'string', 'max:12', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:255'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'is_paid' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $data['code'] = strtoupper($data['code']);
        $data['is_paid'] = $data['is_paid'] ?? true;
        $data['is_active'] = $data['is_active'] ?? true;

        $dupe = LeaveType::query()->where('code', $data['code'])->where('client_id', $data['client_id'] ?? null)
            ->when($ignore, fn ($q) => $q->where('id', '!=', $ignore))->exists();

        abort_if($dupe, 422, 'That code is already used for this client.');

        return $data;
    }
}
