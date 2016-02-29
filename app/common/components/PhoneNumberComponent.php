<?php

namespace common\components;

use libphonenumber\NumberParseException;
use Yii;
use yii\base\Component;
use libphonenumber\PhoneNumberUtil;

class PhoneNumberComponent extends Component
{
    /** @var  \libphonenumber\PhoneNumberUtil $phoneUtil */
    private $phoneUtil;

    public function init()
    {
        parent::init();

        $this->phoneUtil = PhoneNumberUtil::getInstance();

    }

    /**
     * @param $number
     * @param $countryCode
     * @return \libphonenumber\PhoneNumber
     */
    public function parse($number, $countryCode)
    {

        try {
            return $this->phoneUtil->parse($number, $countryCode);
        } catch (NumberParseException $e) {
            return false;
        }
    }

    /**
     * @param \libphonenumber\PhoneNumber $phone
     */
    public function getRegionCodeForNumber($phone){
        return $this->phoneUtil->getRegionCodeForNumber($phone);
    }

    public function format($phone, $format){
        return $this->phoneUtil->format($phone, $format);
    }
}