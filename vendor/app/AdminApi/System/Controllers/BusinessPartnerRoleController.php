<?php

namespace App\AdminApi\System\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Internal\Entities\BusinessPartnerRole;
use App\Core\Internal\Entities\CompanyDisable;
use Illuminate\Support\Facades\DB;

class BusinessPartnerRoleController extends Controller
{
    public function index(Request $request)
    {
        return $this->list($request->input('type'));
    }

    public function list($type) {        
        try {
            $type = $type ?: 'combo';
            $data = BusinessPartnerRole::where('IsActive',1)->whereNull('GCRecord');
            if (!CompanyDisable::where('Modules','Travel')->where('Field','Travel')->first()->IsDisable ?: 0) $data->where('Code','<>','Travel');
            if (!CompanyDisable::where('Modules','Travel')->where('Field','Transport')->first()->IsDisable ?: 0) $data->where('Code','<>','Transport');
            if (!CompanyDisable::where('Modules','Travel')->where('Field','Hotel')->first()->IsDisable ?: 0) $data->where('Code','<>','Hotel');
            $data = $data->orderBy('Name')->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        } 
    }
    
    public function show(BusinessPartnerRole $data)
    {
        try {
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function save(Request $request, $Oid = null)
    {        
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        try {            
            if (!$Oid) $data = new BusinessPartnerRole();
            else $data = BusinessPartnerRole::findOrFail($Oid);
            DB::transaction(function () use ($request, &$data) {
                $disabled = disabledFieldsForEdit();
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->save();
                if(!$data) throw new \Exception('Data is failed to be saved');
            });

            return $data;
            return response()->json(
                $data, Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function destroy(BusinessPartnerRole $data)
    {
        try {            
            DB::transaction(function () use ($data) {
                $data->delete();
            });
            return response()->json(
                null, Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
