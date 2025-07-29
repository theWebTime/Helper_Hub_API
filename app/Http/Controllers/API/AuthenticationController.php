<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\OtpVerification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Laravel\Passport\Token;
use Illuminate\Validation\Rule;



class AuthenticationController extends BaseController
{

    private function sendWhatsappOtp($mobile, $otp)
    {
        $payload = [
            "integrated_number" => env('SMS_INTEGRATED_NUMBER'), // ✅ your Msg91 integrated number
            "content_type" => "template",
            "payload" => [
                "messaging_product" => "whatsapp",
                "type" => "template",
                "template" => [
                    "name" => env('SMS_TEMPLATE_NAME'), // ✅ your template name
                    "language" => [
                        "code" => "en", // lower case
                        "policy" => "deterministic"
                    ],
                    "namespace" => null,
                    "to_and_components" => [
                        [
                            "to" => ["91{$mobile}"],
                            "components" => [
                                "body_1" => [
                                    "type" => "text",
                                    "value" => $otp
                                ],
                                "button_1" => [
                                    "subtype" => "url",
                                    "type" => "text",
                                    "value" => $otp
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://control.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/bulk/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "authkey: " . env('SMS_AUTH_KEY'), // ✅ your Msg91 auth key
                "content-type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            \Log::error('WhatsApp OTP Send Error: ' . $err);
            return false;
        }

        return true;
    }

    public function sendOtpForRegistration(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'mobile' => [
                    'required',
                    'digits:10',
                    'regex:/^[6-9][0-9]{9}$/',
                    Rule::unique('users', 'mobile'),
                ],
            ], [
                'mobile.required' => 'Mobile number is required.',
                'mobile.digits' => 'Mobile number must be exactly 10 digits.',
                'mobile.regex' => 'Mobile number must start with 6, 7, 8, or 9.',
                'mobile.unique' => 'This mobile number is already registered.',
            ]);

            if ($validator->fails()) {
                // Return only the first error message as a string
                return $this->sendError($validator->errors()->first());
            }

            if(env('APP_ENV') == 'production'){
                $otp = rand(1000, 9999);
            }else{
                $otp = '1234';
            }

            // OTP valid for 2 minute
            $expiresAt = now()->addMinute(2);

            OtpVerification::updateOrCreate(
                [
                    'mobile' => $request->mobile,
                    'type' => 'registration',
                ],
                [
                    'otp' => $otp,
                    'is_verified' => false,
                    'expires_at' => $expiresAt,
                ]
            );
            if(env('APP_ENV') == 'production'){
                $this->sendWhatsappOtp($request->mobile, $otp);
            }

            return $this->sendResponse(
                [],
                'OTP sent successfully. It will expire in 2 minute.',
            );
        } catch (\Exception $e) {
            return $this->sendError('OTP send failed.', ['error' => $e->getMessage()]);
        }
    }

    public function verifyOtpAndRegister(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'mobile' => 'required|string|exists:otp_verifications,mobile',
                'otp' => 'required|string'
            ], [
                'name.required' => 'Name is required.',
                'email.required' => 'Email is required.',
                'email.email' => 'Invalid email format.',
                'email.unique' => 'This email is already registered.',
                'mobile.required' => 'Mobile number is required.',
                'mobile.exists' => 'Mobile number not found for OTP verification.',
                'otp.required' => 'OTP is required.',
            ]);

            if ($validator->fails()) {
                // Return only the first error message as a string
                return $this->sendError($validator->errors()->first());
            }

            $otpRecord = OtpVerification::where([
                'mobile' => $request->mobile,
                'otp' => $request->otp,
                'type' => 'registration',
            ])->first();

            if (!$otpRecord || $otpRecord->is_verified || now()->gt($otpRecord->expires_at)) {
                return $this->sendError('Invalid or expired OTP.');
            }

            // Mark OTP as verified
            $otpRecord->is_verified = true;
            $otpRecord->save();

            // Register user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'is_admin' => 0,
                'status' => 1,
            ]);

            $token = $user->createToken('MyApp')->accessToken;

