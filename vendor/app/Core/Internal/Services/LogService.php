<?php

namespace App\Core\Internal\Services;

use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use App\Core\Master\Entities\Log;

class LogService 
{
    private $request; /** @var Request $request */
    private $agent; /** @var Agent $agent */

    public function __construct(Request $request, Agent $agent)
    {
        $this->request = $request;    
        $this->agent = $agent;    
    }

    /**
     * Create a log
     * @param array $params
     * @return Log
     */
    public function create($params)
    {
        $agent = $this->agent;
        $request = $this->request;
        return Log::create(array_merge([
            'Date' => now()->toDateTimeString(),
            'Platform' => $agent->browser().' '.$agent->version($agent->browser()),
            'Platform' => $agent->browser().' '.$agent->version($agent->browser()),
            'IP' => $request->getClientIp(),
            'Agent' => $request->userAgent(),
            'Device' => $agent->device(),
            'OS' => $agent->platform().' '.$agent->version($agent->platform()),
            'URL' => $request->getRequestUri(),
            'Parameter' => json_encode($request->all()),
        ], $params));
    }

     /**
     * Create an access log
     * @param array $params
     * @return Log
     */
    public function createAccessLog($params)
    {
        if ($this->request->getRequestUri()!='/api/pos/v1/connection') {
            $params['Type'] = 'Access';
            return $this->create($params);
        }
    }

     /**
     * Create an error log
     * @param array $params
     * @return Log
     */
    public function createErrorLog($params)
    {
        $params['Type'] = 'Error';
        return $this->create($params);
    }
}