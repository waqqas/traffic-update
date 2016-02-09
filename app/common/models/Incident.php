<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "incident".
 *
 * @property integer $id
 * @property double $lat
 * @property double $lng
 * @property string $location
 * @property integer $type
 * @property string $description
 * @property integer $severity
 * @property integer $eventCode
 * @property integer $startTime
 * @property integer $endTime
 * @property integer $delayFromTypical
 * @property integer $delayFromFreeFlow
 * @property boolean $enabled
 * @property integer $created_at
 * @property integer $updated_at
 */
class Incident extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'incident';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lat', 'lng', 'created_at', 'updated_at'], 'required'],
            [['lat', 'lng'], 'number'],
            [['type', 'severity', 'eventCode', 'startTime', 'endTime', 'delayFromTypical', 'delayFromFreeFlow', 'created_at', 'updated_at'], 'integer'],
            [['enabled'], 'boolean'],
            [['location'], 'string', 'max' => 200],
            [['description'], 'string', 'max' => 500]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'lat' => 'Lat',
            'lng' => 'Lng',
            'location' => 'Location',
            'type' => 'Type',
            'description' => 'Description',
            'severity' => 'Severity',
            'eventCode' => 'Event Code',
            'startTime' => 'Start Time',
            'endTime' => 'End Time',
            'delayFromTypical' => 'Delay From Typical',
            'delayFromFreeFlow' => 'Delay From Free Flow',
            'enabled' => 'Enabled',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
