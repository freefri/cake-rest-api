<?php

declare(strict_types = 1);

namespace RestApi\Lib\Oauth;

use Cake\Http\Exception\BadRequestException;
use Cake\Http\ServerRequest;

class AuthorizationCodeGrantPkceFlow
{
    public function getLoginChallenge(ServerRequest $request): string
    {
        $responseType = $request->getQuery('response_type');
        $clientId = $request->getQuery('client_id');
        $state = $request->getQuery('state');// recommended
        /** @var string $redirectUri Optional. The URL to redirect after authorization has been granted */
        $redirectUri = $request->getQuery('redirect_uri');
        $codeChallengeMethod = $request->getQuery('code_challenge_method');
        $codeChallenge = $request->getQuery('code_challenge');
        if (strtolower($responseType) !== 'code') {
            throw new BadRequestException('Only Authorization Code Grant (PKCE) Flow is allowed');
        }
        if ($codeChallengeMethod !== 'S256') {
            throw new BadRequestException('Only S256 challenge method is allowed');
        }
        if (!$clientId || !$codeChallenge) {
            throw new BadRequestException('Required parameter missing client_id or code_challenge');
        }
        return $this->computeLoginChallenge($codeChallenge, $redirectUri);
    }

    public function computeLoginChallenge(string $codeChallenge, string $redirectUri): string
    {
        return '23uhr98fuihwei'.$codeChallenge.'_'.$redirectUri;
    }
}
