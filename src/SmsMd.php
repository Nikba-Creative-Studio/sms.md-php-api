<?php

namespace Nikba\SmsMdPhpApi;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * SMS.MD API PHP wrapper
 * Send and receive SMS using sms.com
 *
 * @see https://partner.sms.md/api/doc SMS.MD API.
 * @author Bargan Nicolai <office@nikba.com>
 */
class SmsMd {
    private const API_URL = 'https://api.sms.md/';
    private const API_VERSION = 'v1';

    private string $apiToken;
    private bool $validatePhoneNumbers = true;
    private array $apiResponse = [];
    private Client $client;

    public function __construct(string $apiToken) {
        $this->apiToken = $apiToken;
        $this->client = new Client();
    }

    private function request(string $method, string $endpoint, array $params = []): array {
        $params['token'] = $this->apiToken;
        $url = self::API_URL . self::API_VERSION . '/' . $endpoint . '?' . http_build_query($params);
        $response = $this->client->send(new Request($method, $url));
        $this->apiResponse = json_decode($response->getBody()->getContents(), true);
        return $this->apiResponse;
    }

    private function validatePhoneNumber(string $phoneNumber): string {
        if ($this->validatePhoneNumbers) {
            $phoneNumber = preg_replace('/^[0-9]{10}+$/', '', $phoneNumber);
            if (strlen($phoneNumber) !== 11) {
                throw new \Exception('Invalid phone number.');
            }
        }
        return $phoneNumber;
    }

    public function getBalance(): int {
        $this->request('GET', 'balance');
        return $this->apiResponse['balance'];
    }

    public function sendSms(string $to, string $message, ?string $from = null): array {
        $to = $this->validatePhoneNumber($to);
        $params = [
            'from' => $from,
            'to' => $to,
            'message' => $message,
        ];
        $this->request('GET', 'send', $params);
        return $this->apiResponse;
    }

    public function getMessages(int $page = 1, ?string $dateFrom = null, ?string $dateTo = null, ?string $status = null): array {
        $params = compact('page', 'dateFrom', 'dateTo', 'status');
        $this->request('GET', 'message', $params);
        return $this->apiResponse;
    }

    public function getMessage(int $id): array {
        $this->request('GET', 'message/' . $id);
        return $this->apiResponse;
    }

    public function getMessageStatuses(): array {
        $this->request('GET', 'message/status');
        return $this->apiResponse;
    }

    public function getSenderAliases(): array {
        $this->request('GET', 'sender-alias');
        return $this->apiResponse;
    }

    public function getStats(): array {
        $this->request('GET', 'stats');
        return $this->apiResponse;
    }

    public function getContacts(int $page = 1): array {
        $params = ['page' => $page];
        $this->request('GET', 'contact', $params);
        return $this->apiResponse;
    }

    public function getAddressBooks(int $page = 1): array {
        $params = ['page' => $page];
        $this->request('GET', 'address-book', $params);
        return $this->apiResponse;
    }

    public function getAddressBookContacts(string $id, int $page = 1): array {
        $params = ['page' => $page];
        $this->request('GET', 'address-book/' . $id . '/contact', $params);
        return $this->apiResponse;
    }
}
