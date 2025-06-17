<?php

namespace RestApi\Lib\Validator;

class Emoji
{
    // phpcs:ignore
    private static $_emojiRegex = '/^([*#0-9](?>\\xEF\\xB8\\x8F)?\\xE2\\x83\\xA3|\\xC2[\\xA9\\xAE]|\\xE2..'
    . '(\\xF0\\x9F\\x8F[\\xBB-\\xBF])?'
    . '(?>\\xEF\\xB8\\x8F)?|\\xE3(?>\\x80[\\xB0\\xBD]|\\x8A[\\x97\\x99])(?>\\xEF\\xB8\\x8F)?|\\xF0\\x9F(?>'
    . '[\\x80-\\x86].(?>\\xEF\\xB8\\x8F)?|\\x87.\\xF0\\x9F\\x87.|..(\\xF0\\x9F\\x8F[\\xBB-\\xBF])?|'
    . '(((?<zwj>\\xE2\\x80\\x8D)\\xE2\\x9D\\xA4\\xEF\\xB8\\x8F\k<zwj>\\xF0\\x9F..(\k<zwj>\\xF0\\x9F\\x91.)?|'
    . '(\\xE2\\x80\\x8D\\xF0\\x9F\\x91.){2,3}))?))$/';

    public static function validateEmoji($value): bool
    {
        return preg_match(self::$_emojiRegex, $value) === 1;
    }
}
