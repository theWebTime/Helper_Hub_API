<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController as BaseController;

class UserController extends BaseController
{
    public function index(Request $request)
    {
        try{
            $data = User::where('is_admin', 'false')->select('id', 'name', 'mobile', 'status')->where(function ($query) use ($request) {
                if ($request->search != null) {
                    $query->where('name', 'like', '%' . $request->search . '%');
                }
            })->orderBy('id', 'ASC')->paginate($request->itemsPerPage ?? 10);

            return $this->sendResponse($data, 'User Data retrieved successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function show($id)
    {
        //Using Try & Catch For Error Handling
        try {
            $data = User::where('id', $id)->select('id', 'name', 'email', 'mobile', 'status')->first();
            return $this->sendResponse($data, 'User Data retrieved successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $input = $request->all();

            $validator = Validator::make($input, [
                'status' => 'required|in:0,1',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            // Get existing user
            $user = User::findOrFail($id);

            // Prepare update data
            $updateData = [
                'status' => $input['status'],
            ];

            // Update user
            $user->update($updateData);

            return $this->sendResponse([], 'User updated successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong!', $e->getMessage());
        }
    }
}
