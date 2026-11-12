<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AttendancePunch;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PunchController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get today's punches
        $todayPunches = AttendancePunch::where('user_id', $user->id)
            ->whereDate('punch_time', today())
            ->orderBy('punch_time', 'asc')
            ->get();

        // Determine current status
        $lastPunch = $todayPunches->last();
        $currentStatus = $lastPunch?->punch_type ?? null;
        $canPunchIn = !in_array($currentStatus, ['in', 'break_start']);
        $canPunchOut = $currentStatus === 'in';
        $canBreakStart = $currentStatus === 'in';
        $canBreakEnd = $currentStatus === 'break_start';

        return Inertia::render('Employee/Punch', [
            'user' => $user,
            'todayPunches' => $todayPunches,
            'currentStatus' => $currentStatus,
            'canPunchIn' => $canPunchIn,
            'canPunchOut' => $canPunchOut,
            'canBreakStart' => $canBreakStart,
            'canBreakEnd' => $canBreakEnd,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'punch_type' => 'required|in:in,out,break_start,break_end',
            'source' => 'nullable|in:portal,machine,manual,desktop_agent',
            'device_id' => 'nullable|string|max:100',
            'geolocation' => 'nullable|array',
            'ip_address' => 'nullable|string|max:45',
            'notes' => 'nullable|string|max:1000',
        ]);

        AttendancePunch::create([
            'user_id' => $user->id,
            'client_id' => $user->client_id ?? $user->branch_id,
            'punch_time' => now(),
            'punch_type' => $data['punch_type'],
            'source' => $data['source'] ?? 'portal',
            'device_id' => $data['device_id'] ?? null,
            'geolocation' => $data['geolocation'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('employee.punch')
            ->with('success', 'Punch recorded successfully.');
    }
}
