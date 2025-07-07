<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Support\Str;
use App\Models\SiteSetting;

class RazorpayController extends BaseController
{
    private function getRazorpayApi(): Api
    {
        $setting = SiteSetting::first(); // Or cache it if needed
        // dd($setting);

        if (!$setting || !$setting->razorpay_key_id || !$setting->razorpay_key_secret) {
            throw new \Exception("Razorpay credentials not configured.");
        }

        return new Api($setting->razorpay_key_id, $setting->razorpay_key_secret);
    }

    public function createOrder(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:1', // amount in INR
                'receipt' => 'nullable|string',
                'notes' => 'nullable|array',
            ]);

            $api = $this->getRazorpayApi();

            $orderData = [
                'receipt'         => $request->receipt ?? Str::uuid(),
                'amount'          => $request->amount * 100, // Convert to paise
                'currency'        => 'INR',
                'payment_capture' => 1,
                'notes'           => $request->notes ?? [],
            ];

            $order = $api->order->create($orderData);

            return $this->sendResponse($order->toArray(), 'Order created successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to create Razorpay order.', ['error' => $e->getMessage()]);
        }
    }

    public function verifySignature(Request $request)
    {
        try {
            $request->validate([
                'razorpay_order_id' => 'required|string',
                'razorpay_payment_id' => 'required|string',
                'razorpay_signature' => 'required|string',
            ]);

            $setting = SiteSetting::first();

            if (!$setting || !$setting->razorpay_key_secret) {
                return $this->sendError('Razorpay secret key not found.');
            }

            $generatedSignature = hash_hmac(
                'sha256',
                $request->razorpay_order_id . "|" . $request->razorpay_payment_id,
                $setting->razorpay_key_secret
            );

            if ($generatedSignature === $request->razorpay_signature) {
                return $this->sendResponse([], 'Payment signature verified successfully.');
            } else {
                return $this->sendError('Payment verification failed.');
            }
        } catch (\Exception $e) {
            return $this->sendError('Error while verifying payment.', ['error' => $e->getMessage()]);
        }
    }
}
