<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $fillable = [
        'booking_id',
        'shipper_rating',
        'driver_rating',
        'feedback',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
