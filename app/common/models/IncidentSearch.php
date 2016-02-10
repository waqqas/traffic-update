<?php

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Incident;

/**
 * IncidentSearch represents the model behind the search form about `common\models\Incident`.
 */
class IncidentSearch extends Incident
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'type', 'severity', 'eventCode', 'startTime', 'endTime', 'delayFromTypical', 'delayFromFreeFlow', 'created_at', 'updated_at'], 'integer'],
            [['lat', 'lng'], 'number'],
            [['location', 'description'], 'safe'],
            [['enabled'], 'boolean'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Incident::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'type' => $this->type,
            'severity' => $this->severity,
            'eventCode' => $this->eventCode,
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'delayFromTypical' => $this->delayFromTypical,
            'delayFromFreeFlow' => $this->delayFromFreeFlow,
            'enabled' => $this->enabled,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'location', $this->location])
            ->andFilterWhere(['like', 'description', $this->description]);

        return $dataProvider;
    }
}
