<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::withCount('candidates', 'users')
            ->when($request->search, fn ($q, $s) =>
                $q->where('name', 'like', "%$s%")
                  ->orWhere('city', 'like', "%$s%")
                  ->orWhere('code', 'like', "%$s%")
            )
            ->when($request->status === 'active',   fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Branches/Index', [
            'branches' => $branches,
            'filters'  => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Branches/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:10|alpha_num|unique:branches,code',
            'address'     => 'required|string|max:500',
            'city'        => 'required|string|max:100',
            'state'       => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country'     => 'required|string|max:100',
            'phone'       => 'required|string|max:20|unique:branches,phone',
            'email'       => 'required|email|unique:branches,email',
        ]);

        $branch = Branch::create($data);

        return redirect()
            ->route('admin.branches.show', $branch)
            ->with('success', 'Branch created successfully.');
    }

    public function show(Branch $branch)
    {
        return Inertia::render('Admin/Branches/Show', [
            'branch' => $branch,
            'stats'  => $branch->getStats(),
            'interviewers' => User::role('interviewer')
                ->fromBranch($branch->id)
                ->withCount([
                    'interviewProgress as pending_count' => fn ($q) => $q->where('status', 'pending'),
                ])
                ->get()
                ->map(fn ($u) => [
                    'id'      => $u->id,
                    'name'    => $u->full_name,
                    'email'   => $u->email,
                    'phone'   => $u->phone,
                    'pending' => $u->pending_count,
                    'active'  => $u->is_active,
                ]),
            'recent_candidates' => $branch->candidates()
                ->with('currentRound')
                ->latest('registration_date')
                ->limit(5)
                ->get()
                ->map(fn ($c) => [
                    'id'       => $c->id,
                    'name'     => $c->full_name,
                    'position' => $c->position_applied,
                    'status'   => $c->current_status,
                    'profile'  => $c->getProfileCategoryLabel(),
                ]),
        ]);
    }

    public function edit(Branch $branch)
    {
        return Inertia::render('Admin/Branches/Edit', [
            'branch' => $branch,
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => "required|string|max:10|alpha_num|unique:branches,code,{$branch->id}",
            'address'     => 'required|string|max:500',
            'city'        => 'required|string|max:100',
            'state'       => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country'     => 'required|string|max:100',
            'phone'       => "required|string|max:20|unique:branches,phone,{$branch->id}",
            'email'       => "required|email|unique:branches,email,{$branch->id}",
            'is_active'   => 'boolean',
        ]);

        $branch->update($data);

        return redirect()
            ->route('admin.branches.show', $branch)
            ->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch)
    {
        if ($branch->candidates()->exists()) {
            return back()->with('error', 'Cannot delete a branch that has candidates.');
        }

        $branch->delete();

        return redirect()
            ->route('admin.branches.index')
            ->with('success', 'Branch deleted.');
    }
}