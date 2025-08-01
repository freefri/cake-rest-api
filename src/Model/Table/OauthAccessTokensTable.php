<?php

namespace RestApi\Model\Table;

use Cake\Cache\Cache;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Exception\NotImplementedException;
use Cake\Http\Exception\UnauthorizedException;
use Cake\I18n\FrozenTime;
use OAuth2\ResponseType\AccessToken;
use OAuth2\Storage\AccessTokenInterface;
use OAuth2\Storage\AuthorizationCodeInterface;
use OAuth2\Storage\ClientCredentialsInterface;
use OAuth2\Storage\PublicKeyInterface;
use OAuth2\Storage\UserCredentialsInterface;
use RestApi\Model\Entity\OauthAccessToken;

/**
 * @property RestApiTable $OauthPublicKeys
 * @property RestApiTable $OauthClients
 * @property RestApiTable $Users
 */
#[\AllowDynamicProperties] class OauthAccessTokensTable extends RestApiTable implements
    PublicKeyInterface, ClientCredentialsInterface, AccessTokenInterface,
    AuthorizationCodeInterface, UserCredentialsInterface
{
    const CACHE_GROUP = 'acl';

    protected ?string $_entityClass = OauthAccessToken::class;

    public function initialize(array $config): void
    {
        $this->belongsTo('OauthPublicKeys');
        $this->belongsTo('OauthClients');
        $this->belongsTo('OauthAuthorizationCodes');
        $this->belongsTo('Users');
    }

    public static function load(): OauthAccessTokensTable
    {
        /** @var OauthAccessTokensTable $table */
        $table = parent::load();
        return $table;
    }

    protected function getTablePrefix(): string
    {
        return '';
    }

    public function deleteAccessTokenByUserId($uId)
    {
        if (!$uId || !is_numeric($uId)) {
            throw new BadRequestException('uId needs to be numeric');
        }
        $this->deleteAccessTokensCacheByUserId($uId);
        $this->deleteAll(['user_id' => $uId]);
    }

    public function deleteAccessTokensCacheByUserId($userId)
    {
        $tokens = $this->find()->where(['user_id' => $userId]);
        foreach ($tokens as $token) {
            $cacheKey = $this->_getAccessTokenCacheKey($token['access_token']);
            Cache::delete($cacheKey, self::CACHE_GROUP);
        }
    }

    public function deleteAllAccessTokensCache()
    {
        $tokens = $this->find();
        foreach ($tokens as $token) {
            $cacheKey = $this->_getAccessTokenCacheKey($token['access_token']);
            Cache::delete($cacheKey, self::CACHE_GROUP);
        }
    }

    public function getPublicKey($client_id = null)
    {
        $res = $this->_findPublicKeyByClientID($client_id);
        if (isset($res['public_key'])) {
            return $res['public_key'];
        }
        return false;
    }

    public function getPrivateKey($client_id = null)
    {
        throw new NotImplementedException('See /OAuth2/Storage/Pdo');
    }

    public function getEncryptionAlgorithm($client_id = null)
    {
        $res = $this->_findPublicKeyByClientID($client_id);
        if (isset($res['encryption_algorithm'])) {
            return $res['encryption_algorithm'];
        }
        return false;
    }

    private function _findPublicKeyByClientID($id)
    {
        $cacheKey = '_findPublicKeyByClientID' . $id;
        $res = Cache::read($cacheKey, self::CACHE_GROUP);
        if ($res !== null) {
            return $res;
        }
        $toret = $this->OauthPublicKeys->find()->where(['client_id' => $id])->first();
        Cache::write($cacheKey, $toret, self::CACHE_GROUP);
        return $toret;
    }

    public function getClientDetails($client_id)
    {
        $cacheKey = 'getClientDetails' . $client_id;
        $cached = Cache::read($cacheKey, self::CACHE_GROUP);
        if ($cached !== null) {
            return $cached;
        }
        $toret = $this->OauthClients->find()->where(['client_id' => $client_id])->first();
        if (!isset($toret['client_id'])) {
            Cache::write($cacheKey, false, self::CACHE_GROUP);
            return false;
        }
        $toret = $toret->toArray();
        Cache::write($cacheKey, $toret, self::CACHE_GROUP);
        return $toret;
    }

    public function getClientScope($client_id)
    {
        throw new NotImplementedException('See /OAuth2/Storage/Pdo');
    }

    public function checkRestrictedGrantType($client_id, $grant_type): bool
    {
        $details = $this->getClientDetails($client_id);
        if (isset($details['grant_types'])) {
            $grant_types = explode(' ', $details['grant_types']);

            return in_array($grant_type, (array)$grant_types);
        }

        // if grant_types are not defined, then none are restricted
        return true;
    }

    public function expireAccessToken(string $token)
    {
        Cache::delete($this->_getAccessTokenCacheKey($token), self::CACHE_GROUP);
        $res = $this->updateAll(['expires' => new FrozenTime()], ['access_token' => $token]);
        if (!$res) {
            throw new InternalErrorException('Error deleting token');
        }
        return $res;
    }

    public function getAccessToken($oauth_token)
    {
        $cacheKey = $this->_getAccessTokenCacheKey($oauth_token);
        $cached = Cache::read($cacheKey, self::CACHE_GROUP);
        if ($cached !== null) {
            return $cached;
        }
        /** @var OauthAccessToken $r */
        $r = $this->find()
            ->where(['access_token' => $oauth_token])
            ->first();
        if (isset($r['expires'])) {
            $toRet = $r->toArray();
            if (!$r->expires->isFuture()) {
                Cache::write($cacheKey, null, self::CACHE_GROUP);
                return false;
            }
            $toRet['expires'] = $r->expires->getTimestamp();
            Cache::write($cacheKey, $toRet, self::CACHE_GROUP);
            return $toRet;
        } else {
            Cache::write($cacheKey, null, self::CACHE_GROUP);
            return false;
        }
    }

    protected function _getAccessTokenCacheKey(string $oauth_token): string
    {
        return 'getAccessToken' . $oauth_token;
    }

    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null): bool
    {
        $expires = new FrozenTime(date('Y-m-d H:i:s', $expires), 'UTC');
        if ($this->getAccessToken($access_token)) {
            // if it exists, update it.
            throw new NotImplementedException('See /OAuth2/Storage/Pdo');
        } else {
            $data = [
                'access_token' => $access_token,
                'client_id' => $client_id,
                'user_id' => $user_id,
                'expires' => $expires,
                'scope' => $scope
            ];
            return !!$this->save($this->newEntity($data));
        }
    }

    public function getAuthorizationCode($code)
    {
        $code = $this->_findAuthorizationCodes($code);
        if (!$code) {
            return false;
        }
        /** @var FrozenTime $expires */
        $expires = $code->expires;
        if ($expires->isPast()) {
            return false;
        }
        $code = $code->toArray();
        $code['expires'] = $expires->getTimestamp();
        return $code;
    }

    private array $_oauthAuthorizationCodes = [];// use only in-memory store

    private function _findAuthorizationCodes($code)
    {
        return $this->OauthAuthorizationCodes->find()->where(['authorization_code' => $code])->firstOrFail();
    }

    public function setAuthorizationCode(
        $authorization_code,
        $client_id,
        $user_id,
        $redirect_uri,
        $expires,
        $scope = null,
        $n = null,
        $codeChallenge = null,
        $codeChallengeMethod = null
    ): bool {
        $entity = $this->OauthAuthorizationCodes->newEmptyEntity();
        $entity->authorization_code = $authorization_code;
        $entity->client_id = $client_id;
        $entity->user_id = $user_id;
        $entity->redirect_uri = $redirect_uri;
        $entity->expires = new FrozenTime($expires);
        $entity->scope = $scope;
        $entity->code_challenge = $codeChallenge;
        $entity->code_challenge_method = $codeChallengeMethod;
        return $this->_saveAuthorizationCodes($entity);
    }

    private function _saveAuthorizationCodes($entity): bool
    {
        $this->OauthAuthorizationCodes->saveOrFail($entity);
        return true;
    }

    public function expireAuthorizationCode($code)
    {
        $entity = $this->_findAuthorizationCodes($code);
        $entity->expires = new FrozenTime();
        $this->OauthAuthorizationCodes->saveOrFail($entity);
    }

    public function getUserDetails($username)
    {
        $user = $this->Users->find()->where(['email' => $username])
            ->first();
        if (!isset($user['id'])) {
            return [];
        }
        $user['user_id'] = $user['id'];
        return $user;
    }

    public function checkUserCredentials($username, $password): bool
    {
        return true;// The auth must be check before through AuthExtComponent
    }

    public function checkClientCredentials($client_id, $client_secret = null)
    {
        throw new NotImplementedException('See /OAuth2/Storage/Pdo');
    }

    public function isPublicClient($client_id): bool
    {
        $res = $this->getClientDetails($client_id);
        if (!$res) {
            return false;
        }
        return empty($res['client_secret']);
    }

    public function createBearerToken($userId, $clientId, $secondsLifetime = 3600): array
    {
        if (!$this->getClientDetails($clientId)) {
            throw new BadRequestException('Invalid client id: ' . $clientId);
        }
        $config = [
            'access_lifetime' => $secondsLifetime,
            'token_type' => 'Bearer'
        ];
        $accessToken = new AccessToken($this, null, $config);
        $res = $accessToken->createAccessToken($clientId, $userId);
        if (!$res['access_token'] ?? false) {
            throw new InternalErrorException('Access token could not be generated');
        }
        return $res;
    }

    public function checkUserAccessToken($userId, $token): void
    {
        $userToken = $this->find()
            ->where(['user_id' => $userId, 'access_token' => $token])
            ->first();
        if (!$userToken) {
            throw new UnauthorizedException('Invalid user credentials');
        }
    }

    public function expireUserTokens($userId): void
    {
        $oauthTokens = $this->find()
            ->where(['user_id' => $userId, 'expires >' => new FrozenTime('now')])
            ->all();
        /** @var OauthAccessToken $token */
        foreach ($oauthTokens as $token) {
            $token->expires = new FrozenTime('now');
            $this->expireAccessToken($token->access_token);
        }
        $this->saveMany($oauthTokens);
    }

    public function getValidAccessToken(): string
    {
        /** @var OauthAccessToken $oauthAccessToken */
        $oauthAccessToken = $this->find()
            ->where(['expires >' => new FrozenTime('+1 hour') ])
            ->first();
        return $oauthAccessToken->access_token;
    }
}
