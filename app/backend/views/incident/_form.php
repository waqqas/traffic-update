<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\Incident */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="incident-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'lat')->textInput() ?>

    <?= $form->field($model, 'lng')->textInput() ?>

    <?= $form->field($model, 'location')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'type')->textInput() ?>

    <?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'severity')->textInput() ?>

    <?= $form->field($model, 'eventCode')->textInput() ?>

    <?= $form->field($model, 'startTime')->textInput() ?>

    <?= $form->field($model, 'endTime')->textInput() ?>

    <?= $form->field($model, 'delayFromTypical')->textInput() ?>

    <?= $form->field($model, 'delayFromFreeFlow')->textInput() ?>

    <?= $form->field($model, 'enabled')->checkbox() ?>

    <?= $form->field($model, 'created_at')->textInput() ?>

    <?= $form->field($model, 'updated_at')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
