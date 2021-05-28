<?php

namespace xcopy\attachments\controllers;

use Yii;
use yii\db\StaleObjectException;
use yii\helpers\Html;
use yii\filters\{AjaxFilter, ContentNegotiator, VerbFilter};
use yii\web\{Controller, NotFoundHttpException, Response, UploadedFile};

use xcopy\attachments\models\{
    Attachment,
    AttachmentUploadForm
};

/**
 * Class AttachmentsController
 *
 * @package xcopy\attachments\controllers
 * @author Kairat Jenishev <kairat.jenishev@gmail.com>
 */
class AttachmentsController extends Controller
{
    /**
     * {@inheritDoc}
     */
    public function behaviors(): array
    {
        return [
            [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON
                ],
                'except' => ['download']
            ],
            [
                'class' => AjaxFilter::class,
                'except' => ['download']
            ],
            [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['POST'],
                    'delete' => ['POST', 'DELETE']
                ]
            ]
        ];
    }

    /**
     * @return mixed
     */
    public function actionCreate(): array
    {
        $response = [
            'models' => [],
            'errors' => [],
        ];

        $uploadForm = new AttachmentUploadForm;
        $uploadForm->uploadedFiles = UploadedFile::getInstances($uploadForm, 'uploadedFiles');

        if ($uploadForm->validate()) {
            foreach ($uploadForm->uploadedFiles as $uploadedFile) {
                $model = new Attachment;
                $model->uploadedFile = $uploadedFile;

                if ($model->load(Yii::$app->request->post()) && $model->upload()) {
                    $response['models'][] = $model->attributes;
                } else {
                    $response['errors'][] = Html::errorSummary($model, [
                        'header' => Yii::t('app', 'File "{filename}" not uploaded:', [
                            'filename' => $model->original_name,
                        ]),
                        'class' => 'invalid-feedback'
                    ]);
                }
            }
        } else {
            $response['errors'] = Html::errorSummary($uploadForm, [
                'header' => false,
                'class' => 'invalid-feedback'
            ]);
        }

        return $response;
    }

    /**
     * @param int $id
     * @throws NotFoundHttpException
     */
    public function actionDownload(int $id)
    {
        if (!$model = Attachment::findOne($id)) {
            throw new NotFoundHttpException('Requested attachment not found.');
        }

        $model->download();
    }

    /**
     * @param int $id
     * @return array
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function actionDelete(int $id): array
    {
        if (!$model = Attachment::findOne($id)) {
            throw new NotFoundHttpException('Requested attachment not found.');
        }

        return [
            'success' => $model->delete()
        ];
    }
}
