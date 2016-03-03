<?php

namespace common\components\sms;

use common\models\UserPreference;
use Yii;

class User extends \yii\web\User
{

    const PREF_SCHEDULE_SMS = 'schedule_sms';
    const PREF_LANGUAGE = 'language';
    const PREF_LOCATION = 'location';


    public function getPhoneNumber()
    {
        if (!$this->isGuest) {
            return $this->getIdentity()->username;
        }
        return null;
    }

    public function setSmsSchedule($events)
    {
        if (!$this->isGuest) {
            $userPreference = UserPreference::findOne(['user_id' => $this->getId(), 'name' => self::PREF_SCHEDULE_SMS]);
            if ($userPreference == null) {
                $userPreference = new UserPreference();
                $userPreference->encoding = UserPreference::ENCODING_BASE64_SERIALIZE;
                $userPreference->name = self::PREF_SCHEDULE_SMS;
                $userPreference->user_id = $this->getId();
            }

            if ($events == null) {
                if (!$userPreference->isNewRecord) {
                    $userPreference->delete();
                }
            } else {
                $userPreference->value = $events;
                $userPreference->save();
            }
        }
    }

    public function setLanguage($langId)
    {
        if (!$this->isGuest) {
            $userPreference = UserPreference::findOne(['user_id' => $this->getId(), 'name' => self::PREF_LANGUAGE]);
            if ($userPreference == null) {
                $userPreference = new UserPreference();
                $userPreference->encoding = UserPreference::ENCODING_NONE;
                $userPreference->name = self::PREF_LANGUAGE;
                $userPreference->user_id = $this->getId();
            }

            if ($langId) {
                if (!$userPreference->isNewRecord) {
                    $userPreference->delete();
                }
            } else {
                $userPreference->value = $langId;
                $userPreference->save();
            }
        }
    }

    public function setLocation($location)
    {
        if (!$this->isGuest) {
            $userPreference = UserPreference::findOne(['user_id' => $this->getId(), 'name' => self::PREF_LOCATION]);
            if ($userPreference == null) {
                $userPreference = new UserPreference();
                $userPreference->encoding = UserPreference::ENCODING_BASE64_SERIALIZE;
                $userPreference->name = self::PREF_LOCATION;
                $userPreference->user_id = $this->getId();
            }

            if ($location == null) {
                if (!$userPreference->isNewRecord) {
                    $userPreference->delete();
                }
            } else {
                $userPreference->value = $location;
                $userPreference->save();
            }
        }
    }

}