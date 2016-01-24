<?php

namespace common\components;

use common\models\CongestionPt;
use Yii;

use hightman\http\Client;
use hightman\http\Request;
use DateTime;

class MapQuest
{

    public $apiServerUrl = 'http://www.mapquestapi.com';
    public $serviceName = 'directions';
    public $apiVersion = 'v2';

    public $apiKey;

    public $unit = 'k';
    public $ambiguities = 'ignore';
    public $routeType = 'fastest';

    /*

    http://www.mapquestapi.com/directions/v2/route?key=GJVt2ixNbY9BPoGYNDQItCAPQyE9G6L5&from=F-10,Islamabad&to=saddar,rawalpindi

     */
    public function route($from, $to)
    {
        $http = new Client();

        $query = http_build_query([
            'key' => $this->apiKey,
        ]);

        $apiUrl = $this->apiServerUrl . "/" . $this->serviceName . "/" . $this->apiVersion . "/route";

        $apiUrl = http_build_url($apiUrl, [
            'query' => $query,
        ]);

        // get congestion points

        $controlPts = array_map(function ($pt) {
            return [
                'lat' => $pt->lat,
                'lng' => $pt->long,
                'radius' => $pt->radius,
                'weight' => $pt->weight,
            ];
        }, CongestionPt::findAll(['status' => 'enabled']));

        $requestDate = new DateTime();

        $request = new Request($apiUrl);
        $request->setMethod('POST');
        $request->setJsonBody([
            "locations" => [
                $from, $to
            ],
            "options" => [
                'unit' => $this->unit,
                'routeType' => $this->routeType,
                'enhancedNarrative' => true,
                'locale' => 'en_US',
                'mustAvoidLinkIds' => [],
                'tryAvoidLinkIds' => [],
                'sideOfStreetDisplay' => true,
                'destinationManeuverDisplay' => false,
                'drivingStyle' => 'normal',
                'highwayEfficiency' => 10.0,
                'timeType' => 2,
                'dateType' => 0,
                'date' => $requestDate->format('m/d/Y'),
                'localTime' => $requestDate->format('H:i'),
                'routeControlPointCollection' => $controlPts
            ]
        ]);

//        Yii::info(print_r($request, true));

        $response = $http->exec($request);

        if (!$response->hasError()) {
//            return "<pre>" . print_r(json_decode($response->body, true), true) . "</pre>";
            return $response->body;
        } else {
            return "<pre>" . print_r($response, true) . "</pre>";
        }
    }
}