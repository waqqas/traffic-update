<?php

namespace common\components\sms;

use Yii;

class Session extends \yii\web\Session
{
    public $expirySeconds;

    /** @var  \common\models\Session */
    private $session;

    public function open()
    {

        if( $this->getIsActive() ){
            return;
        }

        if (!isset(Yii::$app->smsUser)) {
            return;
        }

        // check for existing session
        $query = \common\models\Session::find()->where([
            'phone_num' => Yii::$app->smsUser->phoneNumber,
        ]);
        $query->andWhere([
            'and', ['>=', 'updated_at', time() - $this->expirySeconds],
        ]);

        $this->session = $query->one();

        if( !$this->session ){
            $this->session = new \common\models\Session();

            $this->session->phone_num = Yii::$app->smsUser->phoneNumber;
            $this->session->data = [];
            $this->session->updated_at = time();

            $this->session->save();
        }
        else{
            $this->session->touch('updated_at');
            $this->session->save();
        }

    }

    public function close()
    {
        if( $this->getIsActive() ){
            $this->session->save();
            $this->session = null;
        }
    }

    public function destroy()
    {
        if( $this->getIsActive() ){
            $this->session->delete();
            $this->close();
        }
    }

    public function getIsActive()
    {
        return isset($this->session);
    }


    // ArrayAccess
    public function offsetExists($offset)
    {
        if( $this->getIsActive()){
            $data = array_values($this->session->data);
            return isset($data[$offset]);
        }
        return false;
    }

    public function offsetGet($offset)
    {
        if( $this->getIsActive()){
            $data = array_values($this->session->data);
            return $data[$offset];
        }
        return null;
    }

    public function offsetSet($offset, $item)
    {
        if( $this->getIsActive()){
            array_push($this->session->data, $item);
        }
    }

    public function offsetUnset($offset)
    {
        if( $this->getIsActive()){
            unset($this->session->data[$offset]);
        }
    }

    // Countable

    public function count()
    {
        if( $this->getIsActive()){
            return count($this->session->data);
        }
        return 0;
    }


    // IteratorAggregate

    public function getIterator()
    {
        return new \ArrayIterator($this->session->data);
    }

    public function get($key, $defaultValue = null)
    {
        if( $this->getIsActive() && isset($this->session->data[$key])){
            return $this->session->data[$key];

        }
        return $defaultValue;
    }

    public function set($key, $value)
    {

        if( $this->getIsActive())
        {
            $this->session->data[$key] = $value;
        }
    }

}