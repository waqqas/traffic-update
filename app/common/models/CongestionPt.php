<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "congestion_pt".
 *
 * @property integer $id
 * @property double $lat
 * @property double $long
 * @property double $radius
 * @property double $weight
 * @property string $status
 * @property integer $created_at
 * @property integer $updated_at
 */
class CongestionPt extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'congestion_pt';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lat', 'long', 'radius', 'weight', 'created_at', 'updated_at'], 'required'],
            [['lat', 'long', 'radius', 'weight'], 'number'],
            [['created_at', 'updated_at'], 'integer'],
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
            'lat' => 'Lat',
            'long' => 'Long',
            'radius' => 'Radius',
            'weight' => 'Weight',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
