<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Controllers
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\InterviewerController;
use App\Http\Controllers\Admin\InterviewRoundController;
use App\Http\Controllers\Admin\FormController;
use App\Http\Controllers\Admin\QRCodeController;
use App\Http\Controllers\Admin\CandidateController as AdminCandidateController;
use App\Http\Controllers\Interviewer\DashboardController as InterviewerDashboard;
use App\Http\Controllers\Interviewer\CandidateController as InterviewerCandidateController;
use App\Http\Controllers\Interviewer\InterviewController;
use App\Http\Controllers\Candidate\RegistrationController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn() => Inertia::render('Welcome'))->name('home');

// Candidate registration via QR code (no auth required)
Route::get('/register/{uuid}', [RegistrationController::class, 'show'])->name('candidate.register');
Route::post('/register/{uuid}', [RegistrationController::class, 'store'])->name('candidate.register.store');

// Candidate status check (public, no auth)
Route::get('/status/{uuid}', [RegistrationController::class, 'status'])->name('candidate.status');

/*
|--------------------------------------------------------------------------
| Breeze Auth Routes
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard redirect based on role
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user->hasRole('admin'))
            return redirect()->route('admin.dashboard');
        if ($user->hasRole('interviewer') || $user->can_interview)
            return redirect()->route('interviewer.dashboard');
        return redirect()->route('profile.edit');
    })->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Admin Routes
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            // Dashboard
            Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

            // Branches
            Route::resource('branches', BranchController::class);

            // Interviewers
            Route::resource('interviewers', InterviewerController::class);
            Route::post(
                '/interviewers/{interviewer}/reset-password',
                [InterviewerController::class, 'resetPassword']
            )->name('interviewers.reset-password');

            Route::resource('employees', EmployeeController::class);
            Route::post('/employees/{employee}/toggle-interviewer', [EmployeeController::class, 'toggleInterviewer'])->name('employees.toggle-interviewer');
            Route::post('/employees/{employee}/assign-role', [EmployeeController::class, 'assignRole'])->name('employees.assign-role');
            Route::post('/employees/{employee}/remove-role', [EmployeeController::class, 'removeRole'])->name('employees.remove-role');
            Route::post('/employees/{employee}/reset-password', [EmployeeController::class, 'resetPassword'])->name('employees.reset-password');
            Route::post('/employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');
            Route::post('/employees/{employee}/interview-rounds', [EmployeeController::class, 'syncInterviewRounds'])->name('employees.sync-interview-rounds');
            Route::get('/employees/import', [EmployeeController::class, 'import'])->name('employees.import');
            Route::post('/employees/import', [EmployeeController::class, 'import']);
            Route::get('/employees/import/sample', [EmployeeController::class, 'downloadSample'])->name('employees.import.sample');

            // Interview Rounds
            Route::resource('rounds', InterviewRoundController::class);
            Route::post(
                '/rounds/{round}/questions',
                [InterviewRoundController::class, 'storeQuestion']
            )->name('rounds.questions.store');
            Route::put(
                '/rounds/{round}/questions/{question}',
                [InterviewRoundController::class, 'updateQuestion']
            )->name('rounds.questions.update');
            Route::delete(
                '/rounds/{round}/questions/{question}',
                [InterviewRoundController::class, 'destroyQuestion']
            )->name('rounds.questions.destroy');
            Route::post(
                '/rounds/{round}/allowed-interviewers',
                [InterviewRoundController::class, 'updateAllowedInterviewers']
            )->name('rounds.allowed-interviewers.update');

            // Registration Forms
            Route::resource('forms', FormController::class);

            // QR Codes
            Route::resource('qrcodes', QRCodeController::class)->except(['edit', 'update']);
            Route::post('/qrcodes/{qrcode}/toggle', [QRCodeController::class, 'toggle'])
                ->name('qrcodes.toggle');

            // Candidates
            Route::get('/candidates', [AdminCandidateController::class, 'index'])->name('candidates.index');
            Route::get('/candidates/{candidate}', [AdminCandidateController::class, 'show'])->name('candidates.show');
            Route::put('/candidates/{candidate}/approval', [AdminCandidateController::class, 'updateApprovalStatus'])->name('candidates.update-approval');
            Route::put('/candidates/{candidate}/status', [AdminCandidateController::class, 'updateFinalStatus'])->name('candidates.update-status');
            Route::delete('/candidates/{candidate}', [AdminCandidateController::class, 'destroy'])->name('candidates.destroy');

            Route::post('/candidates/{candidate}/rounds/{round}/schedule', [AdminCandidateController::class, 'scheduleInterview'])
                ->name('candidates.rounds.schedule');

            Route::put('/interview-schedules/{schedule}', [AdminCandidateController::class, 'updateSchedule'])
                ->name('interview-schedules.update');

            Route::delete('/interview-schedules/{schedule}', [AdminCandidateController::class, 'cancelSchedule'])
                ->name('interview-schedules.cancel');

            Route::post('/interview-schedules/{schedule}/send-email', [AdminCandidateController::class, 'sendScheduleEmail'])
                ->name('interview-schedules.send-email');
        });

    /*
    |----------------------------------------------------------------------
    | Interviewer Routes
    |----------------------------------------------------------------------
    */
    Route::middleware('role:interviewer,admin')
        ->prefix('interviewer')
        ->name('interviewer.')
        ->group(function () {

            // Dashboard
            Route::get('/dashboard', [InterviewerDashboard::class, 'index'])->name('dashboard');

            // My candidates
            Route::get('/candidates', [InterviewerCandidateController::class, 'index'])->name('candidates.index');
            Route::get('/candidates/{candidate}', [InterviewerCandidateController::class, 'show'])->name('candidates.show');

            // Interview actions
            Route::get(
                '/candidates/{candidate}/rounds/{round}',
                [InterviewController::class, 'show']
            )->name('interviews.show');

            Route::post(
                '/candidates/{candidate}/rounds/{round}/start',
                [InterviewController::class, 'start']
            )->name('interviews.start');

            Route::post(
                '/candidates/{candidate}/rounds/{round}/response',
                [InterviewController::class, 'saveResponse']
            )->name('interviews.response.save');

            Route::post(
                '/candidates/{candidate}/rounds/{round}/complete',
                [InterviewController::class, 'complete']
            )->name('interviews.complete');

            Route::post(
                '/candidates/{candidate}/rounds/{round}/reassign',
                [InterviewController::class, 'reassign']
            )->name('interviews.reassign');

            Route::post(
                '/candidates/{candidate}/rounds/{round}/reject',
                [InterviewController::class, 'reject']
            )->name('interviews.reject');

            Route::post(
                '/candidates/{candidate}/rounds/{round}/add-question',
                [InterviewController::class, 'addCustomQuestion']
            )->name('interviews.add-question');

            Route::get(
                '/candidates/{candidate}/rounds/{round}/interviewers',
                [InterviewController::class, 'getInterviewers']
            )->name('interviews.get-interviewers');
        });

    /*
    |----------------------------------------------------------------------
    | Profile (Breeze default)
    |----------------------------------------------------------------------
    */
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [\App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('password.update');

});
