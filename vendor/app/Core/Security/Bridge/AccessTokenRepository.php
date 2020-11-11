<?php
namespace App\Core\Security\Bridge;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use Laravel\Passport\Bridge\AccessTokenRepository as PassportAccessTokenRepository;

class AccessTokenRepository extends PassportAccessTokenRepository
{
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null)
    {
        return new AccessToken($userIdentifier, $scopes); // AccessToken from step 1
    }
}