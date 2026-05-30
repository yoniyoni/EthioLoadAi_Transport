<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cargo_id' => $this->cargo_id,
            'vehicle_id' => $this->vehicle_id,
            'driver_id' => $this->driver_id,
            'booking_status' => $this->booking_status,
            'estimated_price' => $this->estimated_price,
            'commission_fee' => $this->commission_fee,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
