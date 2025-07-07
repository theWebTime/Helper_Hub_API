<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController as BaseController;

class SiteSettingController extends BaseController
{
    public function updateOrCreate(Request $request)
    {
        //Using Try & Catch For Error Handling
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
                'razorpay_key_id' => 'nullable',
                'razorpay_key_secret' => 'nullable',
                'platform_fee_value' => 'nullable',
                'cancellation_charges' => 'nullable',
                'company_name' => 'nullable',
                'address' => 'nullable',
                'phone' => 'nullable',
                'email' => 'nullable',
                'about_us' => 'nullable',
                'terms_conditions' => 'nullable',
                'privacy_policy' => 'nullable',
                'refund_policy' => 'nullable',
                'support_email' => 'nullable',
                'support_phone' => 'nullable',
                'facebook_url' => 'nullable',
                'instagram_url' => 'nullable',
                'twitter_url' => 'nullable',
                'linkedin_url' => 'nullable',
                'youtube_url' => 'nullable',
                'whatsapp_number' => 'nullable',
                'telegram_url' => 'nullable',
                'pinterest_url' => 'nullable',
                'tiktok_url' => 'nullable',
                'snapchat_url' => 'nullable',
                'threads_url' => 'nullable',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }
            $updateData = (['razorpay_key_id' => $input['razorpay_key_id'], 'razorpay_key_secret' => $input['razorpay_key_secret'], 'platform_fee_value' => $input['platform_fee_value'], 'cancellation_charges' => $input['cancellation_charges'], 'company_name' => $input['company_name'], 'address' => $input['address'], 'phone' => $input['phone'], 'email' => $input['email'], 'about_us' => $input['about_us'], 'terms_conditions' => $input['terms_conditions'], 'privacy_policy' => $input['privacy_policy'], 'refund_policy' => $input['refund_policy'], 'support_email' => $input['support_email'], 'support_phone' => $input['support_phone'], 'facebook_url' => $input['facebook_url'], 'instagram_url' => $input['instagram_url'], 'twitter_url' => $input['twitter_url'], 'linkedin_url' => $input['linkedin_url'], 'youtube_url' => $input['youtube_url'], 'whatsapp_number' => $input['whatsapp_number'], 'telegram_url' => $input['telegram_url']]);
            if ($request->file('logo')) {
                $file = $request->file('logo');
                $filename = time() . $file->getClientOriginalName();
                $file->move(public_path('images/siteSetting'), $filename);
                $updateData['logo'] = $filename;
            }
            // Insert or Update Site Setting in site_settings Table
            $data = SiteSetting::updateOrInsert(
                ['id' => 1],
                $updateData
            );
            return $this->sendResponse([], 'Site Setting Updated successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function show()
    {
        // dd(1);
        try {
            $data = SiteSetting::select('id', 'razorpay_key_id',  'razorpay_key_secret', 'platform_fee_value', 'cancellation_charges', 'company_name', 'logo', 'address', 'phone', 'email', 'about_us', 'terms_conditions', 'privacy_policy', 'refund_policy', 'support_email', 'support_phone', 'facebook_url', 'instagram_url', 'twitter_url', 'linkedin_url', 'youtube_url', 'whatsapp_number', 'telegram_url', 'pinterest_url', 'tiktok_url', 'snapchat_url', 'threads_url')->first();
            if (is_null($data)) {
                return $this->sendError('Site Setting not found.');
            }
            return $this->sendResponse($data, 'Site Setting retrieved successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }
}
