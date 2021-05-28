<?php

use yii\widgets\Pjax;
use yii\helpers\Html;
use yii\grid\{ActionColumn, GridView};
use yii\widgets\ActiveForm;

use xcopy\attachments\models\{
    Attachment,
    AttachmentUploadForm
};

use kartik\select2\Select2;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var Attachment $model
 * @var string|null $color
 * @var array|string $action
 * @var array $types
 */

$uploadForm = new AttachmentUploadForm;

$this->registerCss("
#js-attachment-errors .invalid-feedback {
    display: block !important;
}
#js-attachment-errors .invalid-feedback ul {
    list-style: none;
    margin: 0;
    padding: 0;
}
");

$this->registerJs("
$('#js-attachment-files').on('change', function (e) {
    $('#js-attachment-errors').empty().hide();
});

$('#js-attachment-form').on('submit', function (e) {
    var form = $(this);
    var submit = $('#js-attachment-submit');
    var progress = $('#js-attachment-progress');
    var progressBar = progress.find('.progress-bar');
    var fileInput = $('#js-attachment-files');
    var feedback = $('#js-attachment-errors');
    var delay = 250;
    var hasFiles = fileInput[0].files.length > 0;

    e.preventDefault();
    e.stopImmediatePropagation();

    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        dataType: 'json',
        data: new FormData(this),
        beforeSend: function (e) {
            hasFiles && form.hide();
            hasFiles && progress.show();
            fileInput.removeClass('is-invalid');
            feedback.empty().hide();
        },
        success: function (response) {
            if (response.errors) {
                feedback.show().html(response.errors);
            }

            form[0].reset();
            $.pjax.reload('#js-attachment-list', {async: false});
            $.pjax.reload('#js-attachment-type', {async: false});
        },
        complete: function () {
            form.delay(delay).show(0);
            submit.prop('disabled', false);
            progress.delay(delay).hide(0);

            setTimeout(function () {
                progressBar.css('width', 0);
            }, delay);
        },
        cache: false,
        contentType: false,
        processData: false,
        xhr: function () {
            var xhr = $.ajaxSettings.xhr();

            if (xhr.upload) {
                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        progressBar.css('width', ((e.loaded / e.total) * 100) + '%');
                    }
                }, false);
            }

            return xhr;
        }
    });
});

$(document).on('click', '.js-attachment-delete', function (e) {
    e.preventDefault();

    if (confirm('" . Yii::t('app', 'Are you sure? This action will irreversibly delete the file from the system!') . "')) {
        $.post(e.target.href, function (response) {
            if (response.success) {
                $.pjax.reload('#js-attachment-list', {async: false});
                $.pjax.reload('#js-attachment-type', {async: false});
            }
        });
    }
});
");

?>

<div class="card">
    <?php if ($dataProvider) : ?>
        <div class="card-body">
            <?php Pjax::begin([
                'id' => 'js-attachment-list',
                'enablePushState' => false
            ]) ?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'showOnEmpty' => false,
                'showHeader' => false,
                'layout' => "{items}\n{pager}",
                'options' => ['class' => 'grid-view table-responsive'],
                'tableOptions' => ['class' => 'table table-sm mb-0'],
                'columns' => [
                    [
                        'attribute' => 'original_name',
                        'format' => 'raw',
                        'value' => function (Attachment $model) {
                            $link = Html::a(
                                $model->original_name,
                                ['/attachments/download', 'id' => $model->id],
                                [
                                    'title' => $model->original_name,
                                    'target' => '_blank',
                                    'data-pjax' => 0,
                                ]
                            );

                            $model->type_id and ($link .= ' &ndash; ' . $model->type->name);

                            $info = vsprintf(' (%s, %s)', [
                                strtoupper($model->document_type),
                                Yii::$app->formatter->asShortSize($model->file_size, 0)
                            ]);

                            return $link . Html::tag('small', $info, ['class' => 'text-nowrap text-secondary']);
                        }
                    ],
                    [
                        'class' => ActionColumn::class,
                        'template' => '{delete}',
                        'buttons' => [
                            'delete' => function ($url, Attachment $model) {
                                return Html::a(
                                    '',
                                    ['/attachments/delete', 'id' => $model->id],
                                    [
                                        'class' => 'fa fa-trash-alt js-attachment-delete',
                                        'data-pjax' => 0,
                                    ]
                                );
                            }
                        ]
                    ]
                ]
            ]) ?>
            <?php Pjax::end() ?>
        </div>
    <?php endif ?>
    <div class="<?= $dataProvider ? 'card-footer d-block' : 'card-body' ?>">
        <?php $form = ActiveForm::begin([
            'id' => 'js-attachment-form',
            'action' => $action,
            'enableClientValidation' => false
        ]) ?>

        <?php
            Pjax::begin(['id' => 'js-attachment-type']);
            echo $form->field($model, 'type_id')
                ->widget(Select2::class, [
                    'data' => $types,
                    'options' => [
                        'multiple' => false,
                        'prompt' => Yii::t('app', '-- Attachment Type --')
                    ]
                ])
                ->label(false);
            Pjax::end();
        ?>

        <?php $options = ['options' => ['tag' => false]]; ?>
        <div class="row">
            <div class="col-9">
                <?= $form->field($model, 'object_name', $options)->hiddenInput()->label(false) ?>
                <?= $form->field($model, 'object_id', $options)->hiddenInput()->label(false) ?>
                <?= $form->field($uploadForm, 'uploadedFiles', $options)
                    ->fileInput([
                        'id' => 'js-attachment-files',
                        // unknown property: `AttachmentUploadForm::uploadedFiles[]
                        // that's why "name" attribute has to be set manually
                        'name' => $uploadForm->formName() . '[uploadedFiles][]',
                        'multiple' => true
                    ])
                    ->label(false)
                ?>
                <div id="js-attachment-errors" style="display: none;"></div>
            </div>
            <div class="col-3 text-right">
                <?= Html::submitButton(
                    Yii::t('app', 'Submit'),
                    [
                        'id' => 'js-attachment-submit',
                        'class' => 'btn btn-sm btn-primary'
                    ]
                ) ?>
            </div>
        </div>
        <?php ActiveForm::end() ?>
        <div id="js-attachment-progress" class="progress" style="display: none;">
            <div class="progress-bar" role="progressbar"></div>
        </div>
    </div>
</div>
