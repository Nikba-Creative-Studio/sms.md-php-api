<?php

declare(strict_types=1);

namespace Nikba\SmsMdPhpApi\Tests;

use Http\Mock\Client as MockClient;
use Nikba\SmsMdPhpApi\Enum\Destination;
use Nikba\SmsMdPhpApi\Enum\Encoding;
use Nikba\SmsMdPhpApi\Enum\MessageStatus;
use Nikba\SmsMdPhpApi\Exception\AuthenticationException;
use Nikba\SmsMdPhpApi\Exception\InsufficientBalanceException;
use Nikba\SmsMdPhpApi\Exception\TransportException;
use Nikba\SmsMdPhpApi\Exception\ValidationException;
use Nikba\SmsMdPhpApi\SmsMd;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class SmsMdTest extends TestCase
{
    private MockClient $http;
    private Psr17Factory $factory;
    private SmsMd $sms;

    protected function setUp(): void
    {
        $this->http = new MockClient();
        $this->factory = new Psr17Factory();
        $this->sms = new SmsMd('TEST_TOKEN', $this->http, $this->factory, $this->factory);
    }

    private function queue(int $status, array $body): void
    {
        $this->http->addResponse(new Response(
            $status,
            ['Content-Type' => 'application/json'],
            json_encode($body, JSON_THROW_ON_ERROR),
        ));
    }

    private function lastRequest(): RequestInterface
    {
        return $this->http->getLastRequest();
    }

    public function testSendMessagePostsJsonBodyWithAuthHeader(): void
    {
        $this->queue(200, [
            'status' => 'success',
            'httpCode' => 200,
            'data' => ['id' => 'abc', 'segments' => 1, 'encoding' => 'gsm-7', 'cost' => '0.30'],
        ]);

        $data = $this->sms->sendMessage('69123456', 'Your code is 1234', 'sms.md');

        $request = $this->lastRequest();
        self::assertSame('POST', $request->getMethod());
        self::assertStringEndsWith('/v3/messages', (string) $request->getUri());
        self::assertSame('TEST_TOKEN', $request->getHeaderLine('X-Api-Token'));
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));

        $sent = json_decode((string) $request->getBody(), true);
        self::assertSame(['from' => 'sms.md', 'to' => '69123456', 'text' => 'Your code is 1234'], $sent);

        self::assertSame('abc', $data['id']);
        self::assertSame(Encoding::Gsm7, Encoding::from($data['encoding']));
    }

    public function testSendMessageIncludesScheduledTime(): void
    {
        $this->queue(200, ['status' => 'success', 'data' => []]);
        $this->sms->sendMessage('69123456', 'Hi', 'sms.md', '2026-08-07 14:30:00');

        $sent = json_decode((string) $this->lastRequest()->getBody(), true);
        self::assertSame('2026-08-07 14:30:00', $sent['sendAt']);
    }

    public function testListMessagesConvertsStatusEnumAndReturnsMeta(): void
    {
        $this->queue(200, [
            'status' => 'success',
            'data' => [['id' => '1'], ['id' => '2']],
            'meta' => ['currentPage' => 1, 'total' => 2],
        ]);

        $result = $this->sms->listMessages(['status' => MessageStatus::Delivered], 2);

        $uri = (string) $this->lastRequest()->getUri();
        self::assertStringContainsString('page=2', $uri);
        self::assertStringContainsString('status=3', $uri);
        self::assertCount(2, $result['data']);
        self::assertSame(2, $result['meta']['total']);
    }

    public function testGetBalanceReturnsStringToPreservePrecision(): void
    {
        $this->queue(200, ['status' => 'success', 'data' => ['balance' => '123.45', 'currency' => 'MDL']]);
        self::assertSame('123.45', $this->sms->getBalance());
        self::assertStringEndsWith('/v3/account/balance', (string) $this->lastRequest()->getUri());
    }

    public function testValidationErrorIsThrownWithFieldMessages(): void
    {
        $this->queue(422, [
            'status' => 'error',
            'httpCode' => 422,
            'code' => 'VALIDATION_ERROR',
            'message' => 'An error occurred.',
            'errors' => ['to' => ['The number is invalid.']],
        ]);

        try {
            $this->sms->sendMessage('bad', 'Hi', 'sms.md');
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame('VALIDATION_ERROR', $e->getErrorCode());
            self::assertSame(422, $e->getHttpCode());
            self::assertSame('The number is invalid.', $e->firstError('to'));
        }
    }

    public function testInsufficientBalanceMapsToTypedException(): void
    {
        $this->queue(402, [
            'status' => 'error',
            'httpCode' => 402,
            'code' => 'INSUFFICIENT_BALANCE',
            'message' => 'An error occurred.',
        ]);

        $this->expectException(InsufficientBalanceException::class);
        $this->sms->sendMessage('69123456', 'Hi', 'sms.md');
    }

    public function testInvalidTokenMapsToAuthenticationException(): void
    {
        $this->queue(401, [
            'status' => 'error',
            'httpCode' => 401,
            'code' => 'INVALID_API_TOKEN',
            'message' => 'An error occurred.',
        ]);

        $this->expectException(AuthenticationException::class);
        $this->sms->getBalance();
    }

    public function testVerifyOtpReturnsTrueOnSuccess(): void
    {
        $this->queue(200, ['status' => 'success', 'data' => ['message' => 'OTP code verified successfully.']]);
        self::assertTrue($this->sms->verifyOtp('69123456', '482913', 'a'.str_repeat('0', 47)));
    }

    public function testUndecodableBodyRaisesTransportException(): void
    {
        $this->http->addResponse(new Response(200, ['Content-Type' => 'application/json'], '<<not json>>'));
        $this->expectException(TransportException::class);
        $this->sms->getBalance();
    }

    public function testSendToInternationalNumberPassesThroughAndSurfacesDestination(): void
    {
        $this->queue(200, [
            'status' => 'success',
            'data' => [
                'id' => 'intl-1',
                'to' => '+40712345678',
                'destination' => 'international',
                'countryIso' => 'RO',
                'countryName' => 'Romania',
                'cost' => '1.34',
                'currency' => 'MDL',
            ],
        ]);

        $data = $this->sms->sendMessage('+40712345678', 'Salut', 'sms.md');

        // The number is sent through unchanged — no special handling required.
        $sent = json_decode((string) $this->lastRequest()->getBody(), true);
        self::assertSame('+40712345678', $sent['to']);

        self::assertSame(Destination::International, Destination::from($data['destination']));
        self::assertSame('RO', $data['countryIso']);
        self::assertSame('1.34', $data['cost']);
    }

    public function testEstimateReturnsInternationalCountryAndPrice(): void
    {
        $this->queue(200, [
            'status' => 'success',
            'data' => [
                'to' => '+40712345678',
                'segments' => 1,
                'destination' => 'international',
                'countryIso' => 'RO',
                'countryName' => 'Romania',
                'cost' => '1.34',
            ],
        ]);

        $e = $this->sms->estimate('+40712345678', 'Salut');
        self::assertSame('international', $e['destination']);
        self::assertSame('Romania', $e['countryName']);
    }

    public function testInternationalDisabledSurfacesUnderUnderscoreKey(): void
    {
        $this->queue(422, [
            'status' => 'error',
            'httpCode' => 422,
            'code' => 'VALIDATION_ERROR',
            'message' => 'An error occurred.',
            'errors' => ['_' => ['The phone number is not allowed. International numbers are not supported for your account.']],
        ]);

        try {
            $this->sms->sendMessage('+40712345678', 'Salut', 'sms.md');
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertStringContainsString('International numbers are not supported', (string) $e->firstError('_'));
        }
    }

    public function testBulkReturnsRejectedInternationalNumbers(): void
    {
        $this->queue(200, [
            'status' => 'success',
            'data' => [
                'bulkId' => 'bulk-1',
                'queued' => 1,
                'rejected' => ['+1202555000'], // country not allowed for this account
            ],
        ]);

        $data = $this->sms->sendBulk(['69123456', '+1202555000'], 'Hi', 'sms.md');
        self::assertSame(1, $data['queued']);
        self::assertSame(['+1202555000'], $data['rejected']);
    }

    public function testLegacySendUsesTokenQueryParam(): void
    {
        $this->queue(200, ['id' => 'uuid', 'statusId' => 2]);
        $this->sms->legacy()->send('69123456', 'sms.md', 'Hi');

        $uri = (string) $this->lastRequest()->getUri();
        self::assertStringContainsString('/v1/send', $uri);
        self::assertStringContainsString('token=TEST_TOKEN', $uri);
        self::assertSame('', $this->lastRequest()->getHeaderLine('X-Api-Token'));
    }
}
