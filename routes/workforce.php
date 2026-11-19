<?php

use App\Http\Controllers\Employee\AttendanceController as EmployeeAttendance;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboard;
use App\Http\Controllers\Employee\LeaveController as EmployeeLeave;
use App\Http\Controllers\Employee\PayslipController as EmployeePayslips;
use App\Http\Controllers\Employee\PunchController as EmployeePunch;
use App\Http\Controllers\Workforce\AttendanceController;
use App\Http\Controllers\Workforce\AttendanceSetupController;
use App\Http\Controllers\Workforce\LeaveController;
use App\Http\Controllers\Workforce\PayrollController;
use App\Http\Controllers\Workforce\PayrollRunController;
use App\Http\Controllers\Workforce\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Attendance, Leave & Payroll
|--------------------------------------------------------------------------
| Employee self-service lives under /employee. Everything a manager,
| team leader, HR or payroll admin does lives under /workforce and is gated
| by permission; the data each person sees is narrowed by WorkforceScope.
*/

Route::middleware(['auth', 'verified'])->group(function () {

    /* ───────────── Employee self-service ───────────── */

    Route::prefix('employee')->name('employee.')->group(function () {
        Route::get('/dashboard', [EmployeeDashboard::class, 'index'])->name('dashboard');

        Route::get('/punch', [EmployeePunch::class, 'index'])->name('punch')
            ->middleware('permission:attendance.record');
        Route::post('/punch', [EmployeePunch::class, 'store'])->name('punch.store')
            ->middleware(['permission:attendance.record', 'throttle:30,1']);

        Route::get('/attendance', [EmployeeAttendance::class, 'index'])->name('attendance')
            ->middleware('permission:attendance.view-own');
        Route::post('/attendance/corrections', [EmployeeAttendance::class, 'requestCorrection'])->name('attendance.corrections.store')
            ->middleware('permission:attendance.regularize');
        Route::post('/attendance/corrections/{correction}/cancel', [EmployeeAttendance::class, 'cancelCorrection'])->name('attendance.corrections.cancel')
            ->middleware('permission:attendance.regularize');

        Route::get('/leave', [EmployeeLeave::class, 'index'])->name('leave')
            ->middleware('permission:leaves.view-own');
        Route::post('/leave/preview', [EmployeeLeave::class, 'preview'])->name('leave.preview')
            ->middleware('permission:leaves.apply');
        Route::post('/leave', [EmployeeLeave::class, 'store'])->name('leave.store')
            ->middleware('permission:leaves.apply');
        Route::post('/leave/{application}/cancel', [EmployeeLeave::class, 'cancel'])->name('leave.cancel')
            ->middleware('permission:leaves.cancel-own');

        Route::get('/payslips', [EmployeePayslips::class, 'index'])->name('payslips')
            ->middleware('permission:payroll.view-own');
        Route::get('/payslips/{payslip}/download', [EmployeePayslips::class, 'download'])->name('payslips.download')
            ->middleware('permission:payroll.download-payslip');
    });

    /* ───────────── Managers, HR and payroll ───────────── */

    Route::prefix('workforce')->name('workforce.')->group(function () {

        $attendanceView = 'permission:attendance.view-team|attendance.view-branch|attendance.view-all-branches';
        $leaveView = 'permission:leaves.view-team|leaves.view-branch|leaves.view-all-branches|leaves.approve';
        $payrollView = 'permission:payroll.view-branch|payroll.view-all-branches';

        // Attendance
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index')->middleware($attendanceView);
        Route::get('/attendance/exceptions', [AttendanceController::class, 'exceptions'])->name('attendance.exceptions')->middleware($attendanceView);
        Route::get('/attendance/corrections', [AttendanceController::class, 'corrections'])->name('attendance.corrections')->middleware($attendanceView);
        Route::post('/attendance/corrections/{correction}/approve', [AttendanceController::class, 'approveCorrection'])->name('attendance.corrections.approve')->middleware('permission:attendance.approve-regularization');
        Route::post('/attendance/corrections/{correction}/reject', [AttendanceController::class, 'rejectCorrection'])->name('attendance.corrections.reject')->middleware('permission:attendance.approve-regularization');

        Route::get('/attendance/import', [AttendanceController::class, 'importForm'])->name('attendance.import')->middleware('permission:attendance.import-machine');
        Route::post('/attendance/import', [AttendanceController::class, 'import'])->name('attendance.import.store')->middleware('permission:attendance.import-machine');

        Route::get('/attendance/employee/{employee}', [AttendanceController::class, 'employee'])->name('attendance.employee')->middleware($attendanceView);
        Route::post('/attendance/employee/{employee}/punch', [AttendanceController::class, 'manualPunch'])->name('attendance.manual-punch')->middleware('permission:attendance.update');
        Route::post('/attendance/employee/{employee}/recalculate', [AttendanceController::class, 'recalculate'])->name('attendance.recalculate')->middleware('permission:attendance.update');
        Route::post('/attendance/punches/{punch}/void', [AttendanceController::class, 'voidPunch'])->name('attendance.punches.void')->middleware('permission:attendance.update');
        Route::post('/attendance/records/{record}/review', [AttendanceController::class, 'review'])->name('attendance.records.review')->middleware('permission:attendance.update');
        Route::post('/attendance/records/{record}/override', [AttendanceController::class, 'override'])->name('attendance.records.override')->middleware('permission:attendance.update');
        Route::post('/attendance/records/{record}/overtime', [AttendanceController::class, 'approveOvertime'])->name('attendance.records.overtime')->middleware('permission:attendance.approve-regularization');

        // Attendance setup: office geofences and holidays
        Route::get('/setup/locations', [AttendanceSetupController::class, 'locations'])->name('setup.locations')->middleware('permission:attendance.configure');
        Route::post('/setup/locations', [AttendanceSetupController::class, 'storeLocation'])->name('setup.locations.store')->middleware('permission:attendance.configure');
        Route::put('/setup/locations/{location}', [AttendanceSetupController::class, 'updateLocation'])->name('setup.locations.update')->middleware('permission:attendance.configure');
        Route::delete('/setup/locations/{location}', [AttendanceSetupController::class, 'destroyLocation'])->name('setup.locations.destroy')->middleware('permission:attendance.configure');
        Route::get('/setup/holidays', [AttendanceSetupController::class, 'holidays'])->name('setup.holidays')->middleware('permission:holidays.view');
        Route::post('/setup/holidays', [AttendanceSetupController::class, 'storeHoliday'])->name('setup.holidays.store')->middleware('permission:holidays.create');
        Route::delete('/setup/holidays/{holiday}', [AttendanceSetupController::class, 'destroyHoliday'])->name('setup.holidays.destroy')->middleware('permission:holidays.delete');

        // Leave
        Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index')->middleware($leaveView);
        Route::post('/leave/{application}/approve', [LeaveController::class, 'approve'])->name('leave.approve')->middleware('permission:leaves.approve');
        Route::post('/leave/{application}/reject', [LeaveController::class, 'reject'])->name('leave.reject')->middleware('permission:leaves.approve');
        Route::post('/leave/{application}/cancel', [LeaveController::class, 'cancel'])->name('leave.cancel')->middleware('permission:leaves.approve');
        Route::get('/leave/balances', [LeaveController::class, 'balances'])->name('leave.balances')->middleware($leaveView);
        Route::post('/leave/balances/adjust', [LeaveController::class, 'adjust'])->name('leave.adjust')->middleware('permission:leaves.adjust-balance');

        // Payroll
        Route::get('/payroll', [PayrollRunController::class, 'index'])->name('payroll.index')->middleware($payrollView);
        Route::post('/payroll/runs', [PayrollRunController::class, 'store'])->name('payroll.runs.store')->middleware('permission:payroll.generate');
        Route::get('/payroll/runs/{run}', [PayrollRunController::class, 'show'])->name('payroll.runs.show')->middleware($payrollView);
        Route::post('/payroll/runs/{run}/calculate', [PayrollRunController::class, 'calculate'])->name('payroll.runs.calculate')->middleware('permission:payroll.generate|payroll.recalculate');
        Route::post('/payroll/runs/{run}/transition/{action}', [PayrollRunController::class, 'transition'])->name('payroll.runs.transition')->middleware($payrollView);
        Route::post('/payroll/runs/{run}/items/{item}/hold', [PayrollRunController::class, 'hold'])->name('payroll.runs.items.hold')->middleware('permission:payroll.generate|payroll.recalculate');
        Route::post('/payroll/runs/{run}/adjustments', [PayrollRunController::class, 'storeAdjustment'])->name('payroll.runs.adjustments.store')->middleware('permission:payroll.adjust');
        Route::delete('/payroll/runs/{run}/adjustments/{adjustment}', [PayrollRunController::class, 'voidAdjustment'])->name('payroll.runs.adjustments.void')->middleware('permission:payroll.adjust');

        Route::get('/payroll/periods', [PayrollController::class, 'periods'])->name('payroll.periods')->middleware($payrollView);
        Route::post('/payroll/periods', [PayrollController::class, 'storePeriod'])->name('payroll.periods.store')->middleware('permission:payroll.generate');

        Route::get('/payroll/structures', [PayrollController::class, 'structures'])->name('payroll.structures')->middleware($payrollView);
        Route::get('/payroll/structures/{employee}', [PayrollController::class, 'editStructure'])->name('payroll.structures.edit')->middleware($payrollView);
        Route::post('/payroll/structures/{employee}', [PayrollController::class, 'storeStructure'])->name('payroll.structures.store')->middleware('permission:payroll.manage-salary-structure');
        Route::post('/payroll/structures/{employee}/import-offer', [PayrollController::class, 'importFromOffer'])->name('payroll.structures.import-offer')->middleware('permission:payroll.manage-salary-structure');

        Route::get('/payroll/settings', [PayrollController::class, 'settings'])->name('payroll.settings')->middleware($payrollView);
        Route::post('/payroll/settings', [PayrollController::class, 'storeSettings'])->name('payroll.settings.store')->middleware('permission:payroll.manage-salary-structure');

        Route::get('/payroll/payslips', [PayrollController::class, 'payslips'])->name('payroll.payslips')->middleware($payrollView);
        Route::get('/payroll/payslips/{payslip}/download', [PayrollController::class, 'downloadPayslip'])->name('payroll.payslips.download')->middleware($payrollView);

        // Reports
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index')
            ->middleware('permission:attendance.export|payroll.export|leaves.view-team|leaves.view-branch|leaves.view-all-branches');
        Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show')
            ->middleware('permission:attendance.export|payroll.export|leaves.view-team|leaves.view-branch|leaves.view-all-branches');
    });
});
