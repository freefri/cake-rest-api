<?php

namespace RestApi\Lib\Oauth;

use Cake\Http\Exception\ForbiddenException;
use Cake\Http\ServerRequest;

class ScopeRules
{
    protected string $defaultRule;

    public function setDefaultRule(string $rule): self
    {
        $this->defaultRule = $rule;
        return $this;
    }

    public function validate(AccessTokenEntity $token, ServerRequest $request): ?bool
    {
        $log = $token->getAccessToken() . ' -> ' . $request->getMethod() . ' ' . $this->defaultRule;
        if (!$token->getScopes()) {
            return null;
        }
        foreach ($token->getScopes() as $scope) {
            if ($scope === $this->defaultRule . '.readonly') {
                if (strtoupper($request->getMethod()) === 'GET') {
                    return true;
                } else {
                    throw new ForbiddenException('Scope readonly not allowed with the token ' . $log);
                }
            }
            if ($scope === $this->defaultRule) {
                return true;
            }
        }
        throw new ForbiddenException('Scope not allowed with this token ' . $log);
    }
}
