<?php

namespace App\Services\Workforce;

use App\Models\OfficeLocation;
use App\Models\User;

/**
 * Server-side geofence check for portal punches.
 *
 * A browser can report any coordinates, so this is location verification
 * rather than proof; the result (and the raw coordinates) are stored with
 * every punch so exceptions can be reviewed.
 */
class GeofenceService
{
    public const INSIDE = 'inside';
    public const OUTSIDE = 'outside';
    public const LOW_ACCURACY = 'low_accuracy';
    public const UNAVAILABLE = 'unavailable';
    public const NOT_REQUIRED = 'not_required';
    public const NO_LOCATION = 'no_location_configured';

    /**
     * @return array{status:string, allowed:bool, distance:?float, location:?OfficeLocation, message:string}
     */
    public function evaluate(User $user, ?float $latitude, ?float $longitude, ?float $accuracy, bool $required): array
    {
        if (!$required) {
            return $this->result(self::NOT_REQUIRED, true, null, null, 'Location check is not required for your policy.');
        }

        if ($latitude === null || $longitude === null) {
            return $this->result(self::UNAVAILABLE, false, null, null, 'Location permission is required to punch. Please allow location access and try again.');
        }

        $locations = OfficeLocation::query()->availableTo($user)->get();

        if ($locations->isEmpty()) {
            return $this->result(self::NO_LOCATION, false, null, null, 'No authorised office location is configured for you. Please contact HR.');
        }

        $nearest = null;
        $nearestDistance = null;

        foreach ($locations as $location) {
            $distance = self::distanceMeters($latitude, $longitude, $location->latitude, $location->longitude);

            if ($nearestDistance === null || $distance < $nearestDistance) {
                $nearest = $location;
                $nearestDistance = $distance;
            }
        }

        // Poor GPS accuracy cannot prove presence, whichever side of the fence it claims.
        if ($accuracy !== null && $accuracy > $nearest->max_accuracy_meters) {
            return $this->result(
                self::LOW_ACCURACY,
                false,
                $nearestDistance,
                $nearest,
                sprintf('Your location accuracy (±%d m) is too low. Move to an open area and try again.', round($accuracy))
            );
        }

        if ($nearestDistance <= $nearest->radius_meters) {
            return $this->result(self::INSIDE, true, $nearestDistance, $nearest, 'Location verified at ' . $nearest->name . '.');
        }

        return $this->result(
            self::OUTSIDE,
            false,
            $nearestDistance,
            $nearest,
            sprintf('You are %d m from %s. You must be within %d m to punch.', round($nearestDistance), $nearest->name, $nearest->radius_meters)
        );
    }

    /** Great-circle distance in metres (haversine). */
    public static function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function result(string $status, bool $allowed, ?float $distance, ?OfficeLocation $location, string $message): array
    {
        return compact('status', 'allowed', 'distance', 'location', 'message');
    }
}
