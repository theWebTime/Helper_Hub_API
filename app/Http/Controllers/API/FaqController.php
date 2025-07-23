<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faq;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\API\BaseController as BaseController;

class FaqController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $data = Faq::select('id', 'question', 'answer')->where(function ($query) use ($request) {
                if ($request->search != null) {
                    $query->where('question', 'like', '%' . $request->search . '%');
                }
            })->orderBy('id', 'ASC')->paginate($request->itemsPerPage ?? 10);
            return $this->sendResponse($data, 'FAQ retrieved successfully.');
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
                'question' => 'required',
                'answer' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }
            $updateData = (['question' => $input['question'], 'answer' => $input['answer']]);
            Faq::create($updateData);
            return $this->sendResponse([], 'FAQ created successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function show($id)
    {
        //Using Try & Catch For Error Handling
        try {
            $data = Faq::where('id', $id)->select('id', 'question', 'answer')->first();
            return $this->sendResponse($data, 'FAQ retrieved successfully.');
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
                'question' => 'required',
                'answer' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }
            $updateData = (['question' => $input['question'], 'answer' => $input['answer']]);
            Faq::where('id', $id)->update($updateData);
            return $this->sendResponse([], 'FAQ updated successfully.');
        } catch (Exception $e) {
            return $e;
            return $this->sendError('something went wrong!', $e);
        }
    }

    public function delete($id)
    {
        //Using Try & Catch For Error Handling
        try {
            DB::table('faqs')->where('id', $id)->delete();
            return $this->sendResponse([], 'FAQ deleted successfully.');
        } catch (Exception $e) {
            return $this->sendError('something went wrong!', $e);
        }
    }
    
}
