<?php

namespace Nikba\SmsMdPhpApi;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * SMS.MD API PHP wrapper
 *
 * Send and receive SMS using sms.com
 *
 * @see https://partner.sms.md/api/doc SMS.MD API.
 *
 * @author Bargan Nicolai <office@nikba.com>
 */

class SmsMd {
    /**
     * @var string API URL.
     */
    const API_URL = 'https://api.sms.md/';
    /**
     * @var string API version.
     */
    const API_VERSION = 'v1';
    /**
     * @var string API key.
     */
    
    /**
     * @var string API token.
     */
    private $apiToken;
    /**
     * @var bool Validate Phone numbers.
    */
    private $validatePhoneNumbers = true; 
    /*
    * @var array Raw Api Response
    */
    private $apiResponse = [];

    /** 
     * Constructor.
     * @param string $apiToken API token.
     */
    public function __construct($apiToken) {
        $this->apiToken = $apiToken;
    }

    /**
     * Request API.
     * @param string $method API method.
     * @param string $endpoint API endpoint.
     * @param array $params API parameters.
     * @return array API response.
     */
    private function request($method, $endpoint, $params = []) {
        $client = new Client();

        $params['token'] = $this->apiToken;
        $request = new Request($method, self::API_URL . self::API_VERSION . '/' . $endpoint . '?' . http_build_query($params));
        $response = $client->send($request);
        $this->apiResponse = json_decode($response->getBody()->getContents(), true);
        return $this->apiResponse;
    }

    /**
     * Validate phone number.
     * @param string $phoneNumber Phone number.
     * @return string Phone number.
     */
    private function validatePhoneNumber($phoneNumber) {
        if ($this->validatePhoneNumbers) {
            $phoneNumber = preg_replace('/^[0-9]{10}+$/', '', $phoneNumber);
            if (strlen($phoneNumber) != 11) {
                throw new \Exception('Invalid phone number.');
            }
        }
        return $phoneNumber;
    }

    /**
     * Get Balance.
     * @return int Balance.
     */
     public function getBalance() {
        $this->request('GET', 'balance');
        return $this->apiResponse['balance'];
    }

    /**
     * Send SMS.
     * @param string $from Sender.
     * @param string $to Phone Number.
     * @param string $message Message.
     * @return array API response.
     */

    public function sendSms($to, $message, $from = null) {

        // Validate phone number
        if($this->validatePhoneNumbers) {
            $to = $this->validatePhoneNumber($to);
        }

        $params = [
            'from' => $from,
            'to' => $to,
            'message' => $message,
        ];
        $this->request('GET', 'send', $params);
        return $this->apiResponse;
    }

    /**
     * Get Messages.
     * @param int $page Page.
     * @param string $dateFrom Date from. 01.07.2022
     * @param string $dateTo Date to. 20.07.2022
     * @param string $status Status. string 1-Ждет отпрки, 2-Отправлено, 3-Добавлено, 9-Ошибка отпраки
     * @return array API response.
     */
    public function getMessages($page = 1, $dateFrom = null, $dateTo = null, $status = null) {
        $params = [
            'page' => $page,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'status' => $status,
        ];
        $this->request('GET', 'message', $params);
        return $this->apiResponse;
    }

    /**
     * Get Message.
     * @param int $id Message ID.
     * @return array API response.
     */
    public function getMessage($id) {
        $this->request('GET', 'message/' . $id);
        return $this->apiResponse;
    }

    /**
     * Get Message status list
     * @return array API response.
     */
    public function getMessageStatuses() {
        $this->request('GET', 'message/status');
        return $this->apiResponse;
    }

    /**
     * Get all sender aliases
     * @return array API response.
     */
    public function getSenderAliases() {
        $this->request('GET', 'sender-alias');
        return $this->apiResponse;
    }

    /**
     * Get Stats
     * @return array API response.
     */
    public function getStats() {
        $this->request('GET', 'stats');
        return $this->apiResponse;
    }

    /** Get all contacts
     * @param int $page Page.
     * @return array API response.
     */
    public function getContacts($page = 1) {
        $params = [
            'page' => $page,
        ];
        $this->request('GET', 'contact', $params);
        return $this->apiResponse;
    }

    /**
     * Get all address books
     * @param int $page Page.
     * @return array API response.
     */
    public function getAddressBooks($page = 1) {
        $params = [
            'page' => $page,
        ];
        $this->request('GET', 'address-book', $params);
        return $this->apiResponse;
    }

    /**
     * Get all address book contacts
     * @param string $id Address book ID.
     * @param int $page Page.
     */
    public function getAddressBookContacts($id, $page = 1) {
        $params = [
            'page' => $page,
        ];
        $this->request('GET', 'address-book/' . $id . '/contact', $params);
        return $this->apiResponse;
    }
    
}