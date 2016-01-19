<?php

namespace common\components;

use Yii;

use hightman\http\Client;
use hightman\http\Request;

class MapQuest
{

    public $apiServerUrl;
    public $serviceName;
    public $apiVersion;

    public $apiKey;

    /*

    http://www.mapquestapi.com/directions/v2/route?key=GJVt2ixNbY9BPoGYNDQItCAPQyE9G6L5&from=F-10,Islamabad&to=saddar,rawalpindi

     */
    public function getRoute($from, $to)
    {
        $http = new Client();

        $query = http_build_query([
            'key' => $this->apiKey,
            'from' => $from,
            'to' => $to,
        ]);

        $apiUrl = $this->apiServerUrl . "/" . $this->serviceName . "/" . $this->apiVersion . "/route";

        $apiUrl = http_build_url($apiUrl, [
            'query' => $query,
        ]);

        $request = new Request($apiUrl);
        $request->setMethod('GET');

        $response = $http->exec($request);

        if (!$response->hasError()) {
            return "<pre>" . print_r(json_decode($response->body, true), true) . "</pre>";
        } else {
            return "<pre>" . print_r($response, true) . "</pre>";
        }
    }
}