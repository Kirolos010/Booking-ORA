<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Services\BookingService;
use Illuminate\Support\Facades\Auth;
use Throwable;

class BookingController extends Controller
{
    public function __construct(
        private BookingService $service
    ) {}

    public function store(StoreBookingRequest $request)
    {
        try {

            $booking = $this->service->create(
                $request->validated() + [
                    'user_id' => Auth::id(),
                ]
            );

            return response()->json([
                'message' => 'Booking created successfully',
                'data' => $booking,
            ], 201);

        } catch (Throwable $e) {

            return response()->json([
                'message' => 'Failed to create booking.',
                'error' => $e->getMessage(),
            ], 500);

        }
    }

    public function checkout($booking_ref)
    {
        try {

            $booking = $this->service->checkout($booking_ref);

            return response()->json([
                'message' => 'Booking checked out successfully',
                'data' => $booking,
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'message' => 'Checkout failed.',
                'error' => $e->getMessage(),
            ], 500);

        }
    }
}
