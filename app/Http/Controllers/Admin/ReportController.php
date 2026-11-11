<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Candidate;
use App\Models\Client;
use App\Models\LeaveApplication;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $clientId = $request->filled('client_id') ? $request->client_id : null;

        $clientQuery = Client::query();
        if ($clientId) {
            $clientQuery->where('id', $clientId);
        }
        $clients = $clientQuery->orderBy('name')->get();

        // Candidate stats
        $candidateQuery = Candidate::query();
        if ($clientId) {
            $candidateQuery->where('branch_id', function ($q) use ($clientId) {
                $q->select('id')->from('branches')->where('client_id', $clientId);
            });
        }
        $totalCandidates = $candidateQuery->count();
        $selectedCandidates = $candidateQuery->where('final_status', 'selected')->count();
        $convertedCandidates = $candidateQuery->whereNotNull('user_id')->count();

        // Attendance stats
        $attendanceQuery = AttendanceRecord::query();
        if ($clientId) {
            $attendanceQuery->where('client_id', $clientId);
        }
        $presentCount = $attendanceQuery->where('status', 'present')->count();
        $absentCount = $attendanceQuery->where('status', 'absent')->count();
        $lateCount = $attendanceQuery->where('status', 'late')->count();

        // Leave stats
        $leaveQuery = LeaveApplication::query();
        if ($clientId) {
            $leaveQuery->where('client_id', $clientId);
        }
        $pendingLeave = $leaveQuery->where('status', 'pending')->count();
        $approvedLeave = $leaveQuery->where('status', 'approved')->count();
        $rejectedLeave = $leaveQuery->where('status', 'rejected')->count();

        // Payroll stats
        $payslipQuery = Payslip::query();
        if ($clientId) {
            $payslipQuery->where('client_id', $clientId);
        }
        $totalPayslips = $payslipQuery->count();
        $publishedPayslips = $payslipQuery->where('status', 'published')->count();
        $totalPayrollAmount = $payslipQuery->where('status', 'published')->sum('net_pay');

        // Employee stats
        $employeeQuery = User::query()->where('employment_status', 'active');
        if ($clientId) {
            $employeeQuery->where('client_id', $clientId);
        }
        $totalEmployees = $employeeQuery->count();

        return Inertia::render('Admin/Reports/Index', [
            'clients' => $clients,
            'selectedClientId' => $clientId,
            'stats' => [
                'total_candidates' => $totalCandidates,
                'selected_candidates' => $selectedCandidates,
                'converted_candidates' => $convertedCandidates,
                'total_employees' => $totalEmployees,
                'attendance_present' => $presentCount,
                'attendance_absent' => $absentCount,
                'attendance_late' => $lateCount,
                'leave_pending' => $pendingLeave,
                'leave_approved' => $approvedLeave,
                'leave_rejected' => $rejectedLeave,
                'total_payslips' => $totalPayslips,
                'published_payslips' => $publishedPayslips,
                'total_payroll_amount' => $totalPayrollAmount,
            ],
        ]);
    }
}
