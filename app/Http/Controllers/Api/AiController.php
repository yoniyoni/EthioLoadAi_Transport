<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AiController extends Controller
{
    protected $aiEngine;

    public function __construct(\App\Services\AiEngineService $aiEngine)
    {
        $this->aiEngine = $aiEngine;
    }

    public function recommendTruck(Request $request)
    {
        $result = $this->aiEngine->recommendTruck($request->all());
        return response()->json($result);
    }

    public function backhaulOpportunities(Request $request)
    {
        $payload = $request->all();
        
        // Dynamically fetch pending cargo from the database
        $payload['available_cargo'] = \App\Models\CargoRequest::where('status', 'pending')
            ->get()
            ->map(function ($cargo) {
                return [
                    'cargo_id' => $cargo->id,
                    'pickup_location' => $cargo->pickup_location,
                    'destination' => $cargo->destination,
                    'weight' => (float) $cargo->weight,
                    'price' => (float) $cargo->budget,
                ];
            })->toArray();

        $result = $this->aiEngine->backhaulOpportunities($payload);
        return response()->json($result);
    }

    public function predictPrice(Request $request)
    {
        $result = $this->aiEngine->predictPrice($request->all());
        return response()->json($result);
    }

    public function predictEmptyReturn(Request $request)
    {
        $result = $this->aiEngine->predictEmptyReturn($request->all());
        return response()->json($result);
    }

    public function optimizeRoute(Request $request)
    {
        $result = $this->aiEngine->optimizeRoute($request->all());
        return response()->json($result);
    }
}
