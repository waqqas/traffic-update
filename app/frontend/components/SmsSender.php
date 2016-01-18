<?php

namespace frontend\components;

use Yii;
use GuzzleHttp\Client;

class SmsSender
{
    public $username;
    public $password;
    public $originator;

    public $smsGatewayUrl;

    public function send($recipient, $text)
    {

        $client = new Client();

        try {
            $response = $client->get($this->smsGatewayUrl, [
                'query' => [
                    'username' => $this->username,
                    'password' => $this->password,
                    'originator' => $this->originator,
                    'messagedata' => $text,
                    'recipient' => $recipient,
                    'action' => 'sendmessage',
                ]
            ]);

            Yii::trace(print_r($response, true));

        } catch (RequestException $e) {
            Yii::error(print_r($e,true));
            return;
        }


    }
}