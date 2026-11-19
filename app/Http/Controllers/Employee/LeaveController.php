<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use App\Services\Workforce\LeaveException;
use App\Services\Workforce\LeaveService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveController extends Controller
{
    public function __construct(private readonly LeaveService $leave)
    {
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        $applications = LeaveApplication::query()
            ->where('user_id', $user->id)
            ->with(['leaveType:id,name,code', 'approvals.actor:id,first_name,last_name'])
            ->latest('start_date')
            ->limit(50)
            ->get()
            ->map(fn (LeaveApplication $a) => [
                'id' => $a->id,
                'type' => $a->leaveType?->name,
                'start_date' => $a->start_date->format('d M Y'),
                'end_date' => $a->end_date->format('d M Y'),
                'days' => $a->total_days,
                'half_day' => $a->is_half_day ? str_replace('_', ' ', (string) $a->half_day_session) : null,
                'reason' => $a->reason,
                'status' => $a->status,
                'can_cancel' => in_array($a->status, ['pending', 'approved'], true)
                    && ($a->status === 'pending' || $a->start_date->gt(today())),
                'approvals' => $a->approvals->map(fn ($ap) => [
                    'role' => str_replace('_', ' ', $ap->approver_role),
                    'status' => $ap->status,
                    'by' => $ap->actor?->full_name,
                    'note' => $ap->note,
                ]),
            ]);

        return Inertia::render('Employee/Leave', [
            'balances' => $this->leave->balances($user),
            'applications' => $applications,
        ]);
    }

    /** Live check used by the apply form: how many days, and would it be accepted. */
    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'leave_type_id' => ['required', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'is_half_day' => ['boolean'],
        ]);

        try {
            $checked = $this->leave->validate(
                $request->user(),
                (int) $data['leave_type_id'],
                Carbon::parse($data['start_date']),
                Carbon::parse($data['end_date']),
                (bool) ($data['is_half_day'] ?? false),
            );
        } catch (LeaveException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }

        return response()->json([
            'ok' => true,
            'days' => $checked['days'],
            'levels' => $checked['policy']->levels(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'leave_type_id' => ['required', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_half_day' => ['boolean'],
            'half_day_session' => ['nullable', 'in:first_half,second_half'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $this->leave->apply(
                $request->user(),
                (int) $data['leave_type_id'],
                Carbon::parse($data['start_date']),
                Carbon::parse($data['end_date']),
                $data['reason'],
                (bool) ($data['is_half_day'] ?? false),
                $data['half_day_session'] ?? null,
            );
        } catch (LeaveException $e) {
            return back()->withErrors(['leave' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Leave request submitted.');
    }

    public function cancel(Request $request, LeaveApplication $application): RedirectResponse
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        try {
            $this->leave->cancel($application, $request->user());
        } catch (LeaveException $e) {
            return back()->withErrors(['leave' => $e->getMessage()]);
        }

        return back()->with('success', 'Leave cancelled.');
    }
}
