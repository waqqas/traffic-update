<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\IncidentSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="incident-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'lat') ?>

    <?= $form->field($model, 'lng') ?>

    <?= $form->field($model, 'location') ?>

    <?= $form->field($model, 'type') ?>

    <?php // echo $form->field($model, 'description') ?>

    <?php // echo $form->field($model, 'severity') ?>

    <?php // echo $form->field($model, 'eventCode') ?>

    <?php // echo $form->field($model, 'startTime') ?>

    <?php // echo $form->field($model, 'endTime') ?>

    <?php // echo $form->field($model, 'delayFromTypical') ?>

    <?php // echo $form->field($model, 'delayFromFreeFlow') ?>

    <?php // echo $form->field($model, 'enabled')->checkbox() ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
