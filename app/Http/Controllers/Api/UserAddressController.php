<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\UserAddress;
use App\Models\PinCode;
use Illuminate\Http\JsonResponse;

class UserAddressController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $data = UserAddress::where('user_id', auth()->id())
                ->when($request->search, function ($query) use ($request) {
                    $query->where('address', 'like', "%{$request->search}%");
                })
                ->orderBy('id', 'DESC')
                ->paginate($request->itemsPerPage ?? 10);

            return $this->sendResponse($data, 'User addresses retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong!', $e->getMessage());
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'pin_code_id' => 'required|exists:pin_codes,id',
                'type' => 'required|in:home,office,other',
                'address' => 'required|string',
                'title' => 'nullable|string',
                'name' => 'nullable|string',
                'phone' => 'nullable|string',
                'landmark' => 'nullable|string',
                'is_default' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $activePin = PinCode::where('id', $request->pin_code_id)->where('status', true)->first();
            if (!$activePin) {
                return $this->sendError('Selected pincode is not serviceable.');
            }

            if ($request->is_default) {
                UserAddress::where('user_id', auth()->id())->update(['is_default' => false]);
            }

            $address = UserAddress::create([
                'user_id' => auth()->id(),
                'pin_code_id' => $request->pin_code_id,
                'type' => $request->type,
                'title' => $request->title,
                'name' => $request->name,
                'phone' => $request->phone,
                'address' => $request->address,
                'landmark' => $request->landmark,
                'is_default' => $request->is_default ?? false,
            ]);

            return $this->sendResponse($address, 'Address added successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong!', $e->getMessage());
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $address = UserAddress::where('id', $id)->where('user_id', auth()->id())->firstOrFail();
            return $this->sendResponse($address, 'Address fetched successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Address not found.', $e->getMessage());
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $address = UserAddress::where('id', $id)->where('user_id', auth()->id())->firstOrFail();

            $validator = Validator::make($request->all(), [
                'pin_code_id' => 'required|exists:pin_codes,id',
                'type' => 'required|in:home,office,other',
                'address' => 'required|string',
                'title' => 'nullable|string',
                'name' => 'nullable|string',
                'phone' => 'nullable|string',
                'landmark' => 'nullable|string',
                'is_default' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $activePin = PinCode::where('id', $request->pin_code_id)->where('status', true)->first();
            if (!$activePin) {
                return $this->sendError('Selected pincode is not serviceable.');
            }

            if ($request->is_default) {
                UserAddress::where('user_id', auth()->id())->update(['is_default' => false]);
            }

            $address->update([
                'pin_code_id' => $request->pin_code_id,
                'type' => $request->type,
                'title' => $request->title,
                'name' => $request->name,
                'phone' => $request->phone,
                'address' => $request->address,
                'landmark' => $request->landmark,
                'is_default' => $request->is_default ?? false,
            ]);

            return $this->sendResponse($address, 'Address updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong!', $e->getMessage());
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $address = UserAddress::where('id', $id)->where('user_id', auth()->id())->firstOrFail();
            $address->delete();
            return $this->sendResponse([], 'Address deleted successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong!', $e->getMessage());
        }
    }
}
