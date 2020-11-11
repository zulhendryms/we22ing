<?php

namespace App\Core\POS\Services;

use App\Core\Internal\Services\AuditService;
use App\Core\POS\Entities\PointOfSale;
use App\Core\External\Services\SlackWebhookService;

class POSNotificationService 
{

    /** @var SlackWebhookService $webhookService */
    protected $webhookService;

    /**
     * @param SlackWebhookService $slackWebhookService
     * @return void
     */
    public function __construct(SlackWebhookService $slackWebhookService)
    {
        $this->webhookService = $slackWebhookService;
    }

    /**
     * @param PointOfSale $pos
     * @return void
     */
    public function send(PointOfSale $pos)
    {
        $attachments = $this->buildSlackAttachment($pos);
        $this->webhookService->sendMessage('', $attachments);
    }

    /**
     * @param PointOfSale $pos
     * @return array
     */
    protected function buildSlackAttachment(PointOfSale $pos)
    {
        $title = $this->getTitle($pos);
        $author = $this->getAuthor($pos);
        $action = $this->getAction($pos);
        $footer = $this->getFooter($pos);
        $message = $this->getMessage($pos);
        $color = "#afbcd1";
        $fallback = $title.'; '.$author.PHP_EOL.$action;

        $urltransaction = config('app.admin_url').'/default.aspx#ViewID=DealTransaction_DetailView&ObjectKey='.$pos->Oid.'&ObjectClassName=Cloud_ERP.Module.BusinessObjects.Deal.DealTransaction&mode=Edit';
        // $urltransaction = config('app.admin_url').'/default.aspx#ViewID=FerTransaction_DetailView&ObjectKey='.$pos->Oid."&ObjectClassName=Cloud_ERP.Module.BusinessObjects.Ferry.FerTransaction&mode=Edit";
        $urluser = config('app.admin_url').'/default.aspx#ViewID=User_DetailView&ObjectKey='.$pos->User."&ObjectClassName=Cloud_ERP.Module.BusinessObjects.Security.User&mode=View";

        return [
            "fallback"=> $fallback,
            "color"=> $color,
            "author_name"=> $author,
            "author_icon"=> $pos->SupplierObj->Image,
            "title"=> $title,
            "title_link"=> $urluser,
            "text"=> $message,
            'actions' => [
                [
                    'color' => $color,
                    'type' => 'button',
                    'text' => $action,
                    'url' => $urltransaction,
                ]
            ],
            "footer"=> "No. #".$pos->Code,
            "footer_icon"=> company_logo(),
        ];
    }

    /**
     * @param PointOfSale $pos
     * @return string
     */
    protected function getAuthor(PointOfSale $pos)
    {
        return $pos->SupplierObj->Code.' ('.$pos->APIType.') '.PHP_EOL;
    }

    /**
     * @param PointOfSale $pos
     * @return string
     */
    protected function getAction(PointOfSale $pos)
    {
        if ($pos->StatusObj->IsOrdered) {
            return "Please Standby for payment";
        } else if ($pos->StatusObj->IsVerifying) {
            return "Please Verify payment";
        } else if ($pos->StatusObj->IsPaid) {
            $method = $pos->PaymentMethodObj;
            switch ($pos->APIType) {
                case "manual_gen":
                    return "Please Generate E-Ticket";
                case "manual_up":
                    return "Please Upload E-Ticket";
                case "auto":
                    return "Go to Transaction";
                case "redeem":
                    return "Go to Transaction";
                case "auto_stock":
                    $action = 'Go To Transaction';
                    if ($pos->APIType == 'auto_stock' && $pos->ETickets()->count() == 0) {
                        foreach ($pos->Details as $detail) {
                            if (
                                $detail->ItemObj->ETickets()
                                ->available()->count() < $detail->Quantity
                            ) {
                                $action ='Stock not enough!';
                            }
                            break;
                        }
                    }
                    if (config('core.pos.eticket.auto_send')) {
                        $blacklists =  config('core.pos.eticket.auto_send.payment_method_blacklist');
                        $whitelists = config('core.pos.eticket.auto_send.payment_method_whitelist');
                        if (count($blacklists) != 0) {
                            if (in_array(
                                $method->Code, 
                                $blacklists
                            ))  return $action;
                        } else if (count($whitelists) != 0) {
                            if (!in_array(
                                $method->Code, 
                                $whitelists
                            )) return $action;
                        }
                    } else {
                        return $action;
                    }
            }
        }
    }

    /**
     * @param PointOfSale $pos
     * @return string
     */
    protected function getTitle(PointOfSale $pos)
    {
        $title = $pos->ContactName.' ['.$pos->StatusObj->Name.']';
        if (isset($pos->User)) {
            $title .= ' U*';
        } else {
            $title .= ' G*';
        }
        return $title;
    }

    /**
     * @param PointOfSale $pos
     * @return string
     */
    protected function getMessage(PointOfSale $pos)
    {
        $msg = '';
        $msg .= 'User: +'.$pos->UserObj->PhoneCode.$pos->UserObj->PhoneNo.' '.$pos->UserObj->UserName.PHP_EOL;
        if ($pos->ContactPhone != $pos->UserObj->PhoneCode.$pos->UserObj->PhoneNo && $pos->ContactEmail != $pos->UserName)
            $msg .= 'Contact: +'.$pos->ContactPhone.' '.$pos->ContactEmail.PHP_EOL;            
        if (isset($pos->DealTransactionObj))
            $msg .= $pos->DealTransactionObj->ItemObj->Name.' ('.$pos->Quantity.'pcs) '.PHP_EOL;
        $msg .= $pos->PaymentMethodObj->Name.' '.$pos->CurrencyObj->Symbol.' '.number_format($pos->TotalAmount, 0).PHP_EOL;
        return $msg;
    }

    /**
     * @param PointOfSale $pos
     * @return string
     */
    protected function getFooter(PointOfSale $pos)
    {
        return 'No. #'.$pos->Code;
    }

    
}