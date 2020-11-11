<?php

namespace App\Core\External\Services;

use App\Core\Base\Services\HttpService;
use App\Core\Security\Entities\User;

class StripeService {

    /** @var HttpService $httpService */
    private $httpService; 

    public function __construct(HttpService $httpService)
    {
        $headers = [
            'Authorization' => 'Bearer '.config('services.stripe.secret'),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $this->httpService = $httpService
        ->baseUrl(config('services.stripe.url'))
        ->headers($headers)
        ->formParams();
    }
    
    public function getToken($token)
    {
        return $this->httpService->get('/tokens/'.$token);
    }

    public function createToken($data)
    {
        $token = $this->httpService->post('/tokens', $data);
        return $token;
    }

    public function createCharge($data, User $user = null)
    {
        if (isset($user)) {
            $customer = $user->StripeCustomerId;
            if (empty($customer)) {
                $customer = $this->createCustomer([
                    'email' => $user->UserName,
                    'source' => $data['source']
                ]);
                $user->StripeCustomerId = $customer->id;
                $user->save();
                $this->createUserCard($user, (array) $customer->sources->data[0]);
                $data['source'] = $customer->default_source;
                $data['customer'] = $customer->id;
            } else {
                if (strpos($data['source'], 'tok_') === 0) {
                    $token = $this->getToken($data['source']);
                    $card = $this->findUserCard($user, (array) $token->card);
                    if (is_null($card)) {
                        $card = $this->createUserCard(
                            $user,
                            (array) $this->createCustomerCard($user->StripeCustomerId, $data['source'])
                        );
                    }
                    $data['source'] = $card->StripeId;
                }
                $data['customer'] = $user->StripeCustomerId;
            }
        }
        return $this->httpService->post('/charges', $data);
    }

    public function createCustomer($data)
    {
        return $this->httpService->post('/customers', $data);
    }

    public function getCustomerCards($customerId, $limit = 10)
    {
        $data = $this->httpService->get("/customers/{$customerId}?object=card&limit={$limit}");
        return $data->data;
    }

    public function createCustomerCard($customerId, $token)
    {
        return $this->httpService->post("/customers/{$customerId}/sources", [ 'source' => $token ]);
    }

    public function findUserCard($user, $data)
    {
        $card = $user->SavedCards()
        ->where('ExpiredMonth', $data['exp_month'])
        ->where('ExpiredYear', $data['exp_year'])
        ->where('StripeFingerprint', $data['fingerprint'])
        ->first();
        if (is_null($card)) return $card;
        if (isset($data['address_line1'])) $card->AddressLine1 = $data['address_line1'];
        if (isset($data['address_city'])) $card->AddressCity = $data['address_city'];
        if (isset($data['address_state'])) $card->AddressState = $data['address_state'];
        if (isset($data['address_zip'])) $card->AddressZip = $data['address_zip'];
        $card->save();
        return $card;
    }

    public function createUserCard($user, $data)
    {
        return $user->SavedCards()->create([
            'StripeId' => $data['id'],
            'StripeFingerprint' => $data['fingerprint'],
            'ExpiredMonth' => $data['exp_month'],
            'ExpiredYear' => $data['exp_year'],
            'AddressLine1' => $data['address_line1'],
            'AddressCity' => $data['address_city'],
            'AddressState' => $data['address_state'],
            'AddressZip' => $data['address_zip'],
            'Initial' => $data['last4'],
            'Name' => $data['name']
        ]);
    }

    public function verifyWebhookSignature($request)
    {
        $header = $request->header('STRIPE_SIGNATURE');
        throw_unless(isset($header), \Exception::class, "Signature invalid");

        $signatures = explode(',', $header);
        throw_if(count($signatures) < 2, \Exception::class, "Signature invalid");

        $timestamp = substr($signatures[0], strpos($signatures[0], '=') + 1);

        $i = 1;
        $signature = $signatures[$i];
        while (!starts_with($signature, 'v1')) $signature = $signatures[++$i];
        $signature = substr($signature, strpos($signature, '=') + 1);
        $payload = $timestamp.'.'.$request->getContent();
        $signatureToCompare = hash_hmac(
            'sha256',
            $payload,
            config('services.stripe.webhook_secret')
        );
        throw_unless($signature == $signatureToCompare, \Exception::class, "Signature invalid");
    }
}

?>
