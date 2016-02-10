<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel common\models\IncidentSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Incidents';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="incident-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Create Incident', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'lat',
            'lng',
            'location',
            'type',
            // 'description',
            // 'severity',
            // 'eventCode',
            // 'startTime:datetime',
            // 'endTime:datetime',
            // 'delayFromTypical',
            // 'delayFromFreeFlow',
            // 'enabled:boolean',
            // 'created_at',
            // 'updated_at',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>

</div>
