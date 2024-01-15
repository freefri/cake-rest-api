<?php

namespace RestApi\Lib\Validator;

use Cake\Validation\Validator;

class RestApiValidator extends Validator
{

    public function justLetters(string $field, ?string $message = null)
    {
        return $this->add($field, 'invalid-letters',
            [
                'rule' => ['custom', '/^[^0-9\\\<\>#&%$?!=]+$/'],
                'message' => $message,
            ]
        );
    }

    public function phone(string $field, ?string $message = null, $when = null)
    {
        return $this->add($field, 'invalid-phone',
            [
                'rule' => ['custom', '/^[\+]{0,1}[0-9\ \(\)\/-]{0,60}$/i'],
                'message' => $message,
            ]
        )
            ->allowEmptyString($field, $message)
            ->minLength($field, 7, $message, $when);
    }

    public function gender(string $field, ?string $message = null)
    {
        return $this->add($field, 'invalid-gender', [
            'rule' => ['custom', '/^[m fo]$/'],
            'message' => $message,
        ]);
    }

    public function iban(string $field, ?string $message = null)
    {
        return $this->add($field, 'invalid-iban', [
            'rule' => [$this, '_validateIban'],
            'message' => $message
        ]);
    }
    public function _validateIban($value, array $context)
    {
        return Iban::validateIban($value);
    }

    public function bic(string $field, ?string $message = null)
    {
        return $this->add($field, 'invalid-bic', [
            'rule' => [$this, '_validateBic'],
            'message' => $message
        ]);
    }
    public function _validateBic($value, array $context)
    {
        return Iban::validateBic($value);
    }

    public function emoji(string $field, ?string $message = null)
    {
        return $this->add($field, 'invalid-emoji', [
            'rule' => [$this, '_validateEmoji'],
            'message' => $message
        ]);
    }
    public function _validateEmoji($value, array $context): bool
    {
        return Emoji::validateEmoji($value);
    }
}
