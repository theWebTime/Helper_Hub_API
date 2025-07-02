<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Subservice;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\API\BaseController as BaseController;

class SubServiceController extends BaseController
{
    public function serviceList(Request $request)
    {
        try {
            $data = Service::select('id', 'name')->get();
            return $this->sendResponse($data, 'Service retrieved successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function index(Request $request)
    {
        try {
            $data = Subservice::join('services', 'services.id', '=', 'subservices.service_id')->select('subservices.id', 'services.name as service_name', 'subservices.name as sub_service_name')->where(function ($query) use ($request) {
                if ($request->search != null) {
                    $query->where('name', 'like', '%' . $request->search . '%');
                }
            })->orderBy('id', 'DESC')->paginate($request->itemsPerPage ?? 10);

            return $this->sendResponse($data, 'Sub Service retrieved successfully.');
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
                'service_id' => 'required|exists:services,id',
                'type_slugs' => 'required',
                'name' => 'required',
                'description' => 'nullable',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:5120',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }
            $updateData = (['service_id' => $input['service_id'], 'type_slugs' => $input['type_slugs'], 'name' => $input['name'], 'description' => $input['description']]);
            // Handle image upload if provided
            if ($request->file('image')) {
                $file = $request->file('image');
                $filename = time() . $file->getClientOriginalName();
                $file->move(public_path('images/subService'), $filename);
                $updateData['image'] = $filename;
            }
            Subservice::create($updateData);
            return $this->sendResponse([], 'Sub Service created successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function show($id)
    {
        //Using Try & Catch For Error Handling
        try {
            $data = Subservice::where('id', $id)->select('id', 'service_id', 'type_slugs', 'name', 'description', 'image', 'status')->first();
            return $this->sendResponse($data, 'Sub Service retrieved successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $input = $request->all();

            $validator = Validator::make($input, [
                'service_id' => 'required|exists:services,id',
                'type_slugs' => 'required',
                'name' => 'required',
                'description' => 'nullable',
                'status' => 'nullable',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:5120',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            // Get existing user
            $user = Subservice::findOrFail($id);

            // Prepare update data
            $updateData = (['service_id' => $input['service_id'], 'type_slugs' => $input['type_slugs'], 'name' => $input['name'], 'description' => $input['description']
            , 'status' => $input['status']]);

            // Handle image upload if provided
            if ($request->file('image')) {
                $file = $request->file('image');
                $filename = time() . $file->getClientOriginalName();
                $file->move(public_path('images/subService'), $filename);
                $updateData['image'] = $filename;
            }

            // Update user
            $user->update($updateData);

            return $this->sendResponse([], 'Service updated successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong!', $e->getMessage());
        }
    }

    public function delete($id)
    {
        //Using Try & Catch For Error Handling
        try {
            DB::table('subservices')->where('id', $id)->delete();
            return $this->sendResponse([], 'Sub Service deleted successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }
}
