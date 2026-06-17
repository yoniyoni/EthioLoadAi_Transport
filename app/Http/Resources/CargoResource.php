<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CargoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        // Hide budget from drivers — sealed-bid: drivers should not anchor to the shipper's price
        $showBudget = true;

        return [
            'id'              => $this->id,
            'user_id'         => $this->user_id,
            'pickup_location' => $this->pickup_location,
            'destination'     => $this->destination,
            'material_type'   => $this->material_type,
            'weight'          => $this->weight,
            'urgency_level'   => $this->urgency_level,
            'budget'          => $showBudget ? $this->budget : null,
            'price_type'      => $this->price_type ?? 'negotiable',
            'status'          => $this->status,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
