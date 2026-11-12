<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AttendancePunch;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless(
            $user->can('dashboard.employee.view'),
            403
        );

        // Get today's punches
        $todayPunches = AttendancePunch::where('user_id', $user->id)
            ->whereDate('punch_time', today())
            ->orderBy('punch_time', 'asc')
            ->get();

        // Determine current status (last punch type)
        $currentStatus = $todayPunches->last()?->punch_type ?? null;

        // Get recent punches (last 7 days)
        $recentPunches = AttendancePunch::where('user_id', $user->id)
            ->whereDate('punch_time', '>=', today()->subDays(6))
            ->orderBy('punch_time', 'desc')
            ->take(20)
            ->get();

        return Inertia::render('Employee/Dashboard', [
            'user' => $user,
            'todayPunches' => $todayPunches,
            'recentPunches' => $recentPunches,
            'currentStatus' => $currentStatus,
        ]);
    }
}
