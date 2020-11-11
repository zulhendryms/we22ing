<?php

namespace App\Core\Report\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Report extends Mailable
{
    use Queueable, SerializesModels;

    public $file;

    /**
     * Create a new message instance.
     * @return void
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->subject('Report');
        $this->view('Core\Report::report' );

        // $path = $this->file;
        // if (is_file($path)) $this->attach($this->file);
        return $this;
    }
}