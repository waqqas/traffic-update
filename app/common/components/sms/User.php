<?php

namespace common\components\sms;

use common\models\UserPreference;
use Yii;

class User extends \yii\web\User{


    public function getPhoneNumber(){
        if( !$this->isGuest){
            return $this->getIdentity()->username;
        }
        return null;
    }

    public function setEvents($events){
        if( !$this->isGuest){
            $userPreference = UserPreference::findOne(['user_id' => $this->getId(), 'name' => 'events']);
            if( $userPreference == null ){
                $userPreference = new UserPreference();
                $userPreference->encoding = 'base64_serialized';
                $userPreference->name = 'events';
                $userPreference->user_id = $this->getId();
            }

            if( $events == null){
                $userPreference->delete();
            }
            else{
                $userPreference->value = $events;
                $userPreference->save();
            }
        }
    }

    public function setLanguage($langId){
        if( !$this->isGuest){
            $userPreference = UserPreference::findOne(['user_id' => $this->getId(), 'name' => 'language']);
            if( $userPreference == null ){
                $userPreference = new UserPreference();
                $userPreference->encoding = 'none';
                $userPreference->name = 'language';
                $userPreference->user_id = $this->getId();
            }

            $userPreference->value = $langId;
            $userPreference->save();
        }
    }

    public function setLocation($location){
        if( !$this->isGuest){
            $userPreference = UserPreference::findOne(['user_id' => $this->getId(), 'name' => 'location']);
            if( $userPreference == null ){
                $userPreference = new UserPreference();
                $userPreference->encoding = 'none';
                $userPreference->name = 'location';
                $userPreference->user_id = $this->getId();
            }

            $userPreference->value = $location;
            $userPreference->save();
        }
    }

}