<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable = [
        'booking_id',
        'route_details',
        'gps_tracking_data',
        'trip_status',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
