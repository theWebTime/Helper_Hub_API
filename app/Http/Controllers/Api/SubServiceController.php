<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Subservice;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\SubserviceTypeName;
use App\Models\SubserviceTypeDetail;
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

    public function randomSubServiceList(Request $request)
    {
        try {
            $data = Subservice::select('id', 'name')->inRandomOrder()->limit(4)->get();
            return $this->sendResponse($data, 'Sub Service retrieved successfully.');
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
            $updateData = ([
                'service_id' => $input['service_id'], 'type_slugs' => $input['type_slugs'], 'name' => $input['name'], 'description' => $input['description'], 'status' => $input['status']
            ]);

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

    public function getSubservicesByServiceId($serviceId)
    {
        try {
            $rawResults = DB::table('subservices')
                ->join('services', 'subservices.service_id', '=', 'services.id')
                ->leftJoin('subservice_type_names', function ($join) {
                    // match every slug that appears in type_slugs
                    $join->whereRaw("FIND_IN_SET(subservice_type_names.slug, subservices.type_slugs)");
                })
                ->leftJoin('subservice_type_details', function ($join) use ($serviceId) {
                    $join->on('subservice_type_details.subservice_type_name_slug', '=', 'subservice_type_names.slug')
                        ->where('subservice_type_details.service_id', '=', $serviceId);   // 👈 NEW
                })
                ->where('subservices.service_id', $serviceId)       // still limit subservices themselves
                ->where('subservices.status', true)
                ->select(
                    'subservices.id as subservice_id',
                    'subservices.name as subservice_name',
                    'subservices.description',
                    'subservices.image',
                    'subservices.type_slugs',

                    'subservice_type_names.slug as type_slug',
                    'subservice_type_names.name as type_name',
                    'subservice_type_names.unit_label',
                    'subservice_type_names.example',

                    'subservice_type_details.label as detail_label',
                    'subservice_type_details.price as detail_price'
                )
                ->get();

            if ($rawResults->isEmpty()) {
                return $this->sendError('No subservices found for this service ID.');
            }

            // Group results
            $grouped = [];
            foreach ($rawResults as $row) {
                $subId = $row->subservice_id;

                if (!isset($grouped[$subId])) {
                    $grouped[$subId] = [
                        'id' => $subId,
                        'name' => $row->subservice_name,
                        'description' => $row->description,
                        'image' => $row->image,
                        'type_slugs' => $row->type_slugs,
                        'types' => [],
                    ];
                }

                if ($row->type_slug) {
                    if (!isset($grouped[$subId]['types'][$row->type_slug])) {
                        $grouped[$subId]['types'][$row->type_slug] = [
                            'slug' => $row->type_slug,
                            'name' => $row->type_name,
                            'unit_label' => $row->unit_label,
                            'example' => $row->example,
                            'details' => [],
                        ];
                    }

                    if ($row->detail_label && $row->detail_price !== null) {
                        $grouped[$subId]['types'][$row->type_slug]['details'][] = [
                            'label' => $row->detail_label,
                            'price' => $row->detail_price,
                        ];
                    }
                }
            }

            // Reset numeric keys for response
            $final = collect($grouped)->map(function ($sub) {
                $filteredTypes = collect($sub['types'])->filter(function ($type) {
                    return !empty($type['details']);
                });

                if ($filteredTypes->isEmpty()) {
                    return null;
                }

                $sub['types'] = $filteredTypes->values();
                return $sub;
            })->filter()->values();

            return $this->sendResponse($final, 'Subservices fetched successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to fetch subservices.', ['error' => $e->getMessage()]);
        }
    }
}