            return $this->sendResponse([
                'token' => $token,
                'name' => $user->name,
            ], 'User registered successfully.');
        } catch (\Exception $e) {
            return $e;
            return $this->sendError('Registration failed.', ['error' => $e->getMessage()]);
        }
    }

    // 1. Send OTP for User Login
    public function sendOtpForLogin(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'mobile' => [
                    'required',
                    'digits:10',
                    'regex:/^[6-9][0-9]{9}$/',
                    'exists:users,mobile',
                ],
            ], [
                'mobile.required' => 'Mobile number is required.',
                'mobile.digits' => 'Mobile number must be exactly 10 digits.',
                'mobile.regex' => 'Mobile number must start with 6, 7, 8, or 9.',
                'mobile.exists' => 'This mobile number is not registered.',
            ]);

            if ($validator->fails()) {
                // Return only the first error message as a string
                return $this->sendError($validator->errors()->first());
            }

            $user = User::where('mobile', $request->mobile)
                ->where('status', 1)
                ->where('is_admin', 0)
                ->first();

            if (!$user) {
                return $this->sendError('User not found or inactive.');
            }

            if(env('APP_ENV') == 'production'){
                $otp = rand(1000, 9999);
            }else{
                $otp = '1234';
            }
            $expiresAt = now()->addMinute(2);

            OtpVerification::updateOrCreate(
                [
                    'mobile' => $request->mobile,
                    'type' => 'login',
                ],
                [
                    'otp' => $otp,
                    'is_verified' => false,
                    'expires_at' => $expiresAt,
                ]
            );
            if(env('APP_ENV') == 'production'){
                $this->sendWhatsappOtp($request->mobile, $otp);
            }
        
            return $this->sendResponse(
                [],
                'OTP sent successfully. It will expire in 2 minutes.'
            );
        } catch (\Exception $e) {
            return $this->sendError('OTP send failed.', ['error' => $e->getMessage()]);
        }
    }

    // 2. Verify OTP and Login User
    public function verifyOtpAndLogin(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'mobile' => [
                    'required',
                    'digits:10',
                    'regex:/^[6-9][0-9]{9}$/',
                    'exists:users,mobile',
                ],
                'otp' => 'required|string',
            ], [
                'mobile.required' => 'Mobile number is required.',
                'mobile.digits' => 'Mobile number must be exactly 10 digits.',
                'mobile.regex' => 'Mobile number must start with 6, 7, 8, or 9.',
                'mobile.exists' => 'This mobile number is not registered.',
                'otp.required' => 'OTP is required.',
            ]);

            if ($validator->fails()) {
                // Return only the first error message as a string
                return $this->sendError($validator->errors()->first());
            }

            $user = User::where('mobile', $request->mobile)
                ->where('status', 1)
                ->where('is_admin', 0)
                ->first();

            if (!$user) {
                return $this->sendError('User not found or inactive.');
            }

            $otpRecord = OtpVerification::where([
                'mobile' => $request->mobile,
                'otp' => $request->otp,
                'type' => 'login',
            ])->first();

            if (!$otpRecord || $otpRecord->is_verified || now()->gt($otpRecord->expires_at)) {
                return $this->sendError('Invalid or expired OTP.');
            }

            // Mark OTP as verified
            $otpRecord->is_verified = true;
            $otpRecord->save();

            $token = $user->createToken('MyApp')->accessToken;

            return $this->sendResponse([
                'token' => $token,
                'name' => $user->name,
            ], 'User logged in successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Login failed.', ['error' => $e->getMessage()]);
        }
    }


    /**
     * Login API
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                $user = Auth::user();

                if ($user->status != 1) {
                    return $this->sendError('Account is inactive.', ['error' => 'Contact administrator.']);
                }

                $success['token'] = $user->createToken('MyApp')->accessToken;
                $success['name'] = $user->name;
                $success['is_admin'] = $user->is_admin;

                return $this->sendResponse($success, 'User logged in successfully.');
            } else {
                return $this->sendError('Unauthorized.', ['error' => 'Invalid credentials']);
            }
        } catch (\Exception $e) {
            return $this->sendError('Login failed.', ['error' => $e->getMessage()]);
        }
    }


    /**
     * Profile API
     */
    public function profile(): JsonResponse
    {
        try {
            $user = Auth::user();
            return $this->sendResponse($user, 'User profile fetched successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to fetch profile.', ['error' => $e->getMessage()]);
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users', 'email')->ignore($user->id),
                ],
                'password' => 'nullable|min:6|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|max:20',
                'mobile' => [
                    'required',
                    'digits:10',
                    'regex:/^[6-9][0-9]{9}$/',
                    Rule::unique('users', 'mobile')->ignore($user->id),
                ],
            ], [
                'mobile.required' => 'Mobile number is required.',
                'mobile.digits' => 'Mobile number must be exactly 10 digits.',
                'mobile.regex' => 'Mobile number must start with 6, 7, 8, or 9.',
                'mobile.unique' => 'This mobile number is already registered.',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

             if ($request->filled('password')) {
                $user['password'] = bcrypt($request->password);
            }

            $user->name = $request->name;
            $user->email = $request->email;
            $user->mobile = $request->mobile;
            $user->save();

            return $this->sendResponse($user, 'Profile updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Profile update failed.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Logout API
     */
    public function logout()
    {
        try {
            if (Auth::user()) {
                $user = Auth::user()->token();
                $user->revoke();
                return $this->sendResponse([], 'User logout successfully.');
            } else {
                return $this->sendError('Unauthorized.', ['error' => 'Unauthorized']);
            }
        } catch (\Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }
}
