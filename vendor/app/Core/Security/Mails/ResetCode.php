<?php

namespace App\Core\Security\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Core\Security\Entities\User;
use App\Core\Security\Services\UserService;

class ResetCode extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    /**
     * Create a new message instance.
     * @param string $code
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
        $this->subject('Reset Password');
        $this->view( config('core.security.reset_password.template') ?? 'Core\Security::emails.reset_password' );
        return $this;
    }
}