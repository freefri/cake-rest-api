<?php

namespace RestApi\Lib\Helpers;

use Cake\Core\Configure;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Cookie\CookieInterface;
use Cake\Http\ServerRequest;
use Cake\I18n\FrozenTime;
use DateTimeZone;

class CookieHelper
{
    const ENCRIPT_METHOD = 'AES-256-CBC';

    private function _getConfig(): array
    {
        return explode(':', env('COOKIE_ENCRYPT_CONFIG', '::'));
    }

    private function _getCookieName(): string
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

    public function writeApi2Remember($accessToken, $expires = null)
    {
        $encryptedToken = openssl_encrypt(
            $accessToken,
            self::ENCRIPT_METHOD,
            $this->_getEncryptKey(),
            null,
            $this->_getEncryptIv()
        );
        if (!$expires) {
            $expires = Configure::read('Platform.User.rememberExpires');
        }
        $expirationTime = new FrozenTime("+ $expires seconds", new DateTimeZone('GMT'));
        $key = $this->_getCookieName() . '[' . $this->getRememberName() . ']';
        return new Cookie(
            $key,
            $encryptedToken,
            $expirationTime,
            null,
            null,
            true,
            true,
            CookieInterface::SAMESITE_NONE
        );
    }

    protected function getRememberName(): string
    {
        return env('REMEMBER_NAME_API', 'rememberapi2');
    }

    public function readApi2Remember(ServerRequest $request)
    {
        $token = $request->getCookie($this->_getCookieName() . '.' . $this->getRememberName());
        return openssl_decrypt(
            $token,
            self::ENCRIPT_METHOD,
            $this->_getEncryptKey(),
            null,
            $this->_getEncryptIv()
        );
    }

    public function popApi2Remember(ServerRequest $request)
    {
        $token = $this->readApi2Remember($request);
        $this->writeApi2Remember(time(), 1);
        return $token;
    }
}
