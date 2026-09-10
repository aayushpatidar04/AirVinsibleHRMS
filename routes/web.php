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
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Candidate\RegistrationController;
use App\Http\Controllers\HR\DashboardController as HrDashboard;
use App\Http\Controllers\Interviewer\DashboardController as InterviewerDashboard;
use App\Http\Controllers\Interviewer\CandidateController as InterviewerCandidateController;
use App\Http\Controllers\Interviewer\InterviewController;
use App\Http\Controllers\Recruitment\CandidateOfferController;
use App\Http\Controllers\Recruitment\CandidateOfferPortalController;

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
        $user = request()->user();

        return match ($user->primaryRole()) {
            'admin' => redirect()->route('admin.dashboard'),
            'hr' => redirect()->route('hr.dashboard'),
            'interviewer' => redirect()->route('interviewer.dashboard'),
            default => redirect()->route('profile.edit'),
        };
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

            Route::get('/roles', [RoleController::class, 'index'])
                ->middleware('permission:roles.view')
                ->name('roles.index');

            Route::get('/roles/create', [RoleController::class, 'create'])
                ->middleware('permission:roles.create')
                ->name('roles.create');

            Route::post('/roles', [RoleController::class, 'store'])
                ->middleware('permission:roles.create')
                ->name('roles.store');

            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
                ->middleware('permission:roles.update')
                ->name('roles.edit');

            Route::put('/roles/{role}', [RoleController::class, 'update'])
                ->middleware('permission:roles.update')
                ->name('roles.update');

            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
                ->middleware('permission:roles.delete')
                ->name('roles.destroy');

        });

    /*
    |--------------------------------------------------------------------------
    | Recruitment Operations
    |--------------------------------------------------------------------------
    |
    | These routes are shared by Admin and HR.
    | Policies enforce company-wide versus branch-level access.
    |
    */

    Route::prefix('recruitment')
        ->name('recruitment.')
        ->group(function () {
            Route::get(
                '/candidates',
                [AdminCandidateController::class, 'index']
            )
                ->middleware('permission:candidates.view')
                ->name('candidates.index');

            Route::get(
                '/candidates/compare',
                [AdminCandidateController::class, 'compare']
            )->name('candidates.compare');

            Route::get(
                '/candidates/{candidate}',
                [AdminCandidateController::class, 'show']
            )
                ->middleware('permission:candidates.view')
                ->name('candidates.show');

            Route::put(
                '/candidates/{candidate}/approval',
                [AdminCandidateController::class, 'updateApprovalStatus']
            )
                ->middleware(
                    'permission:candidates.approve|candidates.reject-approval'
                )
                ->name('candidates.update-approval');

            Route::put(
                '/candidates/{candidate}/status',
                [AdminCandidateController::class, 'updateFinalStatus']
            )
                ->middleware('permission:candidates.final-decision')
                ->name('candidates.update-status');

            Route::delete(
                '/candidates/{candidate}',
                [AdminCandidateController::class, 'destroy']
            )
                ->middleware('permission:candidates.delete')
                ->name('candidates.destroy');

            Route::post(
                '/candidates/{candidate}/rounds/{round}/schedule',
                [AdminCandidateController::class, 'scheduleInterview']
            )
                ->middleware('permission:interviews.schedule')
                ->name('candidates.rounds.schedule');

            Route::put(
                '/interview-schedules/{schedule}',
                [AdminCandidateController::class, 'updateSchedule']
            )
                ->middleware('permission:interviews.reschedule')
                ->name('interview-schedules.update');

            Route::delete(
                '/interview-schedules/{schedule}',
                [AdminCandidateController::class, 'cancelSchedule']
            )
                ->middleware('permission:interviews.cancel')
                ->name('interview-schedules.cancel');

            Route::post(
                '/interview-schedules/{schedule}/send-email',
                [AdminCandidateController::class, 'sendScheduleEmail']
            )
                ->middleware('permission:interviews.send-email')
                ->name('interview-schedules.send-email');

            Route::get(
                '/offers',
                [CandidateOfferController::class, 'index']
            )->name('offers.index');

            /*
            |--------------------------------------------------------------------------
            | Create Offer for Candidate
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/candidates/{candidate}/offers/create',
                [CandidateOfferController::class, 'create']
            )->name('candidates.offers.create');

            Route::post(
                '/candidates/{candidate}/offers',
                [CandidateOfferController::class, 'store']
            )->name('candidates.offers.store');

            /*
            |--------------------------------------------------------------------------
            | Offer Detail and Editing
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/offers/{candidateOffer}',
                [CandidateOfferController::class, 'show']
            )->name('offers.show');

            Route::get(
                '/offers/{candidateOffer}/edit',
                [CandidateOfferController::class, 'edit']
            )->name('offers.edit');

            Route::put(
                '/offers/{candidateOffer}',
                [CandidateOfferController::class, 'update']
            )->name('offers.update');

            /*
            |--------------------------------------------------------------------------
            | Offer Lifecycle
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/offers/{candidateOffer}/submit-for-approval',
                [
                    CandidateOfferController::class,
                    'submitForApproval',
                ]
            )->name('offers.submit-for-approval');

            Route::post(
                '/offers/{candidateOffer}/approve',
                [
                    CandidateOfferController::class,
                    'approve',
                ]
            )->name('offers.approve');

            Route::get(
                '/offers/{candidateOffer}/revisions/create',
                [
                    CandidateOfferController::class,
                    'createRevisionForm',
                ]
            )->name(
                'offers.revisions.create'
            );

            Route::post(
                '/offers/{candidateOffer}/revisions',
                [
                    CandidateOfferController::class,
                    'createRevision',
                ]
            )->name('offers.revisions.store');

            Route::post(
                '/offers/{candidateOffer}/cancel',
                [
                    CandidateOfferController::class,
                    'cancel',
                ]
            )->name('offers.cancel');

            Route::delete(
                '/offers/{candidateOffer}',
                [
                    CandidateOfferController::class,
                    'destroy',
                ]
            )->name('offers.destroy');

            Route::post(
                'offers/{candidateOffer}/generate-pdf',
                [CandidateOfferController::class, 'generatePdf']
            )->name('offers.generate-pdf');

            Route::get(
                'offers/{candidateOffer}/download-pdf',
                [CandidateOfferController::class, 'downloadPdf']
            )->name('offers.download-pdf');

            Route::get(
                'offers/{candidateOffer}/send',
                [CandidateOfferController::class, 'createSend']
            )->name('offers.send.create');

            Route::post(
                'offers/{candidateOffer}/send',
                [CandidateOfferController::class, 'send']
            )->name('offers.send');

            Route::post(
                'offers/{candidateOffer}/cancel',
                [CandidateOfferController::class, 'cancel']
            )->name('offers.cancel');
        });

    /*
    |----------------------------------------------------------------------
    | Interviewer Routes
    |----------------------------------------------------------------------
    */
    Route::prefix('interviewer')
        ->name('interviewer.')
        ->middleware('permission:interviews.view-assigned')
        ->group(function () {
            Route::get(
                '/dashboard',
                [InterviewerDashboard::class, 'index']
            )->name('dashboard');

            Route::get(
                '/candidates',
                [InterviewerCandidateController::class, 'index']
            )
                ->middleware('permission:candidates.view')
                ->name('candidates.index');

            Route::get(
                '/candidates/{candidate}',
                [InterviewerCandidateController::class, 'show']
            )
                ->middleware('permission:candidates.view')
                ->name('candidates.show');

            Route::get(
                '/candidates/{candidate}/rounds/{round}',
                [InterviewController::class, 'show']
            )
                ->middleware('permission:interviews.view-assigned')
                ->name('interviews.show');

            Route::post(
                '/candidates/{candidate}/rounds/{round}/start',
                [InterviewController::class, 'start']
            )
                ->middleware('permission:interviews.start')
                ->name('interviews.start');

            Route::post(
                '/candidates/{candidate}/rounds/{round}/response',
                [InterviewController::class, 'saveResponse']
            )
                ->middleware('permission:interviews.save-response')
                ->name('interviews.response.save');

            Route::post(
                '/candidates/{candidate}/rounds/{round}/complete',
                [InterviewController::class, 'complete']
            )
                ->middleware('permission:interviews.complete')
                ->name('interviews.complete');

            Route::post(
                '/candidates/{candidate}/rounds/{round}/reassign',
                [InterviewController::class, 'reassign']
            )
                ->middleware('permission:interviews.reassign-interviewer')
                ->name('interviews.reassign');

            Route::post(
                '/candidates/{candidate}/rounds/{round}/reject',
                [InterviewController::class, 'reject']
            )
                ->middleware('permission:interviews.reject-candidate')
                ->name('interviews.reject');

            Route::post(
                '/candidates/{candidate}/rounds/{round}/add-question',
                [InterviewController::class, 'addCustomQuestion']
            )
                ->middleware('permission:interviews.add-custom-question')
                ->name('interviews.add-question');

            Route::get(
                '/candidates/{candidate}/rounds/{round}/interviewers',
                [InterviewController::class, 'getInterviewers']
            )
                ->middleware('permission:interviews.recommend-next-round')
                ->name('interviews.get-interviewers');

            Route::delete(
                '/candidates/{candidate}/rounds/{round}/custom-questions/{customQuestion}',
                [InterviewController::class, 'deleteCustomQuestion']
            )
                ->middleware(
                    'permission:interviews.add-custom-question'
                )
                ->name(
                    'interviews.custom-questions.destroy'
                );

        });

    Route::prefix('hr')
        ->name('hr.')
        ->middleware('permission:dashboard.hr.view')
        ->group(function () {
            Route::get(
                '/dashboard',
                [HrDashboard::class, 'index']
            )->name('dashboard');
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

Route::prefix('candidate-offer')
    ->name('candidate-offers.portal.')
    ->middleware([
        'throttle:offer-portal',
    ])
    ->group(function () {
        Route::get(
            '{token}',
            [
                CandidateOfferPortalController::class,
                'show',
            ]
        )->name('show');

        Route::get(
            '{token}/pdf',
            [
                CandidateOfferPortalController::class,
                'downloadPdf',
            ]
        )->name('download-pdf');

        Route::post(
            '{token}/accept',
            [
                CandidateOfferPortalController::class,
                'accept',
            ]
        )->name('accept');

        Route::post(
            '{token}/decline',
            [
                CandidateOfferPortalController::class,
                'decline',
            ]
        )->name('decline');
    });