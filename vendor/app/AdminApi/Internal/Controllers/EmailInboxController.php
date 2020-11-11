<?php
namespace App\AdminApi\Internal\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use Webklex\IMAP\Client;

class EmailInboxController extends Controller
{
    protected $client;

    public function __construct() {
        $this->client = new Client([
            'host'          => 'premium54.web-hosting.com',
            'port'          => 993,
            'encryption'    => 'ssl',
            'validate_cert' => true,
            'username'      => 'noreply@ezbooking.co',
            'password'      => 'ezBooking2016',
            'protocol'      => 'imap'
        ]);
    }

    public function downloadEmailMessage(Request $request)
    {
        try {
            $this->client->connect();
            $folders = $this->client->getFolders('INBOX.name');
            $result = [];
            $i = 1;
            foreach($folders as $folder){
                // $messages = $folder->query()->from('noreply@ezbooking.co')->limit(3)->get();
                $messages = $folder->messages()->limit(5)->get();
                foreach($messages as $message){
                    logger('data ke '.$i);
                    $i = $i + 1;
                    $result[] = [
                        'Oid' => $message->getUid(),
                        'Subject' => $message->getSubject(),
                        'Date' => $message->getDate(),
                        'Attachments' => $message->getAttachments()->count(),
                        'HTMLBody' => $message->getHTMLBody(),
                        'TextBody' => $message->getTextBody()
                    ];
                    $message->delete();
                }
            }

            return $result;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

}
