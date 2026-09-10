<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class InterviewerController extends Controller
{
    public function index(Request $request)
    {
        $interviewers = User::query()
            ->active()
            ->canInterview()
            ->with('branch')
            ->withCount([
                'interviewProgress as total_interviews',
                'interviewProgress as pending_count' => fn ($q) => $q->where('status', 'pending'),
                'interviewProgress as completed_count' => fn ($q) => $q->where('status', 'completed'),
            ])
            ->when($request->search, fn ($q, $s) =>
                $q->where('first_name', 'like', "%$s%")
                  ->orWhere('last_name', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%")
                  ->orWhere('employee_id', 'like', "%$s%")
            )
            ->when($request->branch_id, fn ($q, $b) => $q->where('branch_id', $b))
            ->when($request->status === 'active',   fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Interviewers/Index', [
            'interviewers' => $interviewers,
            'branches'     => Branch::active()->get(['id', 'name']),
            'filters'      => $request->only(['search', 'branch_id', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Interviewers/Create', [
            'branches' => Branch::active()->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'  => 'required|string|max:255',
            'last_name'   => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'phone'       => 'required|string|max:20|unique:users,phone',
            'employee_id' => 'nullable|string|max:50|unique:users,employee_id',
            'branch_id'   => 'required|exists:branches,id',
            'password'    => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'first_name'  => $data['first_name'],
            'last_name'   => $data['last_name'],
            'email'       => $data['email'],
            'phone'       => $data['phone'],
            'employee_id' => $data['employee_id'] ?? null,
            'branch_id'   => $data['branch_id'],
            'password'    => Hash::make($data['password']),
            'is_active'   => true,
        ]);

        $user->assignRole('interviewer');

        return redirect()
            ->route('admin.interviewers.index')
            ->with('success', 'Interviewer created successfully.');
    }

    public function show(User $interviewer)
    {
        $interviewer->load('branch');

        return Inertia::render('Admin/Interviewers/Show', [
            'interviewer' => [
                'id'          => $interviewer->id,
                'name'        => $interviewer->full_name,
                'email'       => $interviewer->email,
                'phone'       => $interviewer->phone,
                'employee_id' => $interviewer->employee_id,
                'branch'      => $interviewer->branch?->name,
                'branch_id'   => $interviewer->branch_id,
                'is_active'   => $interviewer->is_active,
                'created_at'  => $interviewer->created_at->format('d M Y'),
            ],
            'stats' => [
                'total'     => $interviewer->interviewProgress()->count(),
                'pending'   => $interviewer->interviewProgress()->where('status', 'pending')->count(),
                'completed' => $interviewer->interviewProgress()->where('status', 'completed')->count(),
                'rejected'  => $interviewer->interviewProgress()->where('status', 'rejected')->count(),
            ],
            'recent_interviews' => $interviewer->interviewProgress()
                ->with('candidate', 'round')
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($p) => [
                    'id'        => $p->id,
                    'candidate' => $p->candidate->full_name,
                    'position'  => $p->candidate->position_applied,
                    'round'     => $p->round->name,
                    'status'    => $p->status,
                    'rating'    => $p->overall_rating,
                    'date'      => $p->start_date?->format('d M Y'),
                ]),
        ]);
    }

    public function edit(User $interviewer)
    {
        return Inertia::render('Admin/Interviewers/Edit', [
            'interviewer' => $interviewer,
            'branches'    => Branch::active()->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, User $interviewer)
    {
        $data = $request->validate([
            'first_name'  => 'required|string|max:255',
            'last_name'   => 'required|string|max:255',
            'email'       => "required|email|unique:users,email,{$interviewer->id}",
            'phone'       => "required|string|max:20|unique:users,phone,{$interviewer->id}",
            'employee_id' => "nullable|string|max:50|unique:users,employee_id,{$interviewer->id}",
            'branch_id'   => 'required|exists:branches,id',
            'is_active'   => 'boolean',
        ]);

        $interviewer->update($data);

        return redirect()
            ->route('admin.interviewers.show', $interviewer)
            ->with('success', 'Interviewer updated successfully.');
    }

    public function destroy(User $interviewer)
    {
        if ($interviewer->interviewProgress()->whereIn('status', ['pending', 'in_progress'])->exists()) {
            return back()->with('error', 'Cannot delete interviewer with active interviews.');
        }

        $interviewer->delete();

        return redirect()
            ->route('admin.interviewers.index')
            ->with('success', 'Interviewer deleted.');
    }

    public function resetPassword(Request $request, User $interviewer)
    {
        $data = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $interviewer->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Password reset successfully.');
    }
}