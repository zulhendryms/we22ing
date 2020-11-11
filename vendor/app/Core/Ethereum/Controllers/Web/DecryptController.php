<?php

namespace App\Core\Ethereum\Controllers\Web;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DecryptController extends Controller 
{
    public function index(Request $request)
    {
        return $this->getView();
    }

    public function store(Request $request)
    {
        $data = json_decode($request->input('log'));
        $result = [
            'address' => decrypt_salted($data->address_enc),
            'private_key' => decrypt_salted($data->privateKey_enc)
        ];
        return $this->getView($result);
    }

    private function getView($result = null)
    {
        return view('Core\Ethereum::decrypt', [ 'result' => $result ]);
    }
}