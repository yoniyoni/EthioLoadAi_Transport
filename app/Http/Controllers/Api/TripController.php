<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TripCreateRequest;
use App\Http\Requests\TripUpdateStatusRequest;
use App\Http\Requests\TripLocationUpdateRequest;
use App\Jobs\GenerateBackhaulRecommendations;
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

    /**
     * GET /trips  — Admin only: list all trips with booking + cargo + driver info.
     */
    public function index()
    {
        $trips = Trip::with([
            'booking.cargoRequest',
            'booking.driver',
            'booking.vehicle',
            'tripStops.cargoRequest',
        ])->latest()->take(50)->get();

        return response()->json([
            'data' => $trips->map(fn (Trip $trip) => array_merge($trip->toArray(), [
                'total_amount'          => $trip->total_amount,
                'total_amount_formatted' => 'ETB ' . number_format($trip->total_amount, 0, '.', ','),
                'stops'                 => $trip->tripStops->map(fn ($s) => [
                    'id'             => $s->id,
                    'stop_order'     => $s->stop_order,
                    'location_name'  => $s->location_name,
                    'agreed_price'   => $s->agreed_price,
                    'status'         => $s->status,
                    'cargo_material' => $s->cargoRequest?->material_type,
                    'cargo_weight'   => $s->cargoRequest?->weight,
                    'arrived_at'     => $s->arrived_at,
                    'completed_at'   => $s->completed_at,
                ]),
            ]))->values(),
        ]);
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

        // Generate backhaul recommendations asynchronously (non-blocking)
        GenerateBackhaulRecommendations::dispatch($trip);

        return response()->json([
            'message' => 'Trip started successfully',
            'data'    => $trip,
            'backhaul_recommendations_pending' => true,
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
            try {
                $trip = $this->tripService->completeTrip($trip);
            } catch (\Throwable $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
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
