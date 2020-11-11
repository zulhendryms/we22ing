<?php

namespace App\Core\Security\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use App\Core\Security\Events\UserCreated;

class SendConfirmationEmailToUser implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UserCreated  $event
     * @return void
     */
    public function handle(UserCreated $event)
    {
        $user = $event->user;
        if ($event instanceof UserCreated) {
            if (config('core.security.require_email_registration')) {
                // Send confirmation email
            }
        }
    }
}
