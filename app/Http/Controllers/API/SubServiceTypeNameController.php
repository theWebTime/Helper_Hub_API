<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubserviceTypeName;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\API\BaseController as BaseController;

class SubServiceTypeNameController extends BaseController
{
    public function list(Request $request)
    {
        try {
            $data = SubserviceTypeName::select('name', 'slug', 'unit_label', 'example')->where(function ($query) use ($request) {
                if ($request->search != null) {
                    $query->where('name', 'like', '%' . $request->search . '%');
                }
            })->orderBy('id', 'ASC')->paginate($request->itemsPerPage ?? 10);

            return $this->sendResponse($data, 'Sub Service Type Names retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong!', $e->getMessage());
        }
    }
}
