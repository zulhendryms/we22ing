<?php

namespace App\Core\External\Services;

use App\Core\Base\Services\HttpService;

class SlackService {

    /** @var HttpService $httpService */
    private $httpService; 

    public function __construct(HttpService $httpService)
    {
        $this->httpService = $httpService
        ->baseUrl(config('services.slack.url'));
    }

    /**
     * Create slack channel
     * 
     * @param string $name
     * @return string
     */
    public function createChannel($name)
    {
        $response = $this->httpService->formParams()->post('/channels.create', [
            'token' => config('services.slack.token'),
            'name' => config('app.initial').'_'.$name,
        ]);

        return $response->channel->id;
    }

    /**
     * Invite user to chanel
     * 
     * @param string $channelId
     * @param string $userId
     * @return string
     */
    public function inviteUserToChannel($channelId, $userId)
    {
        return $this->httpService->formParams()->post('/channels.invite', [
            'token' => config('services.slack.token'),
            'channel' => $channelId,
            'user' => $userId
        ]);
    }


    /**
     * Send a message to a channel
     * 
     * @param string $channelId
     * @param string $name
     * @param string $message
     * @return string
     */
    public function sendMessage($channelId, $name, $message, $attachments = [])
    {
        return $this->httpService->formParams()->post('/chat.postMessage', [
            'token' => config('services.slack.token_bot'),
            'channel' => $channelId,
            'text' => $message,
            'icon_url' => 'https://cdn1.iconfinder.com/data/icons/user-pictures/101/malecostume-512.png',
            'username' => $name,
            'as_user' => false,
            'attachments' => json_encode($attachments)
        ]);
    }

    public function sendWebhook($message, $attachments = [])
    {
        $params = [
            'text' => $this->message,
        ];

        if (!empty($this->attachments)) {
            $params['attachments'] = [ $this->attachments ];
        }

        return $httpService
        ->json()
        ->post(config('services.slack.webhook_url'), $params);
    }
}

?>
