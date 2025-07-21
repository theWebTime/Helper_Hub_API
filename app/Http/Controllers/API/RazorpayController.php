<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use App\Models\SiteSetting;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RazorpayController extends BaseController
{
    private function getRazorpayConfig()
    {
        return SiteSetting::query()
            ->select('razorpay_key_id', 'razorpay_key_secret')
            ->first();
    }

    /**
     * Create booking and Razorpay order
     */
    public function createOrder(Request $request)
    {
        $messages = [
            'amount.required' => 'Payment amount is required.',
            'amount.numeric' => 'Amount must be a valid number.',
            'service_id.required' => 'Service is required.',
            'service_id.exists' => 'Service does not exist.',
            'subservice_id.required' => 'Subservice is required.',
            'subservice_id.exists' => 'Subservice does not exist.',
            'subservice_type_detail_id.required' => 'Subservice type detail is required.',
            'subservice_type_detail_id.exists' => 'Subservice type detail does not exist.',
            'user_address_id.required' => 'Address is required.',
            'user_address_id.exists' => 'Invalid address.',
            'service_price.required' => 'Service price is required.',
            'platform_fee.numeric' => 'Platform fee must be a number.',
            'total_amount.required' => 'Total amount is required.',
            'total_amount.numeric' => 'Total amount must be a number.',
            'schedule_start.required' => 'Schedule start is required.',
            'schedule_start.date' => 'Schedule start must be a valid date.',
            'schedule_end.date' => 'Schedule end must be a valid date.',
            'is_dog.boolean' => 'Is dog must be true or false.',
            'special_instructions.string' => 'Special instructions must be a string.',
        ];

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'service_id' => 'required|exists:services,id',
            'subservice_id' => 'required|exists:subservices,id',
            'subservice_type_detail_id' => 'required|exists:subservice_type_details,id',
            'user_address_id' => 'required|exists:user_addresses,id',
            'service_price' => 'required|numeric|min:0',
            'platform_fee' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'schedule_start' => 'required|date',
            'schedule_end' => 'nullable|date',
            'is_dog' => 'nullable|boolean',
            'special_instructions' => 'nullable|string|max:1000',
            'notes' => 'nullable|array',
        ], $messages);

        DB::beginTransaction();
        try {
            $booking_number = $this->generateBookingNumber();

            $booking = Booking::create([
                'booking_number' => $booking_number,
                'user_id' => auth()->id(), // Authenticated user
                'service_id' => $validated['service_id'],
                'subservice_id' => $validated['subservice_id'],
                'subservice_type_detail_id' => $validated['subservice_type_detail_id'],
                'user_address_id' => $validated['user_address_id'],
                'service_price' => $validated['service_price'],
                'platform_fee' => $validated['platform_fee'] ?? 0,
                'total_amount' => $validated['total_amount'],
                'schedule_start' => $validated['schedule_start'],
                'schedule_end' => $validated['schedule_end'] ?? null,
                'is_dog' => $validated['is_dog'] ?? false,
                'special_instructions' => $validated['special_instructions'] ?? null,
                'payment_status' => 1,
                'booking_status' => 1,
            ]);

            $setting = $this->getRazorpayConfig();
            if (!$setting || !$setting->razorpay_key_id || !$setting->razorpay_key_secret) {
                DB::rollBack();
                return $this->sendError('Razorpay credentials not configured.');
            }
            $api = new Api($setting->razorpay_key_id, $setting->razorpay_key_secret);

            $orderData = [
                'receipt'         => $booking->booking_number,
                'amount'          => $validated['amount'] * 100,
                'currency'        => 'INR',
                'payment_capture' => 1,
                'notes'           => $validated['notes'] ?? [],
            ];
            $order = $api->order->create($orderData);

            $booking->payment_order_id = $order['id'];
            $booking->save();

            DB::commit();

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
     * Verify payment and update booking
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
            $setting = SiteSetting::query()->select('razorpay_key_secret')->first();
            if (!$setting || !$setting->razorpay_key_secret) {
                return $this->sendError('Razorpay secret key not found.');
            }

            $generatedSignature = hash_hmac(
                'sha256',
                $validated['razorpay_order_id'] . "|" . $validated['razorpay_payment_id'],
                $setting->razorpay_key_secret
            );

            $booking = Booking::where('payment_order_id', $validated['razorpay_order_id'])->first();

            if (!$booking) {
                return $this->sendError('Booking not found for this payment.');
            }

            if ($generatedSignature === $validated['razorpay_signature']) {
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
                $booking->payment_status = 3; // Failed
                $booking->booking_status = 5; // Cancelled
                $booking->save();

                return $this->sendError('Payment verification failed. Booking marked as cancelled.');
            }
        } catch (\Exception $e) {
            return $this->sendError('Error while verifying payment.', ['error' => $e->getMessage()]);
        }
    }

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