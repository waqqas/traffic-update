<?php

namespace common\components;

use yii\base\Exception;
use yii\i18n\MissingTranslationEvent;
use Yii;

class TranslationEventHandler
{
    public static function handleMissingTranslation(MissingTranslationEvent $event)
    {
        $source = substr(Yii::$app->sourceLanguage, 0, strpos(Yii::$app->sourceLanguage, '-'));
        $target = substr(Yii::$app->language, 0, strpos(Yii::$app->language, '-'));

        try {
            $translatedMsg = Yii::$app->translate->translate($source, $target, $event->message);
            $event->translatedMessage = html_entity_decode($translatedMsg['data']['translations'][0]['translatedText']);
        }
        catch(Exception $e){
            $event->translatedMessage = "@MISSING: {$event->category}.{$event->message} FOR LANGUAGE {$event->language} @";
        }
    }
}