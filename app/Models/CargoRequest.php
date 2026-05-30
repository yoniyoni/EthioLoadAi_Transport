<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargoRequest extends Model
{
    protected $fillable = [
        'user_id',
        'pickup_location',
        'destination',
        'material_type',
        'weight',
        'urgency_level',
        'budget',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->hasOne(Booking::class);
    }
}
