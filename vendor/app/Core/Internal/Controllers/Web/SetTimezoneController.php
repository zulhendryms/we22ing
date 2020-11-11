<?php

namespace App\Core\Internal\Controllers\Web;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SetTimezoneController extends Controller 
{
    public function index(Request $request)
    {
        session()->put(config('constants.timezone'), $request->input('value') ?? company_timezone());
    }
}