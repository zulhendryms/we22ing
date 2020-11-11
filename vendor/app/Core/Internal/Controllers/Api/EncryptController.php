<?php

namespace App\Core\Internal\Controllers\Api;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EncryptController extends Controller 
{
    public function encrypt(Request $request)
    {
        return [
            'Value' => encrypt($request->input('Value'))
        ];
    }

    public function decrypt(Request $request)
    {
        return [
            'Value' => decrypt($request->input('Value'))
        ];
    }

    public function encryptSalted(Request $request)
    {
        return [
            'Value' => encrypt_salted($request->input('Value'))
        ];
    }

    public function decryptSalted(Request $request)
    {
        return [
            'Value' => decrypt_salted($request->input('Value'))
        ];
    }
}