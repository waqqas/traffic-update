<?php

namespace common\components;

use Yii;
use yii\base\Component;

use Flow\JSONPath\JSONPath;
use yii\i18n\Formatter;

class SmsFormatter extends Formatter
{

    public function asSMS($info, $prefix = '')
    {
        if (is_string($info)) {
            return $this->asRouteSMS($info, $prefix);
        } elseif (is_array($info)) {
            return $this->asIncidentsSMS($info, $prefix);
        }
    }

    public function asIncidentsSMS($incidents, $prefix)
    {
        $sms = $prefix;

        /** @var \common\models\Incident $incident */
        foreach ($incidents as $incident) {
//            Yii::info("incident: ". print_r($incident, true));

            $incLine = $incident->id;
            $incLine .= '- ';

//            if ($incident->severity) {
//                switch ($incident->severity) {
//                    case 1:
//                        $incLine .= "Mild";
//                        break;
//                    case 2:
//                        $incLine .= "Moderate";
//                        break;
//                    case 3:
//                        $incLine .= "High";
//                        break;
//                    case 4:
//                        $incLine .= "Severe";
//                        break;
//                }
//                $incLine .= ' ';
//            }

            switch ($incident->type) {
                case 1: // Construction
                    $incLine .= "Construction";
                    break;
                case 2: // Event
                    $incLine .= "Event";
                    break;
                case 3: // Congestion/Flow
                    $incLine .= "Congestion";
                    break;
                case 4: // Incident/accident
                    $incLine .= "Accident";
                    break;
                default:
                    $incLine .= "Unknown event";

            }

            $incLine .= ' at ';
            $incLine .= ucfirst($incident->location);
            $incLine .= ".";

            if ($incident->delayFromFreeFlow) {
//                $delay = new \DateInterval("P" . $incident->delayFromFreeFlow ."M");
//
//                $format = '';
//
//                if($delay->h != 0)
//                    $format .= '%hh ';
//                if($delay->m != 0);
//                    $format .= '%im ';
//                if($delay->s != 0)
//                    $format .= '%ss ';

                $incLine .= ' Delay: ' . $incident->delayFromFreeFlow . ' m.';

                $incLine = Yii::t('sms', $incLine);
            }


            //
            $sms .= $incLine . PHP_EOL;
        }

        return $sms;
    }

    public function asRouteSMS($info, $prefix = '')
    {

        $routeInfo = json_decode($info, true);

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
                return $leg['streets'][0] . '(' . $this->asDecimal($leg['distance'], 1) . 'km)';
            }, $legs));
//        $sms .= " [www.roadez.com]";

        return $sms;
    }
}