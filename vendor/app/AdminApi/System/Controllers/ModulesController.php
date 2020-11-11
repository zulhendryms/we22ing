<?php

namespace App\AdminApi\System\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Internal\Entities\Modules;
use Illuminate\Support\Facades\DB;

class ModulesController extends Controller
{
    public function index(Request $request)
    {        
        try {           
            $field = ['Code']; 
            $type = $request->input('type') ?: 'combo';
            $data = Modules::all();
            
            if($type == "combo"){
                foreach ($data as $row ) {
                    $row->setVisible($field);
                }
            }
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    
}
