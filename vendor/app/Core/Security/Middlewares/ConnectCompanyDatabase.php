<?php

namespace App\Core\Security\Middlewares;

use Closure;
use Illuminate\Http\Request;
use App\Core\Master\Entities\Company;
use Illuminate\Support\Facades\DB;
use PDO;

class ConnectCompanyDatabase
{
    protected $connectionKey = 'database.connections.mysql';
    public function __construct()
    {
        //
    }
    public function handle($request, Closure $next)
    {
        // dd(Company::first());
        // dd(config($this->connectionKey));
        $id = $this->getCompanyId($request);
        if ($id) config(['app.company_id' => $id]);
        $company = Company::select(
            'DatabaseName',
            'DatabaseUser',
            'DatabasePassword',
            'DatabaseHost',
            'DatabaseSSLMode',
            'DatabaseStrict',
            'DatabaseCertificate',
            'DatabasePort'
        )->find($id);
        
        if (!$company) return $next($request); // Use default database

        if (isset($company->DatabaseCertificate)) {
            $options = extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [];
            // $options = [];
            // $options =  [ PDO::MYSQL_ATTR_SSL_CA => $company->DatabaseCertificate, ];
        } else {
            $options = [];
        }

        if ($company->DatabaseName != 'ezb_server') {
            $conn = [
                'database' => $company->DatabaseName,
                'username' => $company->DatabaseUser,
                'password' => $company->DatabasePassword,
                'host' => $company->DatabaseHost,
                'sslmode' => $company->DatabaseSSLMode,
                'options' => $options,
                'strict' => $company->DatabaseStrict ? true : false,
                'port'=> $company->DatabasePort ?: 3306
            ];
        }
        // dd($conn);
        
        if (!empty($conn['host']) && 
            !empty($conn['username']) && 
            !empty($conn['database'])
        ) {            
            config([ $this->connectionKey => array_merge(config($this->connectionKey), $conn) ]);
        }

        // dd(config($this->connectionKey));
        DB::reconnect('mysql');

        return $next($request);
    }

    protected function getCompanyId(Request $request)
    {
        if ($request->method() === 'POST' && ($request->has('Company') || $request->has('company'))) {
            return $request->get('Company') ?? $request->get('company');
        }
        if ($request->bearerToken()) {
            try {
                $token = (new \Lcobucci\JWT\Parser())->parse($request->bearerToken());
                return $token->getClaim('comp');
            } catch (\Exception $exception) {}
        }
    }
}