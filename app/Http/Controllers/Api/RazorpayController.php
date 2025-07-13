<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Support\Str;
use App\Models\SiteSetting;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RazorpayController extends BaseController
{
    /**
     * Get only the required columns for Razorpay configuration.
     */
    private function getRazorpayConfig()
    {
        return SiteSetting::query()
            ->select('razorpay_key_id', 'razorpay_key_secret')
            ->first();
    }

    /**
     * Step 1: Create a booking (Pending) and a Razorpay order.
     * Returns: booking, order details, and key for frontend.
     */
    public function createOrder(Request $request)
    {
        // Validation with custom messages
        $messages = [
            'amount.required' => 'Payment amount is required.',
            'amount.numeric' => 'Amount must be a valid number.',
            'user_id.required' => 'User is required.',
            'user_id.exists' => 'User does not exist.',
            'service_id.exists' => 'Service does not exist.',
            'subservice_id.exists' => 'Subservice does not exist.',
            'subservice_type_detail_id.exists' => 'Subservice type detail does not exist.',
            'pin_code_id.exists' => 'Invalid pin code.',
            'customer_name.required' => 'Customer name is required.',
            'customer_mobile.required' => 'Customer mobile is required.',
            'customer_address.required' => 'Customer address is required.',
            'service_price.required' => 'Service price is required.',
            'total_amount.required' => 'Total amount is required.',
            'preferred_date.required' => 'Preferred date is required.',
        ];

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'user_id' => 'required|exists:users,id',
            'service_id' => 'required|exists:services,id',
            'subservice_id' => 'required|exists:subservices,id',
            'subservice_type_detail_id' => 'required|exists:subservice_type_details,id',
            'pin_code_id' => 'required|exists:pin_codes,id',
            'customer_name' => 'required|string|max:255',
            'customer_mobile' => 'required|string|max:20',
            'customer_address' => 'required|string|max:500',
            'service_price' => 'required|numeric|min:0',
            'platform_fee' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'preferred_date' => 'required|date',
            'preferred_time' => 'nullable|string|max:20',
            'special_instructions' => 'nullable|string|max:1000',
            'notes' => 'nullable|array',
        ], $messages);

        DB::beginTransaction();
        try {
            // Generate unique booking number
            $booking_number = $this->generateBookingNumber();

            // Create Booking as Pending
            $booking = Booking::create([
                'booking_number' => $booking_number,
                'user_id' => $validated['user_id'],
                'service_id' => $validated['service_id'],
                'subservice_id' => $validated['subservice_id'],
                'subservice_type_detail_id' => $validated['subservice_type_detail_id'],
                'pin_code_id' => $validated['pin_code_id'],
                'customer_name' => $validated['customer_name'],
                'customer_mobile' => $validated['customer_mobile'],
                'customer_address' => $validated['customer_address'],
                'service_price' => $validated['service_price'],
                'platform_fee' => $validated['platform_fee'] ?? 0,
                'total_amount' => $validated['total_amount'],
                'preferred_date' => $validated['preferred_date'],
                'preferred_time' => $validated['preferred_time'] ?? null,
                'special_instructions' => $validated['special_instructions'] ?? null,
                'payment_status' => 1,
                'booking_status' => 1,
            ]);

            // Get only required Razorpay config
            $setting = $this->getRazorpayConfig();
            if (!$setting || !$setting->razorpay_key_id || !$setting->razorpay_key_secret) {
                DB::rollBack();
                return $this->sendError('Razorpay credentials not configured.');
            }
            $api = new Api($setting->razorpay_key_id, $setting->razorpay_key_secret);

            // Create Razorpay order
            $orderData = [
                'receipt'         => $booking->booking_number,
                'amount'          => $validated['amount'] * 100,
                'currency'        => 'INR',
                'payment_capture' => 1,
                'notes'           => $validated['notes'] ?? [],
            ];
            $order = $api->order->create($orderData);

            // Save Razorpay order id in booking
            $booking->payment_order_id = $order['id'];
            $booking->save();

            DB::commit();

            // Only return necessary fields to frontend
            return $this->sendResponse([
                'booking' => $booking->only([
                    'id', 'booking_number', 'service_price', 'platform_fee', 'total_amount', 'payment_order_id', 'payment_status', 'booking_status'
                ]),
                'order' => [
                    'id' => $order['id'],
                    'amount' => $order['amount'],
                    'currency' => $order['currency'],
                    'receipt' => $order['receipt']
                ],
                'razorpay_key_id' => $setting->razorpay_key_id,
            ], 'Order and booking created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to create booking/order.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Step 2: Verify signature and update booking record.
     * Expects: razorpay_order_id, razorpay_payment_id, razorpay_signature.
     * Finds booking by payment_order_id.
     * Updates payment_status, payment_id, payment_method, payment_date, etc.
     */
    public function verifySignature(Request $request)
    {
        $messages = [
            'razorpay_order_id.required' => 'Order ID is required.',
            'razorpay_payment_id.required' => 'Payment ID is required.',
            'razorpay_signature.required' => 'Payment signature is required.',
        ];

        $validated = $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
            'payment_method' => 'nullable|string|max:30',
        ], $messages);

        try {
            // Only fetch required secret
            $setting = SiteSetting::query()->select('razorpay_key_secret')->first();
            if (!$setting || !$setting->razorpay_key_secret) {
                return $this->sendError('Razorpay secret key not found.');
            }

            $generatedSignature = hash_hmac(
                'sha256',
                $validated['razorpay_order_id'] . "|" . $validated['razorpay_payment_id'],
                $setting->razorpay_key_secret
            );

            // Only select necessary columns
            $booking = Booking::query()
                ->select('id', 'payment_order_id', 'payment_status', 'booking_status')
                ->where('payment_order_id', $validated['razorpay_order_id'])
                ->first();

            if (!$booking) {
                return $this->sendError('Booking not found for this payment.');
            }

            if ($generatedSignature === $validated['razorpay_signature']) {
                // Success: update booking
                $booking->payment_status = 2; // Paid
                $booking->payment_id = $validated['razorpay_payment_id'];
                $booking->payment_method = $validated['payment_method'] ?? 'razorpay';
                $booking->payment_date = Carbon::now();
                $booking->booking_status = 2; // Confirmed
                $booking->save();

                return $this->sendResponse(
                    $booking->only(['id', 'payment_order_id', 'payment_status', 'booking_status']),
                    'Payment signature verified and booking updated successfully.'
                );
            } else {
                // Fail: mark as failed/cancelled
                $booking->payment_status = 3; // Failed
                $booking->booking_status = 5; // Cancelled
                $booking->save();

                return $this->sendError('Payment verification failed. Booking marked as cancelled.');
            }
        } catch (\Exception $e) {
            return $this->sendError('Error while verifying payment.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Generates a unique booking number (e.g., #HH20250001).
     */
    private function generateBookingNumber(): string
    {
        $prefix = "#HH" . date("Y");
        $lastBooking = Booking::withTrashed()
            ->select('booking_number')
            ->where('booking_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();
        $lastNumber = 0;
        if ($lastBooking && preg_match('/(\d+)$/', $lastBooking->booking_number, $matches)) {
            $lastNumber = (int) $matches[1];
        }
        return $prefix . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
    }
}