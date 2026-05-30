<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'cargo_id',
        'vehicle_id',
        'driver_id',
        'booking_status',
        'estimated_price',
        'commission_fee',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Booking belongs to Cargo Request
    public function cargoRequest()
    {
        return $this->belongsTo(CargoRequest::class);
    }

    // Booking belongs to Vehicle
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    // Booking belongs to Driver (User)
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    // Booking has one Trip
    public function trip()
    {
        return $this->hasOne(Trip::class);
    }

    // Booking has one Payment
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    // Booking has one Rating
    public function rating()
    {
        return $this->hasOne(Rating::class);
    }
}