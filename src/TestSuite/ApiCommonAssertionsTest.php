<?php
declare(strict_types=1);

namespace RestApi\TestSuite;

use Cake\Core\Configure;
use Cake\Error\Debugger;
use Cake\Http\Exception\InternalErrorException;
use Cake\TestSuite\Fixture\FixtureStrategyInterface;
use Cake\TestSuite\Fixture\TransactionStrategy;

abstract class ApiCommonAssertionsTest extends ApiCommonTestCase
{
    /**
     * @var string|null
     * @deprecated use $this->loadAuthToken() instead
     */
    protected $currentAccessToken = null;

    protected function getFixtureStrategy(): FixtureStrategyInterface
    {
        return new TransactionStrategy();
    }

    protected function loadAuthToken($token)
    {
        $this->configRequest([
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
    }

    public function setUp(): void
    {
        parent::setUp();
        $origin = Configure::read('Testing.http_origin');
        if ($origin) {
            $_SERVER['HTTP_ORIGIN'] = $origin;
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();
        unset($_SERVER['HTTP_ORIGIN']);
    }

    protected function assertRedirectEqualsTo(string $url, string $message = ''): void
    {
        $body = $this->_getBodyAsString();
        $bodyDecoded = json_decode($body, true);
        if (!$message) {
            $message = 'Error assertRedirectTo is not status code 302:';
        }
        $this->assertEquals(302, $this->_response->getStatusCode(),
            $message . ' ' . Debugger::exportVar($bodyDecoded));
        $this->assertEquals($url, $this->_response->getHeader('Location')[0]);
    }

    public static function assertEqualsNoId($expected, $actual, string $message = ''): void
    {
        if (isset($actual['id'])) {
            unset($actual['id']);
        }
        if (isset($actual[0]['id'])) {
            foreach ($actual as &$elem) {
                unset($elem['id']);
            }
        }
        parent::assertEquals($expected, $actual, $message);
    }

    protected function assertJsonResponseOK(string $message = ''): array
    {
        $body = $this->_getBodyAsString();
        $bodyDecoded = json_decode($body, true);
        if (!$message) {
            $message = 'Error assertJsonResponseOK:';
        }
        $this->assertResponseOk($message . ' ' . Debugger::exportVar($bodyDecoded));
        return $bodyDecoded;
    }

    public function assertRedirectContains(string $url, string $message = ''): void
    {
        if (!$message) {
            $message = $this->_getBodyAsString();
        }
        parent::assertRedirectContains($url, $message);
    }

    protected function assertResponse204NoContent()
    {
        $this->assertEquals(204, $this->_response->getStatusCode());
        $this->assertEquals('', $this->_response->getBody());
    }

    protected function assertException(string $type, $code = null, $message = null): array
    {
        $body = $this->_getBodyAsString();
        $bodyDecoded = json_decode($body, true);
        $messageToDisplay = 'Error in assertException:' . ' ' . Debugger::exportVar($bodyDecoded);
        $this->assertEquals($type, $bodyDecoded['error'] ?? null, $messageToDisplay);
        if ($code) {
            $this->assertResponseCode($code, $messageToDisplay);
        }
        if ($message) {
            $this->assertStringStartsWith($message, $bodyDecoded['message'], $messageToDisplay);
        }
        return $bodyDecoded;
    }

    /**
     * @deprecated use assertJsonResponseOK() instead
     */
    protected function assertResponseJsonOK($expected)
    {
        throw new InternalErrorException('deprecated use assertJsonResponseOK() instead');
    }
    /**
     * @deprecated use assertException instead
     */
    protected function assertExceptionMessage(string $message, $code = null): array
    {
        if ($code) {
            $this->assertResponseCode($code);
        }
        $body = $this->_getBodyAsString();
        $bodyDecoded = json_decode($body, true);
        $this->assertEquals($message, $bodyDecoded['message']);
        return $bodyDecoded;
    }
    protected function assertValidationErrorMessage(array $message): array
    {
        $body = $this->_getBodyAsString();
        $bodyDecoded = json_decode($body, true);
        $this->assertEquals($message, $bodyDecoded['error_fields'] ?? [], $body);
        return $bodyDecoded;
    }

    protected function actionExpectsException($url, $method, $message)
    {
        $this->_sendRequest($this->_getEndpoint() . $url, strtoupper($method));
        $body = (string)$this->_response->getBody();
        $this->assertResponseError($body);
        $this->assertEquals($message, json_decode($body, true)['message']);
    }
}
