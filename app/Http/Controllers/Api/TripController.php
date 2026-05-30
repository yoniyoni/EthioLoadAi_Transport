<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TripCreateRequest;
use App\Http\Requests\TripUpdateStatusRequest;
use App\Http\Requests\TripLocationUpdateRequest;
use App\Models\Booking;
use App\Models\Trip;
use App\Services\TripService;

class TripController extends Controller
{
    protected $tripService;

    public function __construct(TripService $tripService)
    {
        $this->tripService = $tripService;
    }

    public function store(TripCreateRequest $request)
    {
        $booking = Booking::findOrFail($request->booking_id);

        $user = auth()->user();
        if (!$user->is_admin && $booking->driver_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($booking->trip) {
            return response()->json(['message' => 'Trip already exists for this booking'], 400);
        }

        $trip = $this->tripService->startTrip($booking);

        return response()->json([
            'message' => 'Trip started successfully',
            'data' => $trip
        ], 201);
    }

    public function updateStatus(TripUpdateStatusRequest $request, string $id)
    {
        $trip = Trip::findOrFail($id);
        
        $user = auth()->user();
        if (!$user->is_admin && $trip->booking->driver_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($request->trip_status === 'completed') {
            $trip = $this->tripService->completeTrip($trip);
        } else {
            $trip->update(['trip_status' => $request->trip_status]);
        }

        return response()->json(['data' => $trip]);
    }

    public function updateLocation(TripLocationUpdateRequest $request, string $id)
    {
        $trip = Trip::findOrFail($id);
        
        $user = auth()->user();
        if (!$user->is_admin && $trip->booking->driver_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $trip = $this->tripService->updateLocation($trip, $request->validated());

        return response()->json(['data' => $trip]);
    }

    public function show(string $id)
    {
        $trip = Trip::findOrFail($id);

        $user = auth()->user();
        $canView = $user->is_admin
            || $trip->booking->driver_id === $user->id
            || ($trip->booking->cargoRequest && $trip->booking->cargoRequest->user_id === $user->id);

        if (!$canView) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['data' => $trip]);
    }
}
