<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookingCreateRequest;
use App\Http\Requests\BookingUpdateRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    protected $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->is_admin) {
            return BookingResource::collection(Booking::all());
        }

        if ($user->role === 'driver') {
            return BookingResource::collection(Booking::where('driver_id', $user->id)->get());
        }

        return BookingResource::collection(
            Booking::whereHas('cargoRequest', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BookingCreateRequest $request)
    {
        $booking = $this->bookingService->createBooking($request->validated());
        return (new BookingResource($booking))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $booking = Booking::findOrFail($id);
        $user = auth()->user();

        if (!$user->is_admin
            && $booking->driver_id !== $user->id
            && (! $booking->cargoRequest || $booking->cargoRequest->user_id !== $user->id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return new BookingResource($booking);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BookingUpdateRequest $request, string $id)
    {
        $booking = Booking::findOrFail($id);
        $user = auth()->user();

        $canUpdate = $user->is_admin
            || $booking->driver_id === $user->id
            || ($booking->cargoRequest && $booking->cargoRequest->user_id === $user->id);

        if (!$canUpdate) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $booking = $this->bookingService->updateBooking($booking, $request->validated());
        return new BookingResource($booking);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $booking = Booking::findOrFail($id);
        $user = auth()->user();

        $canDelete = $user->is_admin
            || $booking->driver_id === $user->id
            || ($booking->cargoRequest && $booking->cargoRequest->user_id === $user->id);

        if (!$canDelete) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $this->bookingService->deleteBooking($booking);
        return response()->json(['message' => 'Booking deleted successfully']);
    }
}
