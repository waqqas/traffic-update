<?php

namespace common\components;

use Yii;

class SmsSender
{
    public $username;
    public $password;
    public $originator;

    public $smsGatewayUrl;

    public function send($recipient, $text)
    {
        $url = http_build_url($this->smsGatewayUrl, [
            'query' => [
                'username' => $this->username,
                'password' => $this->password,
                'originator' => $this->originator,
                'messagedata' => $text,
                'recipient' => $recipient,
                'action' => 'sendmessage',
            ]
        ],
            HTTP_URL_STRIP_AUTH | HTTP_URL_JOIN_PATH | HTTP_URL_JOIN_QUERY | HTTP_URL_STRIP_FRAGMENT
        );

        Yii::trace('url = '. $url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        curl_close($ch);

        Yii::trace("response = ", print_r($response, true));
    }
}