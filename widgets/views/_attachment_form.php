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
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var Attachment $model
 * @var array $options
 * @var string|null $header
 * @var array $headerOptions
 * @var string|null $footer
 * @var array $footerOptions
 * @var array|string $action
 * @var array $types
 */

$uploadForm = new AttachmentUploadForm;

?>

<div class="card">
    <?= $header ? Html::tag('div', $header, $headerOptions) : '' ?>
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
                'pager' => [
                    'class' => 'yii\bootstrap4\LinkPager',
                    'options' => ['class' => 'd-flex justify-content-center'],
                    'listOptions' => ['class' => 'pagination mt-3 mb-0']
                ],
                'columns' => [
                    [
                        'attribute' => 'original_name',
                        'format' => 'raw',
                        'contentOptions' => ['class' => 'w-100'],
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
    <div class="<?= $dataProvider ? 'card-footer' : 'card-body' ?>">
        <?php $form = ActiveForm::begin([
            'id' => 'js-attachment-form',
            'options' => array_merge(
                $options,
                ['class' => 'w-100']
            ),
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
    <?= $footer ? Html::tag('div', $footer, $footerOptions) : '' ?>
</div>
