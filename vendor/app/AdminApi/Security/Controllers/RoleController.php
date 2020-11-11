<?php

namespace App\AdminApi\Security\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Internal\Entities\Role;
use App\Core\Security\Entities\RoleMaster;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
  
    public function index(Request $request)
    {
        try {
            if ($request->has('id')) {
                return Role::where('Oid',$request->input('id'))->first();
            } else {
                return Role::orderBy('Name')->get();
            }
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
        
    }
    
    public function save(Request $request, $Code = null)
    { 
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))) ;
        
    
        try {
            $data;

            if (!$Code) $role = new RoleMaster();
            else $role = RoleMaster::where('Oid',$Code)->first();
            
            $role->Name = $request->Name;
            $role->save();
            
            $data = Role::where('Oid',$role->Oid)->first();
            if (!$data) $data = new Role();            
            $data->Oid = $role->Oid;
            $excluded = ['Image', 'ImageHeader'];
            $disabled = array_merge(disabledFieldsForEdit(), $excluded);
            foreach ($request as $field => $key) {
                if (in_array($field, $disabled)) continue;
                $data->{$field} = $request->{$field};
            }
            $data->save(); 
            if(!$data) throw new \Exception('Data is failed to be saved');

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

    public function destroy($Code)
    {
        try {            
            DB::transaction(function () use ($Code) {
                DB::delete("DELETE FROM role WHERE Oid = '".$Code."'");
                // $data = Role::where('Code',$Code)->first();
                // $data->delete();
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
