<?php

namespace common\components;

use Yii;
use yii\base\Component;

use Flow\JSONPath\JSONPath;
use yii\i18n\Formatter;

class MapQuestFormatter extends Formatter
{

    public function asSMS($routeResponse, $prefix = '')
    {
        $routeInfo = json_decode($routeResponse, true);

        $path = new JSONPath($routeInfo);

        $result = array_map(function ($distance, $streets) {
            return [
                'distance' => $distance,
                'streets' => $streets,
            ];
        },
            $path->find('$..legs.*.maneuvers.*[distance]')->data(),
            $path->find('$..legs.*.maneuvers.*[streets]')->data()
        );


//        Yii::info(print_r($result, true));

        $legs = array_reduce($result, function ($legs, $leg) {

            // process only if distance greater than 1 km and we have the name of street
            if ($leg['distance'] > 1 && count($leg['streets']) > 0) {

                // check if that leg already exists (add distance, in that case)
                $index = 0;
                $totalLegs = count($legs);
                for ($index = 0; $index < $totalLegs; $index++) {
                    if ($legs[$index]['streets'][0] == $leg['streets'][0]) {
                        $legs[$index]['distance'] += $leg['distance'];
                        break;
                    }
                }
                if ($index == $totalLegs) {

                    array_push($legs, $leg);
                }
            }
            return $legs;
        }, []);

        $sms = $prefix . implode(" > ", array_map(function ($leg) {
            return $leg['streets'][0] . '('. $this->asDecimal($leg['distance'], 1) . ')';
        }, $legs));
//        $sms .= " [www.roadez.com]";

        return $sms;
    }
}