<?php

namespace App\Core\Master\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use App\Core\Master\Entities\Log;

class CreateLog
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var string $mod */
    protected $mod;
     /** @var string $type */
     protected $type;
      /** @var string $message */
    protected $message;
     /** @var string $description */
     protected $description;
      /** @var string $recordId */
    protected $recordId;

    /**
     * Create a new job instance.
     * 
     * @param string $mod
     * @param string $type
     * @param string $message
     * @param string $description
     * @param string $recordId
     * @return void
     */
    public function __construct($mod, $type, $message, $description = '', $recordId = null)
    {
        $this->mod = $mod;
        $this->type = $type;
        $this->message = $message;
        $this->description = $description;
        $this->recordId = $recordId;
    }

    /**
     * Execute the job.
     * @param Request $request
     * 
     * @return void
     */
    public function handle(Request $request, Agent $agent)
    {
        Log::create([
            'Date' => now()->toDateTimeString(),
            'Type' => $this->type,
            'Platform' => $agent->browser().' '.$agent->version($agent->browser()),
            'IP' => $request->getClientIp(),
            'Agent' => $request->userAgent(),
            'Device' => $agent->device(),
            'OS' => $agent->platform().' '.$agent->version($agent->platform()),
            'URL' => $request->getRequestUri(),
            'Parameter' => json_encode($request->all()),
            'Message' => $this->message,
            'RecordId' => $this->recordId,
            'Description' => $this->description,
            'Module' => $this->mod,
        ]);
    }
}
