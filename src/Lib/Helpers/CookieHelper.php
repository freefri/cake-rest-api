<?php

declare(strict_types = 1);

namespace RestApi\Lib\Helpers;

use Cake\Core\Configure;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Cookie\CookieInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\ServerRequest;
use Cake\I18n\FrozenTime;
use DateTimeZone;

class CookieHelper
{
    public const ENCRIPT_METHOD = 'AES-256-CBC';
    public Cookie $cookie;

    private function _getConfig(): array
    {
        return explode(':', env('COOKIE_ENCRYPT_CONFIG', '::'));
    }

    protected function _getCookieName(): string
    {
        return $this->_getConfig()[0];
    }

    private function _getEncryptKey(): string
    {
        return $this->_getConfig()[1];
    }

    private function _getEncryptIv(): string
    {
        return $this->_getConfig()[2];
    }

    public function writeApi2Remember(string $accessToken, $expires = null): Cookie
    {
        return $this->writeWithName($this->getRememberName(), $accessToken, $expires);
    }

    public function writeWithName(string $cookieName, string $storedValue, $expires = null): Cookie
    {
        $key = $this->_getCookieName() . '[' . $cookieName . ']';
        return $this->writeCookie($key, $storedValue, $expires);
    }

    public function writeLoginChallenge(array $valueToStore, $expires = 20): Cookie
    {
        return $this->writeWithName($this->getLoginChallengeName(), json_encode($valueToStore), $expires);
    }

    public function decryptLoginChallenge(string $encrypted): array
    {
        $decrypted = $this->_decrypt($encrypted);
        if (!$decrypted) {
            throw new BadRequestException('LoginChallenge cannot be decrypted');
        }
        return json_decode($decrypted, true, JSON_UNESCAPED_SLASHES);
    }

    protected function writeCookie(string $key, string $storedValue, $expires = null): Cookie
    {
        $encryptedToken = openssl_encrypt(
            $storedValue,
            $this->getEncriptMethod(),
            $this->_getEncryptKey(),
            0,
            $this->_getEncryptIv()
        );
        if (!$expires) {
            $expires = Configure::read('Platform.User.rememberExpires');
        }
        if (!$expires) {
            $expires = Configure::read('App.Conf.defaultExpirationCookie');
        }
        if (!$expires) {
            throw new InternalErrorException('Default cookie expiration is mandatory, define: App.Conf.defaultExpirationCookie');
        }
        $expirationTime = new FrozenTime("+ $expires seconds", new DateTimeZone('GMT'));
        $this->cookie = new Cookie(
            $key,
            $encryptedToken,
            $expirationTime,
            null,
            null,
            true,
            true,
            CookieInterface::SAMESITE_NONE
        );
        return $this->cookie;
    }

    protected function getRememberName(): string
    {
        return env('REMEMBER_NAME_API', 'rememberapi2');
    }

    protected function getLoginChallengeName(): string
    {
        return env('COOKIE_LOGIN_CHALLENGE', 'loginchallenge');
    }

    protected function getEncriptMethod()
    {
        return self::ENCRIPT_METHOD;
    }

    public function readByName(string $cookieName, ServerRequest $request): string
    {
        $key = $this->_getCookieName() . '.' . $cookieName;
        return $this->readCookie($key, $request);
    }

    public function readApi2Remember(ServerRequest $request)
    {
        $key = $this->_getCookieName() . '.' . $this->getRememberName();
        return $this->readCookie($key, $request);
    }

    protected function readCookie(string $key, ServerRequest $request)
    {
        $token = $request->getCookie($key);
        if (!$token) {
            return '';
        }
        return $this->_decrypt($token);
    }

    private function _decrypt($string)
    {
        return openssl_decrypt(
            $string,
            $this->getEncriptMethod(),
            $this->_getEncryptKey(),
            0,
            $this->_getEncryptIv()
        );
    }

    public function popApi2Remember(ServerRequest $request)
    {
        $token = $this->readApi2Remember($request);
        $this->writeApi2Remember((string)time(), 1);
        return $token;
    }
}
