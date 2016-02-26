<?php

namespace common\components;

use Yii;
use yii\base\Component;
use Httpful\Request;
use yii\console\Response;

class GoogleMaps extends Component
{

    public $apiUrl = 'https://maps.googleapis.com/maps/api';

    public $apiKey;

    public $language;

    public function init()
    {
        parent::init();

        $this->language = Yii::$app->language;
    }

    public function geocode($address, $region = null)
    {

        if( $region == null ) $region = Yii::$app->params['defaultLocation'];

        $query = http_build_query([
            'key' => $this->apiKey,
            'address' => $address,
            'region' => $region,
            'language' => $this->language,
        ]);

        $url = http_build_url($this->apiUrl . '/geocode/json', [
            'query' => $query,
        ]);

        Yii::info('google api url = ' . $url);

        /** @var \Httpful\Response $response */
        $response = Request::get($url)->send();

        if ($response->body->status === 'OK' && !empty($response->body->results)) {
            return $response->body->results;
        } else {
            //TODO generate exception
            return null;
        }

    }

    public function route($from, $to, $region = null)
    {
        if( $region == null ) $region = Yii::$app->params['defaultLocation'];

        $query = http_build_query([
            'key' => $this->apiKey,
            'origin' => $from,
            'destination' => $to,
            'region' => $region,
            'language' => $this->language,
            'mode' => 'driving',
            'units' => 'metric',
            'departure_time' => 'now',
            'traffic_model' => 'best_guess',
        ]);

        $url = http_build_url($this->apiUrl . '/directions/json', [
            'query' => $query,
        ]);

        Yii::info('google api url = ' . $url);

        /** @var \Httpful\Response $response */
        $response = Request::get($url)->send();

        if ($response->body->status === 'OK' && !empty($response->body->routes)) {
            return $response->body->routes;
        } else {
            //TODO generate exception
            return null;
        }

    }

}