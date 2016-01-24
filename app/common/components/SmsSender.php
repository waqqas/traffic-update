<?php

namespace common\components;

use Yii;

class SmsSender
{
    public $username;
    public $password;
    public $originator;

    public $smsGatewayUrl;

    public $lastMsgInfo = null;

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

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        curl_close($ch);

        // parse xml
        $xml = simplexml_load_string($response);
        $json = json_encode($xml);
        $array = json_decode($json, true);

        $this->lastMsgInfo = $array['data']['acceptreport'];

        // 0 means success
        return $this->lastMsgInfo['statuscode']? false: true;
    }

    public static function queueSend($msisdn, $sms){
        $query = http_build_query([
            'to' => $msisdn,
            'text' => $sms,
        ]);
        $url = http_build_url(Yii::$app->params['serverName'],[
            'path' => '/sms/mt',
            'query' => $query,

        ]);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //TODO check response and update status
        curl_exec($ch);

        curl_close($ch);
    }
}