<?php

namespace App\Core\Security\Controllers\Api;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Security\Requests\Api\LoginRequest;
use App\Core\Security\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller {
    
    /** @var AuthService $authService */
    private $authService; 

    /**
     * @param AuthService $authService
     * @return void
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    
    public function index(Request $request)
    {
        $user = Auth::user();
        return response()->json(
            $user,
            Response::HTTP_OK
        );
    }
    
    /**
     * @param LoginRequest $request
     */
    public function login(LoginRequest $request) 
    {
        // return $this->authService->login($request->all(), 'api');
        $request = object_to_array(json_decode($request->getContent())); //WILLIAM ZEF
        return $this->authService->login($request, 'api');        
    }
    public function logout() 
    {
        return $this->authService->logout($type = 'api');
    }    
    
    public function domain(Request $request) 
    {   
        if (!$request->get('code')) return null;
        $data = DB::select("SELECT Oid FROM company WHERE Code = '{$request->get('code')}'");
        if (!$data) return null;
        return $data[0]->Oid;
    }
    public function version(Request $request) 
    {   
        return company()->Oid;
    }
}