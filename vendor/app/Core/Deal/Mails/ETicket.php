<?php

namespace App\Core\Deal\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Core\POS\Entities\PointOfSale;
use App\Core\POS\Mails\Purchase;

class ETicket extends Purchase { }