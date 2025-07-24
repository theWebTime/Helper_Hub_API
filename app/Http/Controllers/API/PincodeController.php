<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PinCode;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController as BaseController;

class PincodeController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $data = PinCode::select('id', 'pin_code', 'status')->where(function ($query) use ($request) {
                if ($request->search != null) {
                    $query->where('pin_code', 'like', '%' . $request->search . '%');
                }
            })->orderBy('id', 'DESC')->paginate($request->itemsPerPage ?? 10);
            return $this->sendResponse($data, 'Pincode retrieved successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function store(Request $request)
    {
        //Using Try & Catch For Error Handling
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                'pin_code' => 'required|unique:pin_codes,pin_code',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }
            $updateData = (['pin_code' => $input['pin_code']]);
            PinCode::create($updateData);
            return $this->sendResponse([], 'Pincode created successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function show($id)
    {
        //Using Try & Catch For Error Handling
        try {
            $data = PinCode::where('id', $id)->select('id', 'pin_code', 'status')->first();
            return $this->sendResponse($data, 'Pincode retrieved successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function update(Request $request, $id)
    {
        //Using Try & Catch For Error Handling
        try {
            $input = $request->all();
            $validator = Validator::make($input, [
                'pin_code' => 'required|unique:pin_codes,pin_code,' . $id,
                'status' => 'required|in:0,1',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }
            $updateData = (['pin_code' => $input['pin_code'], 'status' => $input['status']]);
            PinCode::where('id', $id)->update($updateData);
            return $this->sendResponse([], 'Pincode updated successfully.');
        } catch (Exception $e) {
            return $e;
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function delete($id)
    {
        //Using Try & Catch For Error Handling
        try {
            DB::table('pin_codes')->where('id', $id)->delete();
            return $this->sendResponse([], 'Pincode deleted successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }
}
