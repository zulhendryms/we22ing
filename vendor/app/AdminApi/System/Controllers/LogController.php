<?php

namespace App\AdminApi\System\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Laravel\Http\Controllers\Controller;

class LogController extends Controller
{
    public function create(Request $request)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        $data = DB::select("SELECT * FROM pospointofsale WHERE Oid='{$request->PointOfSale}'");
        $data = $data[0];
        $user = Auth::user();
        $now = now();
        $arr = [
            "Oid" => "UUID()",
            "Company" => qstr($data->Company),
            "PointOfSale" => qstr($data->Oid),
            "Name" => qstr(Auth::check() ? Auth::user()->UserName : 'Guest'),
            "Status" => qstr($data->Status),
            'Date' => qstr((clone $now)->addHours(company_timezone())->toDateTimeString()),
            "Module" => qstr($request->Module),
            "Description" => qstr($request->Description),
            "User" => qstr($user->Oid),
            "Message" => qstr($request->Message),            
        ];

        $query = "INSERT INTO pospointofsalelog (%s) SELECT %s";
        $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
        DB::insert($query);
		return response()->json(
			null, Response::HTTP_OK
		);
    }
    
}
