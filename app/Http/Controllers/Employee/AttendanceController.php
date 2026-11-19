<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrection;
use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Services\Workforce\AttendanceCalculator;
use App\Services\Workforce\CorrectionService;
use App\Services\Workforce\PolicyResolver;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceCalculator $calculator,
        private readonly PolicyResolver $resolver,
        private readonly CorrectionService $corrections,
    ) {
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        $month = $this->month($request->query('month'));
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth()->startOfDay();
        $until = $to->gt(today()) ? today() : $to;

        // Fill in days that have not been calculated yet (only up to today).
        $have = AttendanceRecord::query()->where('user_id', $user->id)
            ->whereDate('attendance_date', '>=', $from->toDateString())
            ->whereDate('attendance_date', '<=', $to->toDateString())
            ->pluck('attendance_date')->map(fn ($d) => $d->toDateString())->all();

        for ($d = $from->copy(); $d->lte($until); $d->addDay()) {
            if (!in_array($d->toDateString(), $have, true)) {
                $this->calculator->calculate($user, $d->copy());
            }
        }

        $records = AttendanceRecord::query()->where('user_id', $user->id)
            ->whereDate('attendance_date', '>=', $from->toDateString())
            ->whereDate('attendance_date', '<=', $to->toDateString())
            ->with('leaveApplication.leaveType')->get()
            ->keyBy(fn ($r) => $r->attendance_date->toDateString());

        $punches = AttendancePunch::query()->where('user_id', $user->id)->valid()
            ->whereDate('attendance_date', '>=', $from->toDateString())
            ->whereDate('attendance_date', '<=', $to->toDateString())
            ->orderBy('punched_at')->get()
            ->groupBy(fn ($p) => $p->attendance_date->toDateString());

        $holidays = $this->resolver->holidaysBetween($user, $from, $to);
        $corrections = AttendanceCorrection::query()->where('user_id', $user->id)
            ->latest()->limit(20)->get();

        $days = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $key = $d->toDateString();
            $r = $records->get($key);

            $days[] = [
                'date' => $key,
                'day' => $d->format('d'),
                'weekday' => $d->format('D'),
                'status' => $r?->status ?? ($holidays->has($key) ? 'holiday' : 'pending'),
                'first_in' => $r?->first_in?->format('h:i A'),
                'last_out' => $r?->last_out?->format('h:i A'),
                'worked_minutes' => $r?->worked_minutes ?? 0,
                'late_minutes' => $r?->late_minutes ?? 0,
                'early_minutes' => $r?->early_minutes ?? 0,
                'overtime_minutes' => $r && $r->overtime_approved ? $r->overtime_minutes : 0,
                'flags' => $r?->flags ?? [],
                'locked' => (bool) $r?->isLocked(),
                'note' => $holidays->get($key)?->name ?? $r?->leaveApplication?->leaveType?->name,
                'is_today' => $d->isToday(),
                'can_correct' => !$d->gt(today()) && !$r?->isLocked() && $d->gte(today()->subDays(CorrectionService::WINDOW_DAYS)),
                'punches' => ($punches->get($key) ?? collect())->map(fn ($p) => [
                    'type' => $p->type, 'source' => $p->source, 'time' => $p->punched_at->format('h:i A'),
                ])->values()->all(),
            ];
        }

        $counted = collect($days);
        $summary = [
            'present' => $counted->where('status', 'present')->count(),
            'half_day' => $counted->where('status', 'half_day')->count(),
            'absent' => $counted->where('status', 'absent')->count(),
            'leave' => $counted->where('status', 'leave')->count(),
            'weekly_off' => $counted->where('status', 'weekly_off')->count(),
            'holiday' => $counted->where('status', 'holiday')->count(),
            'late' => $counted->where('late_minutes', '>', 0)->count(),
            'exceptions' => $counted->where('status', 'incomplete')->count(),
            'worked_minutes' => $counted->sum('worked_minutes'),
            'overtime_minutes' => $counted->sum('overtime_minutes'),
        ];

        return Inertia::render('Employee/Attendance', [
            'month' => $month->format('Y-m'),
            'monthLabel' => $month->format('F Y'),
            'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->lte(today()->endOfMonth()) ? $month->copy()->addMonth()->format('Y-m') : null,
            'days' => $days,
            'summary' => $summary,
            'corrections' => $corrections->map(fn ($c) => [
                'id' => $c->id,
                'date' => $c->attendance_date->format('d M Y'),
                'requested_in' => $c->requested_in?->format('h:i A'),
                'requested_out' => $c->requested_out?->format('h:i A'),
                'reason' => $c->reason,
                'status' => $c->status,
                'decision_note' => $c->decision_note,
            ]),
        ]);
    }

    public function requestCorrection(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'in_time' => ['nullable', 'date_format:H:i'],
            'out_time' => ['nullable', 'date_format:H:i'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $date = Carbon::parse($data['date'])->startOfDay();
        $in = !empty($data['in_time']) ? Carbon::parse($date->toDateString() . ' ' . $data['in_time']) : null;
        $out = !empty($data['out_time']) ? Carbon::parse($date->toDateString() . ' ' . $data['out_time']) : null;

        // An exit earlier than the entry means the shift ran past midnight.
        if ($in && $out && $out->lte($in)) {
            $out->addDay();
        }

        try {
            $this->corrections->request($request->user(), $date, $in, $out, $data['reason'], $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['correction' => $e->getMessage()]);
        }

        return back()->with('success', 'Correction request sent for approval.');
    }

    public function cancelCorrection(Request $request, AttendanceCorrection $correction): RedirectResponse
    {
        abort_unless($correction->user_id === $request->user()->id, 403);

        try {
            $this->corrections->cancel($correction, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['correction' => $e->getMessage()]);
        }

        return back()->with('success', 'Request cancelled.');
    }

    private function month(?string $value): Carbon
    {
        try {
            return $value ? Carbon::createFromFormat('Y-m', $value)->startOfMonth() : today()->startOfMonth();
        } catch (\Throwable) {
            return today()->startOfMonth();
        }
    }
}
