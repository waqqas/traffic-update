<?php

namespace console\controllers;


use common\models\Language;
use common\models\LanguageSource;
use common\models\LanguageTranslate;
use Yii;
use yii\console\Controller;

class LanguageController extends Controller
{
    public function actionTranslate(array $language = [], $category = '')
    {

        $langs = Language::findAll([ 'status' => 1]);

        foreach($langs as $lang){
            // check if we need to translate it
            if( in_array($lang->language_id, $language) && (Yii::$app->language != $lang->language_id) ){
                Yii::info('Translating: ' . $lang->language_id);

                $alreadyTranslated = LanguageTranslate::find()->select(['id'])->where([
                    'language' => $lang->language_id,
                ])->asArray()->all();

                $alreadyTranslated = array_map(function($item){
                    return $item['id'];
                }, $alreadyTranslated);

                // find all messages to be translated
                $query = LanguageSource::find()->where([
                    'not in', 'id', $alreadyTranslated
                ]);

                if( !empty($category) ){
                    $query  = $query->andWhere([
                       'category' => $category,
                    ]);
                }

                $sourceMsgs = $query->all();

                $source = substr(Yii::$app->sourceLanguage, 0, strpos(Yii::$app->sourceLanguage, '-'));

                foreach($sourceMsgs as $msg){
                    try {
                        $translatedMsg = Yii::$app->translate->translate($source, $lang->language, $msg->message);

//                        Yii::info(print_r($translatedMsg, true));

                        /** @var \common\models\LanguageTranslate $tr */
                        $tr = new LanguageTranslate();

                        $tr->id = $msg->id;
                        $tr->language = $lang->language_id;
                        $tr->translation = html_entity_decode($translatedMsg['data']['translations'][0]['translatedText']);

//                        Yii::info(print_r($tr->attributes, true));

                        $tr->save();
                    }
                    catch(\Exception $e){

                    }

                }



            }

        }

        return Controller::EXIT_CODE_NORMAL;
    }

}