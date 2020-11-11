<?php

namespace App\Core\Setup\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Core\Security\Events\UserCreated' => [
            'App\Core\Security\Listeners\SendWelcomeEmailToUser',
            'App\Core\Security\Listeners\SendConfirmationEmailToUser',
        ],
        'App\Core\Security\Events\UserLoggedIn' => [
            'App\Core\Security\Listeners\CreateUserDevice',
        ],
        'App\Core\Chat\Events\ChatRoomCreated' => [
            'App\Core\Chat\Listeners\InviteAdminToChannel',
        ],
        'App\Core\POS\Events\POSCreated' => [
            'App\Core\POS\Listeners\CreatePOSEntryLog',
        ],
        'App\Core\POS\Events\POSOrdered' => [
            'App\Core\POS\Listeners\CreatePOSStatusLog',
            'App\Core\POS\Listeners\SendPOSNotificationToSlack'
        ],
        'App\Core\POS\Events\POSPaymentMethodSelected' => [
            'App\Core\POS\Listeners\SendPOSEmailToUser'
        ],
        'App\Core\POS\Events\POSVerifying' => [
            'App\Core\POS\Listeners\CreatePOSStatusLog',
            'App\Core\POS\Listeners\SendPOSNotificationToSlack'
        ],
        'App\Core\POS\Events\POSExpired' => [
            'App\Core\POS\Listeners\CreatePOSStatusLog',
            'App\Core\Travel\Listeners\RemoveTravelTransactionAllotment',
        ],
        'App\Core\POS\Events\POSPaid' => [
            'App\Core\POS\Listeners\CreatePOSStatusLog',
            'App\Core\POS\Listeners\SendPOSNotificationToSlack',
            'App\Core\POS\Listeners\SendPOSEmailToUser',
            'App\Core\POS\Listeners\SendETicketToUser',
            'App\Core\POS\Listeners\CreateRedeemETickets',
        ],
        'App\Core\POS\Events\POSCompleted' => [
            'App\Core\POS\Listeners\CreatePOSStatusLog',
            'App\Core\POS\Listeners\SendPOSEmailToUser',
            'App\Core\Travel\Listeners\SetTravelTransactionDetailStatusToComplete',
            'App\Core\Travel\Listeners\CreateTravelTransactionJournal',
        ],
        'App\Core\POS\Events\POSCancelled' => [
            'App\Core\Travel\Listeners\RemoveTravelTransactionAllotment',
            'App\Core\Travel\Listeners\SetTravelTransactionDetailStatusToCancel',
        ],
        'App\Core\POS\Events\POSHidden' => [
            'App\Core\POS\Listeners\CreatePOSStatusLog',
        ],
        'App\Core\Travel\Events\TravelTransactionDetailCompleted' => [
            'App\Core\POS\Listeners\SetPOSToComplete',
            'App\Core\Travel\Listeners\CreateTravelTransactionDetailJournal',
        ],
        'App\Core\Internal\Events\EventSendNotificationSocketOneSignal' => [
            'App\Core\Internal\Listeners\SendNotificationSocketOneSignal',
        ],
        'App\Core\Trading\Events\PurchaseRequestSubmit' => [
            'App\Core\Trading\Listeners\SendNotificationToUser',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
