<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CargoCreateRequest;
use App\Http\Requests\CargoUpdateRequest;
use App\Http\Resources\CargoResource;
use App\Models\CargoRequest;
use Illuminate\Http\Request;

class CargoRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return CargoResource::collection(CargoRequest::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CargoCreateRequest $request)
    {
        $validated = $request->validated();
        $cargoRequest = CargoRequest::create(array_merge($validated, [
            'user_id' => auth()->id(),
            'status' => $validated['status'] ?? 'pending',
        ]));

        return (new CargoResource($cargoRequest))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return new CargoResource(CargoRequest::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CargoUpdateRequest $request, string $id)
    {
        $cargoRequest = CargoRequest::findOrFail($id);
        $user = auth()->user();

        if (!$user->is_admin && $cargoRequest->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cargoRequest->update($request->validated());
        return new CargoResource($cargoRequest);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cargoRequest = CargoRequest::findOrFail($id);
        $user = auth()->user();

        if (!$user->is_admin && $cargoRequest->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cargoRequest->delete();
        return response()->json(['message' => 'Cargo request deleted successfully']);
    }
}
