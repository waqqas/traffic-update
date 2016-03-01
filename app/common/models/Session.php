<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "session".
 *
 * @property integer $id
 * @property string $phone_num
 * @property string $data
 * @property integer $created_at
 * @property integer $updated_at
 */
class Session extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%session}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['phone_num', 'data'], 'required'],
            [['phone_num'], 'string', 'max' => 32],
            [['data'], 'string', 'max' => 16364]
        ];
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'phone_num' => 'Phone Num',
            'data' => 'Data',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function beforeValidate()
    {
        if( is_array($this->data))
            $this->data = base64_encode(serialize($this->data));

        return parent::beforeValidate();
    }

    public function beforeSave($insert)
    {
        if( is_array($this->data))
            $this->data = base64_encode(serialize($this->data));

        return parent::beforeSave($insert);
    }

    public function afterFind()
    {
        parent::afterFind();
        $this->data = unserialize(base64_decode($this->data));
    }
}
