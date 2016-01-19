<?php

namespace common\components;

use Yii;

class GraphHopper{

    public $apiServerUrl; // https://graphhopper.com/api/1

    public $apiKey;

    /*
     *
    https://graphhopper.com/api/1/route?point=51.131108%2C12.414551&point=48.224673%2C3.867187&vehicle=car&locale=de&debug=true&points_encoded=false&key=[YOUR_KEY]
    https://graphhopper.com/api/1/route?point=49.932707,11.588051&point=50.3404,11.64705&vehicle=car&debug=true&key=3090ca2b-a4b6-422b-be18-24f7a68422d9&type=json&calc_points=false&instructions=false

    http://traffic.dev/graphhopper/route?from=51.131108,12.414551&to=48.224673,3.867187

     */
    public function getRoute($from, $to){

        $query = http_build_query([
            'vehicle' => 'car',
            'locale' => 'en',
//            'debug' => YII_DEBUG ? 'true': 'false',
            'key' => $this->apiKey,
            'points_encoded' => 'false',
//            'calc_points' => 'true',
//            'instructions' => 'true',
            'point' => [$from, $to],
        ]);

        // remove [0]
        $query = preg_replace('/(%5B[0-9]*%5D)/i', '', $query);


        $apiUrl = http_build_url($this->apiServerUrl . "/route", [
            'query' => $query,
        ], HTTP_URL_JOIN_PATH | HTTP_URL_JOIN_QUERY);

        Yii::info('api url = ' . $apiUrl);


        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        curl_close($ch);

        if ($response) {
            return $response;
        } else {
            return "<pre>" . print_r($response, true) . "</pre>";
        }

    }
}