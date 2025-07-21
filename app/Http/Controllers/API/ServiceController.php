<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController as BaseController;

class ServiceController extends BaseController
{
    public function index(Request $request)
    {
        try{
            $data = Service::select('id', 'name', 'status')->where(function ($query) use ($request) {
                if ($request->search != null) {
                    $query->where('name', 'like', '%' . $request->search . '%');
                }
            })->orderBy('id', 'ASC')->paginate($request->itemsPerPage ?? 10);

            return $this->sendResponse($data, 'Service Data retrieved successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function show($id)
    {
        //Using Try & Catch For Error Handling
        try {
            $data = Service::where('id', $id)->select('id', 'name', 'description', 'image', 'status')->first();
            return $this->sendResponse($data, 'Service Data retrieved successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $input = $request->all();

            $validator = Validator::make($input, [
                'image' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:5120',
                'name' => 'required|max:100',
                'description' => 'nullable',
                'status' => 'required|in:0,1',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            // Get existing user
            $user = Service::findOrFail($id);

            // Prepare update data
            $updateData = [
                'name' => $input['name'],
                'description' => $input['description'],
                'status' => $input['status'],
            ];

            // Handle image upload if provided
            if ($request->file('image')) {
                $file = $request->file('image');
                $filename = time() . $file->getClientOriginalName();
                $file->move(public_path('images/service'), $filename);
                $updateData['image'] = $filename;
            }

            // Update user
            $user->update($updateData);

            return $this->sendResponse([], 'Service updated successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong!', $e->getMessage());
        }
    }
}
