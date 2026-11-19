<?php

namespace App\Services\Workforce;

use App\Models\AttendanceImportBatch;
use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Imports biometric / attendance-machine punches from CSV.
 *
 * Expected columns (header row required, order free, names case-insensitive):
 *   employee_id, punched_at (or date + time), direction (optional: in/out),
 *   device_id (optional)
 *
 * Re-importing the same file never creates duplicates (each punch has a
 * deterministic hash), unmatched employee IDs are reported instead of
 * dropped, and original rows are kept as punches - corrections are made
 * with manual punches, not by editing them.
 */
class MachinePunchImporter
{
    public function __construct(
        private readonly PunchService $punches,
        private readonly AttendanceCalculator $calculator,
    ) {
    }

    public function import(string $path, ?string $filename, ?User $by): AttendanceImportBatch
    {
        $rows = $this->readRows($path);

        $batch = AttendanceImportBatch::create([
            'type' => 'machine',
            'filename' => $filename,
            'uploaded_by' => $by?->id,
            'total_rows' => $rows->count(),
        ]);

        $imported = $duplicates = $unmatched = $errors = 0;
        $unmatchedIds = [];
        $errorRows = [];
        $touched = [];

        $employees = User::query()
            ->whereNotNull('employee_id')
            ->get()
            ->keyBy(fn (User $u) => mb_strtolower(trim($u->employee_id)));

        $parsed = $rows->map(function (array $row, int $index) use ($employees, &$unmatched, &$unmatchedIds, &$errors, &$errorRows) {
            $code = mb_strtolower(trim((string) ($row['employee_id'] ?? '')));
            $user = $employees->get($code);

            try {
                $at = $this->parseTime($row);
            } catch (\Throwable) {
                $errors++;
                $errorRows[] = ['row' => $index + 2, 'reason' => 'Unreadable date/time'];

                return null;
            }

            if (!$user) {
                $unmatched++;
                $unmatchedIds[$row['employee_id'] ?? '(blank)'] = true;

                return null;
            }

            return ['user' => $user, 'at' => $at, 'direction' => $this->direction($row['direction'] ?? null), 'device' => $row['device_id'] ?? null];
        })->filter()->sortBy(fn ($r) => $r['user']->id . '|' . $r['at']->timestamp)->values();

        foreach ($parsed as $row) {
            /** @var User $user */
            $user = $row['user'];
            $type = $row['direction'] ?? $this->inferType($user, $row['at']);
            $hash = sha1(implode('|', ['machine', $row['device'] ?? '', $user->id, $row['at']->format('Y-m-d H:i:s'), $type]));

            if (AttendancePunch::query()->where('dedupe_hash', $hash)->exists()) {
                $duplicates++;
                continue;
            }

            $date = $this->punches->attendanceDateFor($user, $row['at'], $type);

            AttendancePunch::create([
                'user_id' => $user->id,
                'client_id' => $user->client_id,
                'attendance_date' => $date->toDateString(),
                'punched_at' => $row['at'],
                'type' => $type,
                'source' => AttendancePunch::SOURCE_MACHINE,
                'device_id' => $row['device'],
                'dedupe_hash' => $hash,
                'geo_status' => GeofenceService::NOT_REQUIRED,
                'created_by' => $by?->id,
            ]);

            $imported++;
            $touched[$user->id . '|' . $date->toDateString()] = [$user, $date];
        }

        $locked = 0;
        foreach ($touched as [$user, $date]) {
            $record = $this->calculator->calculate($user, $date);
            $locked += $record->isLocked() ? 1 : 0;
        }

        $batch->update([
            'imported_rows' => $imported,
            'duplicate_rows' => $duplicates,
            'unmatched_rows' => $unmatched,
            'error_rows' => $errors,
            'details' => [
                'unmatched_employee_ids' => array_slice(array_keys($unmatchedIds), 0, 50),
                'errors' => array_slice($errorRows, 0, 50),
                'days_recalculated' => count($touched),
                'days_locked_by_payroll' => $locked,
            ],
        ]);

        return $batch;
    }

    /** Machines that do not send a direction: first punch of the open session is "in", the next is "out". */
    private function inferType(User $user, Carbon $at): string
    {
        $state = $this->punches->currentState($user, $at);

        return $state['last_type'] === AttendancePunch::TYPE_IN || $state['last_type'] === AttendancePunch::TYPE_BREAK_END
            ? AttendancePunch::TYPE_OUT
            : AttendancePunch::TYPE_IN;
    }

    private function direction(?string $value): ?string
    {
        $value = mb_strtolower(trim((string) $value));

        return match (true) {
            in_array($value, ['in', 'i', 'checkin', 'check-in', 'check in', '0'], true) => AttendancePunch::TYPE_IN,
            in_array($value, ['out', 'o', 'checkout', 'check-out', 'check out', '1'], true) => AttendancePunch::TYPE_OUT,
            default => null,
        };
    }

    private function parseTime(array $row): Carbon
    {
        if (!empty($row['punched_at'])) {
            return Carbon::parse($row['punched_at']);
        }

        if (!empty($row['date']) && !empty($row['time'])) {
            return Carbon::parse($row['date'] . ' ' . $row['time']);
        }

        throw new \InvalidArgumentException('No timestamp');
    }

    /** @return Collection<int, array<string, string>> */
    private function readRows(string $path): Collection
    {
        $handle = fopen($path, 'r');
        $rows = collect();

        if (!$handle) {
            return $rows;
        }

        $header = null;

        while (($line = fgetcsv($handle)) !== false) {
            if ($line === [null] || $line === false) {
                continue;
            }

            if ($header === null) {
                $header = array_map(fn ($h) => str_replace([' ', '-'], '_', mb_strtolower(trim((string) $h, "\xEF\xBB\xBF \t\n\r"))), $line);
                continue;
            }

            $rows->push(array_combine($header, array_pad(array_slice($line, 0, count($header)), count($header), '')));
        }

        fclose($handle);

        return $rows;
    }
}
