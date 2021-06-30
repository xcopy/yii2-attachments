<?php

namespace xcopy\attachments\models;

use yii\base\Model;
use yii\web\UploadedFile;

/**
 * Class AttachmentUploadForm
 *
 * @package xcopy\attachments\models
 * @author Kairat Jenishev <kairat.jenishev@gmail.com>
 */
class AttachmentUploadForm extends Model
{
    /** @var UploadedFile[] */
    public $uploadedFiles;

    /** @var string */
    public $redirectUrl;

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [
                'uploadedFiles',
                'file',
                'skipOnEmpty' => false,
                'minFiles' => 1,
                'maxFiles' => 10,
                'minSize' => 1,
                'maxSize' => 1024 * 1024 * 10,
            ],
            ['redirectUrl', 'string']
        ];
    }
}
