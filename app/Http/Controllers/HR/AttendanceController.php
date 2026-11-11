<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendancePunch;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = AttendanceRecord::query()
            ->with(['user', 'client', 'shift']);

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('attendance_date', [$request->start_date, $request->end_date]);
        }

        $attendanceRecords = $query->orderByDesc('attendance_date')
            ->paginate(15)
            ->withQueryString();

        $clients = Client::active()->orderBy('name')->get();
        $users = User::active()->orderBy('first_name')->get();

        return Inertia::render('Hr/Attendance/Index', [
            'attendanceRecords' => $attendanceRecords,
            'clients' => $clients,
            'users' => $users,
            'filters' => $request->only(['client_id', 'user_id', 'status', 'start_date', 'end_date']),
        ]);
    }

    public function show(AttendanceRecord $attendanceRecord)
    {
        $attendanceRecord->load(['user', 'client', 'shift', 'punches']);

        return Inertia::render('Hr/Attendance/Show', [
            'attendanceRecord' => $attendanceRecord,
        ]);
    }

    public function punch(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'punch_type' => 'required|in:in,out,break_start,break_end',
            'source' => 'required|in:portal,machine,manual,desktop_agent',
            'device_id' => 'nullable|string|max:100',
            'geolocation' => 'nullable|array',
            'ip_address' => 'nullable|string|max:45',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = User::findOrFail($data['user_id']);
        $clientId = $user->branch_id; // Using branch_id as client reference for now

        $punch = AttendancePunch::create([
            'user_id' => $data['user_id'],
            'client_id' => $clientId,
            'punch_time' => now(),
            'punch_type' => $data['punch_type'],
            'source' => $data['source'],
            'device_id' => $data['device_id'] ?? null,
            'geolocation' => $data['geolocation'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'punch' => $punch,
            'message' => 'Punch recorded successfully.',
        ]);
    }

    public function corrections(Request $request)
    {
        $query = \App\Models\AttendanceCorrection::query()
            ->with(['user', 'client', 'attendanceRecord', 'requestedBy', 'approvedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $corrections = $query->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $clients = Client::active()->orderBy('name')->get();

        return Inertia::render('Hr/Attendance/Corrections', [
            'corrections' => $corrections,
            'clients' => $clients,
            'filters' => $request->only(['status', 'client_id']),
        ]);
    }

    public function approveCorrection(Request $request, \App\Models\AttendanceCorrection $correction)
    {
        $data = $request->validate([
            'approved' => 'required|boolean',
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        if ($data['approved']) {
            $correction->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Apply correction to attendance record
            if ($correction->attendance_record_id && $correction->corrected_punch_in) {
                $record = $correction->attendanceRecord;
                $record->update([
                    'first_punch_in' => $correction->corrected_punch_in,
                    'last_punch_out' => $correction->corrected_punch_out ?? $record->last_punch_out,
                    'notes' => ($record->notes ?? '') . ' | Corrected: ' . $correction->reason,
                ]);
            }
        } else {
            $correction->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejection_reason' => $data['rejection_reason'],
            ]);
        }

        return back()->with('success', 'Correction ' . ($data['approved'] ? 'approved' : 'rejected') . '.');
    }
}
