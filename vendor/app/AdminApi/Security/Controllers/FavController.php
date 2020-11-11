<?php

namespace App\AdminApi\Security\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Security\Entities\UserModule;
use App\Core\Internal\Entities\Modules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Validator;

class FavController extends Controller
{
    public function index(Request $request)
    {        
        try {     
            // $user = Auth::user();
            // if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
            // $query = "SELECT um.Oid, um.Modules AS Code, m.Name, m.Url, m.Icon FROM userModule um LEFT OUTER JOIN sysModules m ON m.Code = um.Modules WHERE User='".$user->Oid."'";            
            // $data = DB::select($query);

            // return $data;
            return [];
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        } 
    }
    

    public function save(Request $request)
    {        
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        $dataArray = object_to_array($request);
        
        $messsages = array(
            'Modules.required'=>__('_.Modules').__('error.required'),
            'User.required'=>__('_.User').__('error.required'),
            'User.exists'=>__('_.User').__('error.exists'),
        );
        $rules = array(
            'Modules' => 'required',
            'User' => 'required|exists:user,Oid',                       
        );

        $validator = Validator::make($dataArray, $rules,$messsages);

        if ($validator->fails()) {
            return response()->json(
                $validator->messages(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {    
            $query = "SELECT COUNT(*) AS count FROM usermodule um
                WHERE  um.User = '".$request->User."' AND um.Modules = '".$request->Modules."' AND um.GCRecord IS NULL";
            $count = DB::select($query);
            if ($count[0]->count != 0) return response()->json('There is already data at Fav', Response::HTTP_NOT_FOUND);

            $data = new UserModule();
            DB::transaction(function () use ($request, &$data) {
                $disabled = disabledFieldsForEdit();
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->save();
                if(!$data) throw new \Exception('Data is failed to be saved');
            });

            $result = UserModule::with([
                'UserObj' => function ($query) {$query->addSelect('Oid', 'Code', 'Name');},
                ])->where('Oid',$data->Oid)->firstOrFail();
            $module = Modules::where('Code',$result->Modules)->first();
            $result->Icon = $module->Icon;
            $result->Name = $module->Name;
            $result->Url = $module->Url;
            return response()->json(
                $result, Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function destroy(UserModule $data)
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
