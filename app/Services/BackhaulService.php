<?php

namespace App\Services;

use App\Models\BackhaulRecommendation;
use App\Models\CargoRequest;
use App\Models\Trip;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BackhaulService
{
    // Ethiopian corridor cities: each entry lists accepted name variants and coordinates.
    private static array $CITIES = [
        ['names' => ['addis ababa', 'addis abeba', 'addis'], 'lat' => 9.03,  'lng' => 38.74],
        ['names' => ['mekele', 'mekelle', 'mekелe'],         'lat' => 13.49, 'lng' => 39.47],
        ['names' => ['gondar', 'gonder'],                     'lat' => 12.60, 'lng' => 37.47],
        ['names' => ['bahir dar', 'bahar dar', 'bahirdar'],   'lat' => 11.59, 'lng' => 37.39],
        ['names' => ['dire dawa', 'diredawa'],                'lat' => 9.59,  'lng' => 41.86],
        ['names' => ['hawassa', 'awasa'],                     'lat' => 7.06,  'lng' => 38.47],
        ['names' => ['jimma', 'jima'],                        'lat' => 7.67,  'lng' => 36.83],
        ['names' => ['metema'],                               'lat' => 12.85, 'lng' => 36.20],
        ['names' => ['humera', 'humer'],                      'lat' => 14.30, 'lng' => 36.61],
        ['names' => ['shire', 'shire indasilase'],            'lat' => 14.10, 'lng' => 38.28],
        ['names' => ['addis zemen'],                          'lat' => 12.13, 'lng' => 37.79],
        ['names' => ['debre tabor', 'debretabor'],            'lat' => 11.85, 'lng' => 38.01],
        ['names' => ['debre markos', 'debremarkos'],          'lat' => 10.33, 'lng' => 37.72],
    ];

    /**
     * Generate and persist backhaul recommendations for a trip.
     * Returns the top-5 BackhaulRecommendation records with cargoRequest loaded.
     */
    public function recommendForTrip(Trip $trip): Collection
    {
        $trip->loadMissing('booking.cargoRequest');
        $booking = $trip->booking;

        if (!$booking) {
            return collect();
        }

        $driverId       = $booking->driver_id;
        $currentCargoId = $booking->cargo_id;
        $destCoords     = $this->resolveCity($trip->destination);

        if (!$destCoords) {
            Log::warning("BackhaulService: no city match for destination '{$trip->destination}' (trip #{$trip->id})");
            return collect();
        }

        [$destLat, $destLng] = $destCoords;

        // Fetch all pending cargo except the one already being hauled
        $candidates = CargoRequest::where('status', 'pending')
            ->when($currentCargoId, fn ($q) => $q->where('id', '!=', $currentCargoId))
            ->get();

        $scored = $candidates->map(function (CargoRequest $cargo) use ($destLat, $destLng) {
            $pickupCoords = $this->resolveCity($cargo->pickup_location);
            if (!$pickupCoords) {
                return null;
            }

            [$pickLat, $pickLng] = $pickupCoords;
            $distanceKm = $this->haversine($destLat, $destLng, $pickLat, $pickLng);

            if ($distanceKm > 50) {
                return null; // outside 50 km radius
            }

            $distanceScore = max(0.0, 1.0 - ($distanceKm / 50.0));

            $urgencyScore = match (strtolower($cargo->urgency_level ?? 'normal')) {
                'urgent', 'express' => 1.0,
                'high', 'normal'    => 0.5,
                'low'               => 0.2,
                default             => 0.5,
            };

            $weightScore = min((float) $cargo->weight / 20.0, 1.0);

            $finalScore = ($distanceScore * 0.4) + ($urgencyScore * 0.3) + ($weightScore * 0.3);

            return [
                'cargo'       => $cargo,
                'score'       => round($finalScore, 3),
                'distance_km' => round($distanceKm, 1),
            ];
        })->filter()->sortByDesc('score')->take(5);

        return $scored->map(function (array $item) use ($trip, $driverId) {
            /** @var CargoRequest $cargo */
            $cargo = $item['cargo'];

            $priceRange = null;
            if ($cargo->budget) {
                $priceRange = [
                    'min' => round($cargo->budget * 0.8),
                    'max' => round($cargo->budget * 1.2),
                ];
            }

            $rec = BackhaulRecommendation::updateOrCreate(
                [
                    'trip_id'          => $trip->id,
                    'driver_id'        => $driverId,
                    'cargo_request_id' => $cargo->id,
                ],
                [
                    'score'  => $item['score'],
                    'status' => 'pending',
                    'metadata' => [
                        'distance_km'           => $item['distance_km'],
                        'urgency'               => $cargo->urgency_level,
                        'estimated_price_range' => $priceRange,
                        'pickup_location_name'  => $cargo->pickup_location,
                    ],
                ]
            );

            $rec->setRelation('cargoRequest', $cargo);

            return $rec;
        });
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Returns [lat, lng] for a city string, or null if unknown.
     * Matches greedily — longest matching name wins to avoid "Addis" matching
     * inside "Addis Zemen" prematurely. List is ordered longest-first where
     * ambiguous.
     */
    private function resolveCity(string $location): ?array
    {
        $s = strtolower(trim($location));
        // Prefer longer matches (e.g. "Addis Zemen" before "Addis")
        $best      = null;
        $bestLen   = 0;

        foreach (self::$CITIES as $city) {
            foreach ($city['names'] as $name) {
                if (str_contains($s, $name) && strlen($name) > $bestLen) {
                    $best    = [$city['lat'], $city['lng']];
                    $bestLen = strlen($name);
                }
            }
        }

        return $best;
    }

    /**
     * Haversine great-circle distance in kilometres.
     */
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R   = 6371.0;
        $φ1  = deg2rad($lat1);
        $φ2  = deg2rad($lat2);
        $Δφ  = deg2rad($lat2 - $lat1);
        $Δλ  = deg2rad($lng2 - $lng1);

        $a = sin($Δφ / 2) ** 2 + cos($φ1) * cos($φ2) * sin($Δλ / 2) ** 2;

        return 2 * $R * atan2(sqrt($a), sqrt(1 - $a));
    }
}
