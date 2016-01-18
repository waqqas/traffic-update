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
        $parts = [
            'query' => http_build_query([
                'username' => $this->username,
                'password' => $this->password,
                'originator' => $this->originator,
                'action' => 'sendmessage',
                'recipient' => $recipient,
                'messagedata' => $text,
            ])
        ];

        $url = http_build_url($this->smsGatewayUrl, $parts, HTTP_URL_JOIN_PATH | HTTP_URL_JOIN_QUERY | HTTP_URL_STRIP_FRAGMENT);

//        Yii::error("url = " . $url);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        curl_close($ch);

        Yii::error("response = ", print_r($response, true));
    }
}