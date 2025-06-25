<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Laravel\Passport\Token;

class AuthenticationController extends BaseController
{
    /**
     * Register API
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'mobile' => 'required|string|unique:users,mobile',
                'password' => 'required|string|min:6',
                'c_password' => 'required|same:password',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $input = $request->only(['name', 'email', 'mobile', 'password']);
            $input['password'] = bcrypt($input['password']);
            $input['is_admin'] = 0; // Default
            $input['status'] = 1;   // Active by default

            $user = User::create($input);

            $success['token'] = $user->createToken('MyApp')->accessToken;
            $success['name'] = $user->name;

            return $this->sendResponse($success, 'User registered successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Registration failed.', ['error' => $e->getMessage()]);
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
                'email' => 'required|email|unique:users,email,' . $user->id,
                'mobile' => 'required|string|unique:users,mobile,' . $user->id,
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
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
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function admin(Request $request): JsonResponse
    {
        dd("working");
        return "working";
    }
    public function user(Request $request): JsonResponse
    {
        dd("working");

        return "working";

    }
}
