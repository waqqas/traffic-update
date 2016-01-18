<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "smsmo".
 *
 * @property integer $id
 * @property string $msisdn
 * @property string $operator
 * @property string $text
 * @property string $status
 * @property integer $created_at
 * @property integer $updated_at
 */
class Smsmo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'smsmo';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['msisdn', 'operator', 'text'], 'required'],
            [['text'], 'string'],
//            [['created_at', 'updated_at'], 'integer'],
            [['msisdn', 'operator'], 'string', 'max' => 32],
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
            'msisdn' => 'Msisdn',
            'operator' => 'Operator',
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
