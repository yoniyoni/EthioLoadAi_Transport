<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargoRequest extends Model
{
    protected $fillable = [
        'user_id',
        'pickup_location',
        'pickup_latitude',
        'pickup_longitude',
        'destination',
        'material_type',
        'weight',
        'urgency_level',
        'budget',
        'price_type',
        'bid_deadline',
        'status',
    ];

    protected $casts = [
        'bid_deadline' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->hasOne(Booking::class);
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }
}
