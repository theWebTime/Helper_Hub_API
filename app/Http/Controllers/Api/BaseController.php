<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Exception;

class AuthenticationController extends BaseController
{
    /**
     * Register a new user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        try {
            // Validate request data
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|min:2',
                'email' => 'required|email|unique:users,email|max:255',
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                ],
                'c_password' => 'required|same:password',
            ], [
                'name.required' => 'Name is required',
                'name.min' => 'Name must be at least 2 characters',
                'email.required' => 'Email is required',
                'email.email' => 'Please provide a valid email address',
                'email.unique' => 'This email is already registered',
                'password.required' => 'Password is required',
                'password.min' => 'Password must be at least 8 characters',
                'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character',
                'c_password.required' => 'Password confirmation is required',
                'c_password.same' => 'Password confirmation does not match',
            ]);

            // Create user
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
            ]);

            // Generate token
            $token = $user->createToken('MyApp')->accessToken;

            $response = [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
            ];

            return $this->sendResponse($response, 'User registered successfully.');

        } catch (ValidationException $e) {
            return $this->sendError('Validation Error.', $e->errors(), 422);

        } catch (Exception $e) {
            return $this->sendError('Registration failed. Please try again.', [], 500);
        }
    }

    /**
     * Login user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Validate request data
            $validatedData = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ], [
                'email.required' => 'Email is required',
                'email.email' => 'Please provide a valid email address',
                'password.required' => 'Password is required',
            ]);

            // Attempt authentication
            if (Auth::attempt($validatedData)) {
                $authenticatedUser = Auth::user();
                
                // Generate token
                $token = $authenticatedUser->createToken('MyApp')->accessToken;

                $response = [
                    'token' => $token,
                    'user' => [
                        'id' => $authenticatedUser->id,
                        'name' => $authenticatedUser->name,
                        'email' => $authenticatedUser->email,
                    ]
                ];

                return $this->sendResponse($response, 'User logged in successfully.');
            }

            return $this->sendError('Invalid credentials.', ['error' => 'Invalid email or password'], 401);

        } catch (ValidationException $e) {
            return $this->sendError('Validation Error.', $e->errors(), 422);

        } catch (Exception $e) {
            return $this->sendError('Login failed. Please try again.', [], 500);
        }
    }

    /**
     * Logout user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if ($user) {
                // Revoke current token
                $user->token()->revoke();
                
                return $this->sendResponse([], 'User logged out successfully.');
            }

            return $this->sendError('User not authenticated.', [], 401);

        } catch (Exception $e) {
            return $this->sendError('Logout failed. Please try again.', [], 500);
        }
    }

    /**
     * Get authenticated user profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return $this->sendError('User not authenticated.', [], 401);
            }

            $response = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ];

            return $this->sendResponse($response, 'User profile retrieved successfully.');

        } catch (Exception $e) {
            return $this->sendError('Failed to retrieve profile. Please try again.', [], 500);
        }
    }
}