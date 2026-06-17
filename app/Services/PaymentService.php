<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;

class PaymentService
{
    public function processPayment(Booking $booking, array $paymentDetails)
    {
        // For MVP, simulate a successful payment deduction (including commission)
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => $booking->estimated_price,
            'payment_method' => $paymentDetails['payment_method'] ?? 'in_app',
            'payment_status' => 'paid',   // enum: pending | paid | failed
        ]);

        $booking->update(['booking_status' => 'confirmed']);

        return $payment;
    }
}
