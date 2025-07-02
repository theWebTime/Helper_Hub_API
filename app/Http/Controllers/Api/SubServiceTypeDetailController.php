<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubserviceTypeName;
use App\Models\SubserviceTypeDetail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\API\BaseController as BaseController;

class SubServiceTypeDetailController extends BaseController
{
    public function listIndex(Request $request)
    {
        try {
            $data = SubserviceTypeName::select('name', 'slug')->get();
            return $this->sendResponse($data, 'Sub Service Type Slug retrieved successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function index(Request $request)
    {
        try {
            $data = SubserviceTypeDetail::select('id', 'subservice_type_name_slug', 'label', 'price')->where(function ($query) use ($request) {
                if ($request->search != null) {
                    $query->where('label', 'like', '%' . $request->search . '%');
                }
            })->orderBy('id', 'DESC')->paginate($request->itemsPerPage ?? 10);

            return $this->sendResponse($data, 'Sub Service Type Details retrieved successfully.');
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
                'subservice_type_name_slug' => 'required|exists:subservice_type_names,slug',
                'label' => 'required',
                'price' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }
            $updateData = (['subservice_type_name_slug' => $input['subservice_type_name_slug'], 'label' => $input['label'], 'price' => $input['price']]);
            SubserviceTypeDetail::create($updateData);
            return $this->sendResponse([], 'Sub Service Type Details created successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function show($id)
    {
        //Using Try & Catch For Error Handling
        try {
            $data = SubserviceTypeDetail::where('id', $id)->select('id', 'subservice_type_name_slug', 'label', 'price')->first();
            return $this->sendResponse($data, 'Sub Service Type Details retrieved successfully.');
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
                'subservice_type_name_slug' => 'required|exists:subservice_type_names,slug',
                'label' => 'required',
                'price' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }
            $updateData = (['subservice_type_name_slug' => $input['subservice_type_name_slug'], 'label' => $input['label'], 'price' => $input['price']]);
            SubserviceTypeDetail::where('id', $id)->update($updateData);
            return $this->sendResponse([], 'Sub Service Type Details updated successfully.');
        } catch (Exception $e) {
            return $e;
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function delete($id)
    {
        //Using Try & Catch For Error Handling
        try {
            DB::table('subservice_type_details')->where('id', $id)->delete();
            return $this->sendResponse([], 'Sub Service Type Details deleted successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }
}
