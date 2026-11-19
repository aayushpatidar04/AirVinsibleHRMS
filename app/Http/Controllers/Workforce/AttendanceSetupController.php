<?php

namespace App\Http\Controllers\Workforce;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Holiday;
use App\Models\OfficeLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Office geofences and holiday calendars. */
class AttendanceSetupController extends Controller
{
    public function locations(): Response
    {
        return Inertia::render('Workforce/Setup/Locations', [
            'locations' => OfficeLocation::query()->with('client:id,name', 'branch:id,name')->orderBy('name')->get()
                ->map(fn ($l) => [
                    'id' => $l->id,
                    'name' => $l->name,
                    'latitude' => $l->latitude,
                    'longitude' => $l->longitude,
                    'radius_meters' => $l->radius_meters,
                    'max_accuracy_meters' => $l->max_accuracy_meters,
                    'client_id' => $l->client_id,
                    'branch_id' => $l->branch_id,
                    'client' => $l->client?->name,
                    'branch' => $l->branch?->name,
                    'is_active' => $l->is_active,
                ]),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeLocation(Request $request): RedirectResponse
    {
        $location = OfficeLocation::create($this->locationData($request));
        AuditLog::record('office_location.created', $location, [], $location->only(['name', 'latitude', 'longitude', 'radius_meters']), $request->user()->id);

        return back()->with('success', 'Office location added.');
    }

    public function updateLocation(Request $request, OfficeLocation $location): RedirectResponse
    {
        $old = $location->only(['name', 'latitude', 'longitude', 'radius_meters', 'is_active']);
        $location->update($this->locationData($request));
        AuditLog::record('office_location.updated', $location, $old, $location->only(array_keys($old)), $request->user()->id);

        return back()->with('success', 'Office location updated.');
    }

    public function destroyLocation(Request $request, OfficeLocation $location): RedirectResponse
    {
        AuditLog::record('office_location.deleted', $location, $location->only(['name']), [], $request->user()->id);
        $location->delete();

        return back()->with('success', 'Office location removed.');
    }

    public function holidays(Request $request): Response
    {
        $year = (int) $request->query('year', today()->year);

        return Inertia::render('Workforce/Setup/Holidays', [
            'year' => $year,
            'holidays' => Holiday::query()->with('client:id,name')->whereYear('date', $year)->orderBy('date')->get()
                ->map(fn ($h) => [
                    'id' => $h->id,
                    'date' => $h->date->toDateString(),
                    'label' => $h->date->format('D, d M Y'),
                    'name' => $h->name,
                    'client' => $h->client?->name ?? 'All clients',
                ]),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeHoliday(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:120'],
            'client_id' => ['nullable', 'exists:clients,id'],
        ]);

        $exists = Holiday::query()->whereDate('date', $data['date'])
            ->where('client_id', $data['client_id'] ?? null)->exists();

        if ($exists) {
            return back()->withErrors(['date' => 'A holiday already exists on that date for the same scope.']);
        }

        $holiday = Holiday::create($data);
        AuditLog::record('holiday.created', $holiday, [], $holiday->only(['date', 'name', 'client_id']), $request->user()->id);

        return back()->with('success', 'Holiday added. Days already calculated keep their status until recalculated.');
    }

    public function destroyHoliday(Request $request, Holiday $holiday): RedirectResponse
    {
        AuditLog::record('holiday.deleted', $holiday, $holiday->only(['date', 'name']), [], $request->user()->id);
        $holiday->delete();

        return back()->with('success', 'Holiday removed.');
    }

    private function locationData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:5', 'max:5000'],
            'max_accuracy_meters' => ['required', 'integer', 'min:5', 'max:500'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }
}
