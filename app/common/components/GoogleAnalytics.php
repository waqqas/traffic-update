<?php

namespace common\components;

use yii\base\Component;
use Yii;
use Httpful\Request;

class GoogleAnalytics extends Component
{
    public $trackingId;
    public $clientId;
    public $apiUrl = 'http://www.google-analytics.com';

    public function track($data)
    {

        $commonParams = [
            'v' => 1,
            'tid' => $this->trackingId,
            'cid' => $this->clientId,
            'ul' => strtolower(Yii::$app->language),
            't' => 'event',
            'an' => Yii::$app->id,
        ];

        if (isset(Yii::$app->smsUser)) {
            $commonParams['uid'] = Yii::$app->smsUser->phoneNumber;
        }


        $query = http_build_query(array_merge($commonParams, $data));

//        Yii::info('query = ' . $query);

        try {
            /** @var \Httpful\Response $response */
            $response = Request::post($this->apiUrl . '/collect')->body($query)->send();

            return ($response->code == 200);

        } catch (\Exception $e) {
            return false;
        }


    }
}