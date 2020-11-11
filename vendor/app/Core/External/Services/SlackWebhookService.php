<?php

namespace App\Core\External\Services;

use App\Core\Base\Services\HttpService;

class SlackWebhookService 
{

    /** @var HttpService $httpService */
    private $httpService; 

    public function __construct(HttpService $httpService)
    {
        $this->httpService = $httpService->json();
    }

    /**
     * @param string $message
     * @param array $attachments
     */
    public function sendMessage($message, $attachments = [])
    {
        $params = [
            'text' => $message,
        ];

        if (!empty($attachments)) {
            $params['attachments'] = [ $attachments ];
        }

        return $this->httpService
        ->post(config('services.slack.webhook_url'), $params);
    }
    
}

?>
