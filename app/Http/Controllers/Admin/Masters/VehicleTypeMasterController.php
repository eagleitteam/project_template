<?php

namespace App\Http\Controllers\Admin\Masters;

use App\Http\Controllers\Admin\Controller;
use App\Http\Requests\Admin\Masters\StoreVehicleRequest;
use App\Http\Requests\Admin\Masters\UpdateVehicleRequest;
use App\Models\VehicleTypeMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;


class VehicleTypeMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $vehicleType = VehicleTypeMaster::latest()->get();

        return view('admin.masters.vehicle-types')->with(['vehicleType' => $vehicleType]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVehicleRequest $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->validated();
VehicleTypeMaster::create(Arr::only($input, (new VehicleTypeMaster())->getFillable()));
            DB::commit();

            return response()->json(['success' => 'vehicle type created successfully!']);
        } catch (\Exception $e) {
            return $this->respondWithAjax($e, 'creating', 'Ward');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
   public function edit(VehicleTypeMaster $vehicle, Request $request)
{

    $vehicle = VehicleTypeMaster::find($request->model_id);
    if ($vehicle) {
        return response()->json([
            'result' => 1,
            'vehicle' => $vehicle,
        ]);
    } else {
        return response()->json([
            'result' => 0,
            'message' => 'Vehicle type not found',
        ]);
    }
}

    /**
     * Update the specified resource in storage.
     */
public function update(UpdateVehicleRequest $request, VehicleTypeMaster $vehicle)
    {
        try {
            DB::beginTransaction();
            $input = $request->validated();
            $vehicle = VehicleTypeMaster::find($request->edit_model_id);
            $vehicle->update(Arr::only($input, VehicleTypeMaster::getFillables()));
            DB::commit();

            return response()->json(['success' => 'Ward updated successfully!']);
        } catch (\Exception $e) {
            return $this->respondWithAjax($e, 'updating', 'Vehicle');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VehicleTypeMaster $vehicle, Request $request)
    {
                    $vehicle = VehicleTypeMaster::find($request->model_id);

        try {
            DB::beginTransaction();
            $vehicle->delete();
            DB::commit();
            return response()->json(['success' => 'vehicle deleted successfully!']);
        } catch (\Exception $e) {
            return $this->respondWithAjax($e, 'deleting', 'vehicle');
        }
    }
}