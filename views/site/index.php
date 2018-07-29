<?php

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */

$this->title = 'My Yii Application';
?>
    <div class="site-index">

        <div class="row">
            <div class="col-lg-4">
                <div class="well">
                    <?php $form = ActiveForm::begin([
                        'action' => ['/site/ajax-get-country'],
                        'method' => 'post',
                        'options' => [
                            'id' => 'ajax-country',
                        ],
                    ]); ?>

                    <?= $form->field($model, 'ip')->widget(yii\widgets\MaskedInput::className(), [
                        'name' => 'ip',
                        'clientOptions' => [
                            'alias' => 'ip'
                        ],
                        'options' => [
                            'class' => 'form-control',
                        ],

                    ])->label('Ip Address'); ?>


                    <div class="form-group">
                        <?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
            <div class="col-lg-8">
                <table class="table table-sm">
                    <thead>
                    <tr>
                        <th>
                            <h3>Country:</h3>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td id="result"></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <hr>
        <div class="jumbotron">
            <?php if (!Yii::$app->user->isGuest): ?>
                <?php if (Yii::$app->user->identity->isPremium()): ?>
                    <h3>You have premium account</h3>
                    <p>Expire days: <?= Yii::$app->user->identity->getSubscribeDays() ?></p>
                <?php else: ?>
                    <p>
                        <a class="btn btn-lg btn-success"
                           onclick="window.location.href='<?= Url::to(['site/get-premium']) ?>';">
                            Get 5 Premium days
                        </a>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

<?php $this->registerJs("
        var form = $('#ajax-country');
        $(document).on('beforeSubmit', '#ajax-country', function (e) {
            $.post(form.attr('action'), form.serialize(), function (response) {
                if (response.success) {
                    var result = $('#result').html(response.data);
                    if (response.limit) {
                         result.parent().removeClass('success').addClass('danger');
                    } else {
                         result.parent().removeClass('danger').addClass('success'); 
                    }         
                }
            });
        return false;
        })
"); ?>

<?php $this->registerCss('
#result {
  font: normal 24px/normal "Warnes", Helvetica, sans-serif;
  padding : 15px 10px;
}
'); ?>

