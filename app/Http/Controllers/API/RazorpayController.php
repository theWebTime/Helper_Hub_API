<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use App\Models\SiteSetting;
use App\Models\Booking;
use App\Models\SubserviceTypeDetail;
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
            'service_id.required' => 'Service is required.',
            'subservice_id.required' => 'Subservice is required.',
            'subservice_type_detail_ids.required' => 'Subservice details are required.',
            'subservice_type_detail_ids.array' => 'Subservice details must be an array.',
            'subservice_type_detail_ids.*.exists' => 'Some subservice detail IDs are invalid.',
            'user_address_id.required' => 'Address is required.',
            'schedule_date.required' => 'Schedule date is required.',
            'schedule_date.date' => 'Schedule date must be valid.',
            'schedule_time.required' => 'Schedule time is required.',
            'is_dog.boolean' => 'Is dog must be true or false.',
            'special_instructions.string' => 'Special instructions must be a string.',
        ];

        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'subservice_id' => 'required|exists:subservices,id',
            'subservice_type_detail_ids' => 'required|array|min:1',
            'subservice_type_detail_ids.*' => 'exists:subservice_type_details,id',
            'user_address_id' => 'required|exists:user_addresses,id',
            'schedule_date' => 'required|date',
            'schedule_time' => 'required',
            'is_dog' => 'nullable|boolean',
            'special_instructions' => 'nullable|string|max:1000',
            'notes' => 'nullable|array',
        ], $messages);

        DB::beginTransaction();
        try {
            // 🔒 Fetch platform fee value from SiteSetting
            $setting = SiteSetting::query()->select('razorpay_key_id', 'razorpay_key_secret', 'platform_fee_value')->first();
            if (!$setting || !$setting->razorpay_key_secret || !$setting->razorpay_key_id) {
                return $this->sendError('Razorpay credentials not configured.');
            }

            // 🔍 Fetch prices from subservice_type_details
            $detailPrices = SubserviceTypeDetail::whereIn('id', $validated['subservice_type_detail_ids'])->pluck('price')->toArray();

            if (count($detailPrices) === 0) {
                return $this->sendError('No valid pricing found for the selected options.');
            }

            $service_price = array_sum($detailPrices);
            $platform_fee = (float) $setting->platform_fee_value;
            $total_amount = $service_price + $platform_fee;

            $booking_number = $this->generateBookingNumber();

            // 📝 Create Booking (no longer includes subservice_type_detail_id)
            $booking = Booking::create([
                'booking_number' => $booking_number,
                'user_id' => auth()->id(),
                'service_id' => $validated['service_id'],
                'subservice_id' => $validated['subservice_id'],
                'user_address_id' => $validated['user_address_id'],
                'service_price' => $service_price,
                'platform_fee' => $platform_fee,
                'total_amount' => $total_amount,
                'schedule_date' => $validated['schedule_date'],
                'schedule_time' => $validated['schedule_time'],
                'schedule_end_date' => $validated['service_id'] == 2
                    ? \Carbon\Carbon::parse($validated['schedule_date'])->addMonth()->toDateString()
                    : null,
                'is_dog' => $validated['is_dog'] ?? false,
                'special_instructions' => $validated['special_instructions'] ?? null,
                'payment_status' => 1,
                'booking_status' => 1,
            ]);

            // 🔗 Attach multiple subservice_type_details to pivot
            $booking->subserviceTypeDetails($validated['subservice_type_detail_ids']);

            // 💳 Create Razorpay order
            $api = new Api($setting->razorpay_key_id, $setting->razorpay_key_secret);

            $orderData = [
                'receipt' => $booking->booking_number,
                'amount' => $total_amount * 100, // in paise
                'currency' => 'INR',
                'payment_capture' => 1,
                'notes' => $validated['notes'] ?? [],
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

    public function adminBookingList(Request $request)
    {
        try {
            $bookings = \DB::table('bookings')
                ->join('users', 'users.id', '=', 'bookings.user_id')
                ->join('services', 'services.id', '=', 'bookings.service_id')
                ->join('subservices', 'subservices.id', '=', 'bookings.subservice_id')
                ->join('user_addresses', 'user_addresses.id', '=', 'bookings.user_address_id')
                ->select(
                    'bookings.id',
                    'bookings.booking_number',
                    'bookings.user_id',
                    'bookings.service_id',
                    'bookings.subservice_id',
                    'bookings.user_address_id',
                    'bookings.service_price',
                    'bookings.platform_fee',
                    'bookings.total_amount',
                    'bookings.schedule_date',
                    'bookings.schedule_time',
                    'bookings.schedule_end_date',
                    'bookings.is_dog',
                    'bookings.special_instructions',
                    'bookings.payment_status',
                    'bookings.payment_id',
                    'bookings.payment_method',
                    'bookings.payment_order_id',
                    'bookings.payment_date',
                    'bookings.booking_status',
                    'bookings.cancellation_reason',
                    'bookings.cancelled_at'
                )
                ->when($request->search, function ($q) use ($request) {
                    $q->where('bookings.booking_number', 'like', '%' . $request->search . '%')
                        ->orWhere('users.name', 'like', '%' . $request->search . '%');
                })
                ->orderByDesc('bookings.id')
                ->paginate($request->itemsPerPage ?? 10);

            // Add subservice type details (label + price) per booking
            $bookings->getCollection()->transform(function ($booking) {
                $details = \DB::table('booking_subservice_type_detail as bstd')
                    ->join('subservice_type_details as std', 'std.id', '=', 'bstd.subservice_type_detail_id')
                    ->where('bstd.booking_id', $booking->id)
                    ->select('std.label', 'std.price')
                    ->get()
                    ->map(function ($d) {
                        return "{$d->label} (₹{$d->price})";
                    })
                    ->implode(', '); // combine into single string

                $booking->selected_type_details = $details;
                return $booking;
            });

            return $this->sendResponse($bookings, 'Booking report fetched successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong!', $e->getMessage());
        }
    }

    public function userBookingList(Request $request)
    {
        try {
            $bookings = \DB::table('bookings')
                // ->join('users', 'users.id', '=', 'bookings.user_id')
                ->join('services', 'services.id', '=', 'bookings.service_id')
                ->join('subservices', 'subservices.id', '=', 'bookings.subservice_id')
                ->join('user_addresses', 'user_addresses.id', '=', 'bookings.user_address_id')
                ->select(
                    'bookings.id',
                    'bookings.booking_number',
                    'bookings.user_id',
                    'bookings.service_id',
                    'services.name as service_name',
                    'bookings.subservice_id',
                    'subservices.name as sub_service_name',
                    'bookings.user_address_id',
                    'user_addresses.address as user_address',
                    'bookings.service_price',
                    'bookings.platform_fee',
                    'bookings.total_amount',
                    'bookings.schedule_date',
                    'bookings.schedule_time',
                    'bookings.schedule_end_date',
                    'bookings.is_dog',
                    'bookings.special_instructions',
                    'bookings.payment_status',
                    'bookings.payment_id',
                    'bookings.payment_method',
                    'bookings.payment_order_id',
                    'bookings.payment_date',
                    'bookings.booking_status',
                    'bookings.cancellation_reason',
                    'bookings.cancelled_at'
                )
                ->when($request->search, function ($q) use ($request) {
                    $q->where('bookings.booking_number', 'like', '%' . $request->search . '%')
                       ;
                })
                ->where('bookings.user_id', auth()->user()->id)
                ->orderByDesc('bookings.id')
                ->paginate($request->itemsPerPage ?? 10);

            // Add subservice type details (label + price) per booking
            $bookings->getCollection()->transform(function ($booking) {
                $details = \DB::table('booking_subservice_type_detail as bstd')
                    ->join('subservice_type_details as std', 'std.id', '=', 'bstd.subservice_type_detail_id')
                    ->where('bstd.booking_id', $booking->id)
                    ->select('std.label', 'std.price')
                    ->get()
                    ->map(function ($d) {
                        return "{$d->label} (₹{$d->price})";
                    })
                    ->implode(', '); // combine into single string

                $booking->selected_type_details = $details;
                return $booking;
            });

            return $this->sendResponse($bookings, 'Booking report fetched successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong!', $e->getMessage());
        }
    }
}