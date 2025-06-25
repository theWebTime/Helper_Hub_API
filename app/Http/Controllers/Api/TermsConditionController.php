<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TermsCondition;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\API\BaseController as BaseController;

class TermsConditionController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $data = TermsCondition::select('id', 'title', 'description')->where(function ($query) use ($request) {
                if ($request->search != null) {
                    $query->where('title', 'like', '%' . $request->search . '%');
                }
            })->orderBy('id', 'DESC')->paginate($request->itemsPerPage ?? 10);
            return $this->sendResponse($data, 'Terms & Condition retrieved successfully.');
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
                'title' => 'required',
                'description' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }
            $updateData = (['title' => $input['title'], 'description' => $input['description']]);
            TermsCondition::create($updateData);
            return $this->sendResponse([], 'Terms & Condition created successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function show($id)
    {
        //Using Try & Catch For Error Handling
        try {
            $data = TermsCondition::where('id', $id)->select('id', 'title', 'description')->first();
            return $this->sendResponse($data, 'Terms & Condition retrieved successfully.');
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
                'title' => 'required',
                'description' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }
            $updateData = (['title' => $input['title'], 'description' => $input['description']]);
            TermsCondition::where('id', $id)->update($updateData);
            return $this->sendResponse([], 'Terms & Condition updated successfully.');
        } catch (Exception $e) {
            return $e;
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function delete($id)
    {
        //Using Try & Catch For Error Handling
        try {
            DB::table('terms_conditions')->where('id', $id)->delete();
            return $this->sendResponse([], 'Terms & Condition deleted successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }
}
