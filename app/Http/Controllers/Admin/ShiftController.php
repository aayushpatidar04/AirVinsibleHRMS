<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Shift;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $query = Shift::query()
            ->with('client');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $shifts = $query->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $clients = Client::active()->orderBy('name')->get();

        return Inertia::render('Admin/Shifts/Index', [
            'shifts' => $shifts,
            'clients' => $clients,
            'filters' => $request->only(['search', 'client_id', 'is_active']),
        ]);
    }

    public function create()
    {
        $clients = Client::active()->orderBy('name')->get();

        return Inertia::render('Admin/Shifts/Create', [
            'clients' => $clients,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:shifts,code',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'break_start_time' => 'nullable|date_format:H:i',
            'break_end_time' => 'nullable|date_format:H:i|after:break_start_time',
            'break_duration_minutes' => 'nullable|integer|min:0',
            'grace_period_minutes' => 'nullable|integer|min:0',
            'is_rotational' => 'boolean',
            'is_overnight' => 'boolean',
            'working_days' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $data['is_rotational'] = $request->boolean('is_rotational');
        $data['is_overnight'] = $request->boolean('is_overnight');
        $data['working_days'] = $data['working_days'] ?? [];
        $data['is_active'] = $request->boolean('is_active');

        $shift = Shift::create($data);

        return redirect()
            ->route('admin.shifts.show', $shift)
            ->with('success', 'Shift created successfully.');
    }

    public function show(Shift $shift)
    {
        $shift->load('client');

        return Inertia::render('Admin/Shifts/Show', [
            'shift' => $shift,
        ]);
    }

    public function edit(Shift $shift)
    {
        $clients = Client::active()->orderBy('name')->get();

        return Inertia::render('Admin/Shifts/Edit', [
            'shift' => $shift,
            'clients' => $clients,
        ]);
    }

    public function update(Request $request, Shift $shift)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:shifts,code,' . $shift->id,
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'break_start_time' => 'nullable|date_format:H:i',
            'break_end_time' => 'nullable|date_format:H:i|after:break_start_time',
            'break_duration_minutes' => 'nullable|integer|min:0',
            'grace_period_minutes' => 'nullable|integer|min:0',
            'is_rotational' => 'boolean',
            'is_overnight' => 'boolean',
            'working_days' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $data['is_rotational'] = $request->boolean('is_rotational');
        $data['is_overnight'] = $request->boolean('is_overnight');
        $data['working_days'] = $data['working_days'] ?? [];
        $data['is_active'] = $request->boolean('is_active');

        $shift->update($data);

        return redirect()
            ->route('admin.shifts.show', $shift)
            ->with('success', 'Shift updated successfully.');
    }

    public function destroy(Shift $shift)
    {
        $shift->delete();

        return redirect()
            ->route('admin.shifts.index')
            ->with('success', 'Shift deleted successfully.');
    }
}
