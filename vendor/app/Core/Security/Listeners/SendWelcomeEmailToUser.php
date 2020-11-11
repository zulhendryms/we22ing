<?php

namespace App\Core\Security\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use App\Core\Security\Events\UserCreated;
use App\Core\Security\Mails\Welcome;

class SendWelcomeEmailToUser implements ShouldQueue
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
            if (config('core.security.register.require_email_confirmation')) return;
        }
        if (!empty(config('core.security.email.welcome_email'))) Mail::to($user->UserName)->queue(new Welcome($user));
    }
}
