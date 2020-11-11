<?php
namespace App\Core\Security\Bridge;

use Laravel\Passport\Bridge\AccessToken as PassportAccessToken;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\CryptKey;

class AccessToken extends PassportAccessToken {
    public function convertToJWT(CryptKey $privateKey)
    {
        return (new Builder())
            ->setAudience($this->getClient()->getIdentifier())
            ->setId($this->getIdentifier(), true)
            ->setIssuedAt(time())
            ->setNotBefore(time())
            ->setExpiration($this->getExpiryDateTime()->getTimestamp())
            ->setSubject($this->getUserIdentifier())
            ->set('scopes', $this->getScopes())
            ->set('comp', request()->input('company')) // my custom claims
            ->sign(new Sha256(), new Key($privateKey->getKeyPath(), $privateKey->getPassPhrase()))
            ->getToken();
    }
}