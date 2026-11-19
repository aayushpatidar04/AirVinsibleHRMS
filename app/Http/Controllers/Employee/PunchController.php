<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AttendancePunch;
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

        // Determine current status from last punch
        $lastPunch = $todayPunches->last();
        $currentStatus = $lastPunch?->punch_type ?? null;

        // Valid state transitions:
        //   null       -> in            (first punch of day)
        //   in         -> break_start   (start break)
        //   break_start -> break_end    (end break)
        //   break_end   -> break_start  (another break)
        //   break_end   -> out          (end of day)
        //   out         -> in           (next day)
        $canPunchIn = is_null($currentStatus)
            || in_array($currentStatus, ['out', 'break_end']);
        $canPunchOut = in_array($currentStatus, ['in', 'break_end']);
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

        // Get today's last punch to validate state transition
        $todayPunches = AttendancePunch::where('user_id', $user->id)
            ->whereDate('punch_time', today())
            ->orderBy('punch_time', 'asc')
            ->get();

        $lastPunch = $todayPunches->last();
        $currentStatus = $lastPunch?->punch_type ?? null;

        $punchType = $data['punch_type'];

        // Validate state transitions
        switch ($punchType) {
            case 'in':
                if (!is_null($currentStatus) && !in_array($currentStatus, ['out', 'break_end'])) {
                    return back()->withErrors([
                        'punch_type' => 'You cannot punch in again. You are already punched in or on break.',
                    ])->withInput();
                }
                break;

            case 'out':
                if (!in_array($currentStatus, ['in', 'break_end'])) {
                    return back()->withErrors([
                        'punch_type' => 'You cannot punch out. You must punch in first, or end your break before punching out.',
                    ])->withInput();
                }
                break;

            case 'break_start':
                if ($currentStatus !== 'in') {
                    return back()->withErrors([
                        'punch_type' => 'You cannot start a break. You must be punched in first.',
                    ])->withInput();
                }
                break;

            case 'break_end':
                if ($currentStatus !== 'break_start') {
                    return back()->withErrors([
                        'punch_type' => 'You cannot end a break. You must be on break first.',
                    ])->withInput();
                }
                break;
        }

        AttendancePunch::create([
            'user_id' => $user->id,
            'client_id' => $user->client_id ?? $user->branch_id,
            'punch_time' => now(),
            'punch_type' => $punchType,
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
