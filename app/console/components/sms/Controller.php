<?php

namespace console\components\sms;

use Yii;
use common\components\SmsSender;
use common\models\User;


class Controller extends \yii\console\Controller
{

    /**
     * @var string JSON encoded string to set $_GET global variable
     */
    public $get = '{}';

    /**
     * @var string JSON encoded string to set $_POST global variable
     */
    public $post = '{}';

    /**
     * @var string JSON encoded string to set $_COOKIE global variable
     */
    public $cookie = '{}';

    /**
     * @var string JSON encoded string to set $_COOKIE global variable
     */
    public $session = '{}';

    public $userTransitions = [];

    public function options($actionID)
    {
        return ['get', 'post', 'cookie', 'session'];
    }

    public static function getCommand($command, $phoneNumber, $params = [])
    {
        // convert string to array
        if (is_scalar($params)) {
            $params = [$params];
        }

        $get = [
            'X-PHONE-NUMBER' => $phoneNumber,
        ];

        $cookie = [
            'PHPSESSID' => md5($phoneNumber),
        ];

        return implode(' ', array_merge(
            [
                'sms/' . $command,
            ],
            $params,
            [
                "--get='" . json_encode($get) . "'",
                "--cookie='" . json_encode($cookie) . "'",
            ]
        ));

    }


    private function createUser($phoneNumber)
    {
        $user = new User();
        $user->username = $phoneNumber;
        $user->email = "$phoneNumber@roardez.com";
        $user->setPassword($phoneNumber);
        $user->generateAuthKey();
        $user->sendToStatus('init');
        if ($user->save()) {
            return $user;
        }
        return null;

    }

    public function beforeAction($action)
    {
        if (!Yii::$app->session->isActive) {


            $_GET = json_decode($this->get, true);
            $_POST = json_decode($this->post, true);
            $_COOKIE = json_decode($this->cookie, true);
            $_SESSION = json_decode($this->session, true);

            ini_set('session.gc_maxlifetime', Yii::$app->params['sessionExpirySeconds']);

            Yii::$app->session->open();

            $phoneNumber = isset($_GET['X-PHONE-NUMBER']) ? $_GET['X-PHONE-NUMBER'] : '';

            if (empty($phoneNumber)) {
                return false;
            }

            $identity = User::findIdentityByPhoneNumber($phoneNumber);

            if (!$identity) {
                $identity = $this->createUser($phoneNumber);
            }
            Yii::$app->user->setIdentity($identity);

            // get user's language preference
            $language = Yii::$app->user->identity->getPreference('language')->one();

            if (!$language) {
                $language = Yii::$app->sourceLanguage;
            } else {
                $language = $language->value;
            }

            Yii::$app->language = $language;

        }

        return parent::beforeAction($action);
    }


    public function afterAction($action, $result)
    {
        // do state transition
        if (in_array($action->id, array_keys($this->userTransitions))) {
            Yii::$app->user->setState($this->userTransitions[$action->id]);
        }


        /** @var \console\components\sms\Response $response */
        $response = Yii::$app->response;

        foreach ($response->session as $key => $value) {
            Yii::$app->session->set($key, $value);
        }

        if ($response->exitStatus == Controller::EXIT_CODE_NORMAL && !empty($response->content)) {
            $sms = $response->getContent();

//            Yii::info("sms: " . $sms);

            SmsSender::queueSend(Yii::$app->user->getPhoneNumber(), $sms);
        }

        Yii::$app->user->identity->save();

        Yii::$app->session->close();
        return parent::afterAction($action, $result);
    }
}