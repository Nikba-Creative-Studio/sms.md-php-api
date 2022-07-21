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
     * Get Balance.
     * @return int Balance.
     */
     public function getBalance() {
        $request = new Request('GET', self::API_URL . self::API_VERSION . '/balance');
        $client = new Client();
        $response = $client->send($request, [
            'token' => $this->apiToken
        ]);
        $this->apiResponse = json_decode($response->getBody()->getContents(), true);
        return $this->apiResponse['balance'];
    }


    /**
     * Send SMS.
     * @param string $phoneNumber Phone number.
     * @param string $message Message.
     * @param string $sender Sender.
     * @return array API response.
     */

    public function sendSms($phoneNumber, $message, $sender = null) {
        $request = new Request('POST', self::API_URL . self::API_VERSION . '/send');
        $client = new Client();
        $response = $client->send($request, 
            [
                'token' => $this->apiToken,
                'to' => $phoneNumber,
                'message' => $message,
                'from' => $sender,
            ]);
        $this->apiResponse = json_decode($response->getBody()->getContents(), true);
        return $this->apiResponse;
    }

    
}