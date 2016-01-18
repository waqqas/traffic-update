<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "smsmt".
 *
 * @property integer $id
 * @property string $recipient
 * @property string $text
 * @property string $status
 * @property integer $created_at
 * @property integer $updated_at
 */
class Smsmt extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'smsmt';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['recipient', 'text'], 'required'],
            [['text'], 'string'],
//            [['created_at', 'updated_at'], 'integer'],
            [['recipient'], 'string', 'max' => 32],
            [['status'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'recipient' => 'Recipient',
            'text' => 'Text',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }
}
