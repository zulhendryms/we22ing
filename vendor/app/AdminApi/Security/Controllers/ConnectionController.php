<?php

namespace App\AdminApi\Security\Controllers;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Core\Base\Services\HttpService;
// use Illuminate\Support\Facades\Auth;

class ConnectionController extends Controller
{

    /** @var HttpService $httpService */
    private $httpService; 

    public function __construct(HttpService $httpService)
    {
        $this->httpService = $httpService->baseUrl('http://ezbpostest.ezbooking.co:1000/admin/api/v1');
        // $this->httpService = $httpService->baseUrl('http://ezbpostest.ezbooking.co:1000/pos/api/v1');
        // $this->httpService = $httpService->baseUrl('http://localhost/erp-laravel-travel/public/admin/api/v1');
    }
    
    public function test()
    {
        $token="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjNjNDBmNmM1NDBlM2FiNzg2NmYwYTEwY2QyNDViZGZjM2Q1YzlmZWI2YTYyNzllNmI3MjA4YTUyYjc5MTA3YjBkZmE5Y2ZiMTVhOGU4NDMyIn0.eyJhdWQiOiI0IiwianRpIjoiM2M0MGY2YzU0MGUzYWI3ODY2ZjBhMTBjZDI0NWJkZmMzZDVjOWZlYjZhNjI3OWU2YjcyMDhhNTJiNzkxMDdiMGRmYTljZmIxNWE4ZTg0MzIiLCJpYXQiOjE1NjQ2MzI5ODUsIm5iZiI6MTU2NDYzMjk4NSwiZXhwIjoxNzIyNDg1NzgzLCJzdWIiOiI1YjJmMGRjMi1lOWVjLTRlZmItOTg3Ni1lNTkzMjkzNzJhNDYiLCJzY29wZXMiOltdLCJjb21wIjoiNDE1MTE5YjktZTI1Mi00OTgyLThhMjgtODI4MTFlMjM1YWU1In0.O9l-kNGB4s6QcCkI_Xs-jcRp1S4U1f4-bTJxBefBQj0o6ZmOhf-n-uZiQvnMpgV4Gp--EjPIDOeTrXKPJv8Au38n-u3lZb0Xiy46LlGShpRi8Xy1JC1i0inr_KqIf_QHXNpx5i-1YLiLZ0UD0i5zm4612t-8uNxtmNifhZTOyzXQEa2lb73WgMcFsCrvMvlaPV4A87FBZyFT0wqYHHgCohwnWgkb5MgRtXpOM6eU7XOzbBSzxhb_MVtq69kljLZr2IfZRG1SBPrDbPL11VN1FrTsRL_qBUEZtAOuUnw2W8hYam-0awKzF8w5UUGnv1DWfKETFCpmTvj4yjtBeW07FaR4Se2_UGMi7wYJCiFPj727qxcUkJp6z_03QFFG-B-wYVxeB8wrQsxe78FuFagQ5qwqPrzdIpKviuZnH84D3PhH3oL4-0w3vuMaoBm2zaSeuNZ95e2nZzhQA5427UBEXXcsoyutvOZtfnkHUxHcAKh6_VNW2yhejIzjhPBQyS-bOqtb0-tD48YaSLs6hIRuMhXwDtlaic1a-ey9nGRDy6886s9OCXdaGOrHtqZcUuu8TUSMU9cndKLNvhZPl2BkvkvStT91LtEsrJL6duF5zb3XclI6SiH9nSYVpjd0KXK20TcmZOBPZFjwNXn3uBEhNenpgzxy3O88p1JPqJY_xnY";
        $data = $this->httpService->setHeader("Authorization", "Bearer ".$token)->get("/account?type=list");
        // $data = $this->httpService->setHeader("Authorization", "Bearer ".$token)->get("/account?type=list");
        // $data = $this->httpService->get("/connection");
        return $data;
    }
    
    /**
    * Check connection.
    *
    * @return \Illuminate\Http\Response
    */
    public function index()
    {
        // $user = Auth::user();
        // if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
        return response()->json(
            1,
            Response::HTTP_OK
        );
    }
    public function version()
    {
        return response()->json(
            "2019-07-03 08:000000",
            Response::HTTP_OK
        );
    }
    
}
