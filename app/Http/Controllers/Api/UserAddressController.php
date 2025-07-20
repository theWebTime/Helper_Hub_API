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
    public function pincodeList()
    {
        try{
            $data = PinCode::where('status', '=', 1)->select('id', 'pin_code')->get();
            return $this->sendResponse($data, 'Pincode retrieved successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $data = UserAddress::where('user_id', auth()->id())->join('pin_codes', 'pin_codes.id', '=', 'user_addresses.pin_code_id')
                ->select('user_addresses.id', 'pin_codes.pin_code', 'type', 'title', 'name', 'phone', 'address', 'landmark')
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
                'title' => 'required|string',
                'name' => 'required|string',
                'phone' => 'required|string',
                'landmark' => 'required|string',
                'is_default' => 'required|boolean',
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
                'title' => 'required|string',
                'name' => 'required|string',
                'phone' => 'required|string',
                'landmark' => 'required|string',
                'is_default' => 'required|boolean',
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

    public function delete($id): JsonResponse
    {
        try {
            $address = UserAddress::where('id', $id)->where('user_id', auth()->id())->firstOrFail();
            $address->delete();
            return $this->sendResponse([], 'Address deleted successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong!', $e->getMessage());
        }
    }

    public function userAddressList(Request $request)
    {
        try {
            $data = UserAddress::join('users', 'users.id', '=', 'user_addresses.user_id')->join('pin_codes', 'pin_codes.id', '=', 'user_addresses.pin_code_id')->select('user_addresses.id', 'users.name as user_name', 'pin_codes.pin_code', 'user_addresses.type', 'user_addresses.title', 'user_addresses.name as pickup_person_name', 'user_addresses.phone', 'user_addresses.address', 'user_addresses.landmark', 'user_addresses.is_default')->where(function ($query) use ($request) {
                if ($request->search != null) {
                    $query->where('user_name', 'like', '%' . $request->search . '%');
                }
            })->orderBy('id', 'DESC')->paginate($request->itemsPerPage ?? 10);

            return $this->sendResponse($data, 'User Addresses retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong!', $e->getMessage());
        }
    }
}
