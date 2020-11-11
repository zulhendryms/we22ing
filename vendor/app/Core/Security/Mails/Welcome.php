<?php

namespace App\Core\Security\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Core\Security\Entities\User;
use App\Core\Security\Services\UserService;

class Welcome extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    /**
     * Create a new message instance.
     * @param User $user
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->subject('Welcome to '.$this->user->CompanyObj->Name);
        $this->view( config('core.security.email.welcome_email') ?? 'Core\Security::emails.welcome' );
        return $this;
    }
}