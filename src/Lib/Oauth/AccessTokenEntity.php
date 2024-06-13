<?php

namespace RestApi\Lib\Oauth;

class AccessTokenEntity
{
    protected $access_token;
    protected $client_id;
    protected $user_id;
    //protected $expires;
    protected ?string $scope;

    public function __construct(array $token)
    {
        $this->access_token = $token['access_token'] ?? null;
        $this->client_id = $token['client_id'] ?? null;
        $this->user_id = $token['user_id'] ?? null;
        //$this->expires = $token['expires'] ?? null;
        $this->scope = $token['scope'] ?? null;
    }

    public function getUIdOrEmpty()
    {
        return $this->getUserId() ?? '';
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function getAccessToken()
    {
        return $this->access_token;
    }

    public function getClientId()
    {
        return $this->client_id;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function getScopes(): array
    {
        return explode(' ', $this->scope);
    }

    public function storeInfoForLogs(): void
    {
        if ($this->user_id) {
            $token = ['AUTH_TOKEN_UID' => $this->user_id];
        } else {
            if ($this->client_id) {
                $token = ['AUTH_TOKEN_CLIENT_ID' => $this->client_id];
            } else {
                $token = [];
            }
        }
        $_SERVER = array_merge($token, $_SERVER);
    }
}
