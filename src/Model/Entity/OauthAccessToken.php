<?php
namespace RestApi\Model\Entity;

use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;

/**
 * @property FrozenTime expires
 * @property mixed access_token
 */
class OauthAccessToken extends Entity
{
    protected $_accessible = [
        '*' => false,
        'access_token' => true,
        'client_id' => true,
        'user_id' => true,
        'expires' => true,
        'scope' => true,
    ];

    //protected function _getExpires(FrozenTime $expires)
    //{
    //    return $expires->getTimestamp();
    //}
}
