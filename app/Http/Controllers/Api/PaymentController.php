<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentProcessRequest;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function store(PaymentProcessRequest $request)
    {
        $booking = Booking::findOrFail($request->booking_id);

        // Basic authorization - only the shipper or driver can process the payment (or admin)
        $user = auth()->user();
        $canPay = $user->is_admin
            || $booking->driver_id === $user->id
            || ($booking->cargoRequest && $booking->cargoRequest->user_id === $user->id);

        if (!$canPay) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Check if payment already exists
        if ($booking->payment) {
            return response()->json(['message' => 'Payment already processed for this booking'], 400);
        }

        $payment = $this->paymentService->processPayment($booking, $request->validated());

        return response()->json([
            'message' => 'Payment processed successfully',
            'data' => $payment
        ], 201);
    }

    public function show(string $booking_id)
    {
        $booking = Booking::with('payment')->findOrFail($booking_id);

        $user = auth()->user();
        $canView = $user->is_admin
            || $booking->driver_id === $user->id
            || ($booking->cargoRequest && $booking->cargoRequest->user_id === $user->id);

        if (!$canView) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!$booking->payment) {
            return response()->json(['message' => 'No payment found for this booking'], 404);
        }

        return response()->json(['data' => $booking->payment]);
    }
}
