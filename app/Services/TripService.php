<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Trip;
use App\Notifications\TripStatusUpdatedNotification;

class TripService
{
    public function startTrip(Booking $booking)
    {
        $trip = Trip::create([
            'booking_id' => $booking->id,
            'trip_status' => 'in_transit',
            'route_details' => json_encode([]),
            'gps_tracking_data' => json_encode(['lat' => null, 'lng' => null]),
        ]);

        if ($booking->cargoRequest && $booking->cargoRequest->user) {
            $booking->cargoRequest->user->notify(new TripStatusUpdatedNotification($trip));
        }

        return $trip;
    }

    public function updateLocation(Trip $trip, array $gpsData)
    {
        $trip->update([
            'gps_tracking_data' => json_encode($gpsData)
        ]);

        return $trip;
    }

    public function completeTrip(Trip $trip)
    {
        $trip->update([
            'trip_status' => 'completed'
        ]);
        
        $trip->booking->update(['booking_status' => 'completed']);

        if ($trip->booking->cargoRequest && $trip->booking->cargoRequest->user) {
            $trip->booking->cargoRequest->user->notify(new TripStatusUpdatedNotification($trip));
        }

        return $trip;
    }
}
