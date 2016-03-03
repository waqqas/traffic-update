<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "user_preference".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $name
 * @property string $encoding
 * @property string $value
 */
class UserPreference extends \yii\db\ActiveRecord
{
    const ENCODING_BASE64_SERIALIZE = 'base64_serialize';
    const ENCODING_SERIALIZE = 'serialize';
    const ENCODING_NONE = 'none';


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_preference}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'name'], 'required'],
            [['user_id'], 'integer'],
            [['value'], 'string'],
            [['name'], 'string', 'max' => 32],
            [['encoding'], 'string', 'max' => 16]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'name' => 'Name',
            'encoding' => 'Encoding',
            'value' => 'Value',
        ];
    }

    public function afterFind()
    {
        parent::afterFind();

        switch($this->encoding){
            case self::ENCODING_BASE64_SERIALIZE:
                $this->value = unserialize(base64_decode($this->value));
                break;
            case self::ENCODING_SERIALIZE:
                $this->value = unserialize($this->value);
                break;
        }

    }

    public function beforeValidate()
    {
        switch($this->encoding){
            case self::ENCODING_BASE64_SERIALIZE:
                $this->value = base64_encode(serialize($this->value));
                break;
            case self::ENCODING_SERIALIZE:
                $this->value = serialize($this->value);
                break;
        }

        return parent::beforeValidate();
    }


    // AR Relations
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
