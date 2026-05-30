<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VehicleLocationUpdateRequest;
use App\Http\Requests\VehicleRegisterRequest;
use App\Http\Requests\VehicleUpdateRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return VehicleResource::collection(Vehicle::all());
    }

    /**
     * Register a new vehicle.
     */
    public function register(VehicleRegisterRequest $request)
    {
        $validated = $request->validated();
        $vehicle = Vehicle::create(array_merge($validated, [
            'user_id' => auth()->id(),
            'availability_status' => $validated['availability_status'] ?? 'available',
            'rating' => $validated['rating'] ?? 0,
        ]));

        return (new VehicleResource($vehicle))->response()->setStatusCode(201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VehicleRegisterRequest $request)
    {
        return $this->register($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
       return new VehicleResource(Vehicle::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(VehicleUpdateRequest $request, string $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $user = auth()->user();

        if (!$user->is_admin && $vehicle->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $vehicle->update($request->validated());
        return new VehicleResource($vehicle);
    }

    /**
     * Update the vehicle GPS location.
     */
    public function updateLocation(VehicleLocationUpdateRequest $request, string $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $user = auth()->user();

        if (!$user->is_admin && $vehicle->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $vehicle->update($request->validated());
        return new VehicleResource($vehicle);
    }

    /**
     * Display nearby available vehicles.
     */
    public function nearby(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_km' => 'sometimes|numeric|min:1',
        ]);

        $radiusKm = $validated['radius_km'] ?? 50;
        $latitude = $validated['latitude'];
        $longitude = $validated['longitude'];

        $latDelta = $radiusKm / 110;
        $lngDelta = $radiusKm / (111 * max(cos(deg2rad($latitude)), 0.0001));

        $vehicles = Vehicle::where('availability_status', 'available')
            ->whereBetween('latitude', [$latitude - $latDelta, $latitude + $latDelta])
            ->whereBetween('longitude', [$longitude - $lngDelta, $longitude + $lngDelta])
            ->get();

        return VehicleResource::collection($vehicles);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $user = auth()->user();

        if (!$user->is_admin && $vehicle->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $vehicle->delete();
        return response()->json(['message' => 'Vehicle deleted successfully']);
    }
}
