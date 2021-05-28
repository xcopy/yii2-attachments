<?php

namespace xcopy\attachments\models;

use Yii;
use yii\base\Model;
use yii\helpers\{ArrayHelper, FileHelper, StringHelper};
use yii\db\ActiveQuery;
use yii\validators\FileValidator;
use yii\web\{NotFoundHttpException, UploadedFile};

/**
 * Class Attachment
 *
 * @package xcopy\attachments\models
 * @author Kairat Jenishev <kairat.jenishev@gmail.com>
 * 
 * @property int $id
 * @property int|null $type_id
 * @property string $object_name
 * @property int $object_id
 * @property string $path
 * @property string $name
 * @property string $original_name
 * @property string $document_type
 * @property string $mime_type
 * @property int $file_size
 * @property string|null $json_data
 * @property bool $active
 * @property int $created_by
 * @property string $created_time
 * @property int|null $modified_by
 * @property string|null $modified_time
 *
 * @property AttachmentType $type
 * @property Model $object
 * @property array $fileExtensions
 * @property array $mimeTypes
 * @property string $fullName
 */
class Attachment extends ActiveRecord
{
    /** @var UploadedFile */
    public $uploadedFile;

    /** @var Model */
    private $_object;

    /** @var array */
    private $_fileExtensions = [];

    /** @var array */
    private $_mimeTypes = [];

    /**
     * {@inheritDoc}
     */
    public function afterDelete()
    {
        parent::afterDelete();

        FileHelper::unlink($this->fullName);
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        $rules = [
            [['type_id', 'object_id', 'file_size', 'created_by', 'modified_by'], 'default', 'value' => null],
            [['type_id', 'object_id', 'file_size', 'created_by', 'modified_by'], 'integer'],
            [['object_name', 'object_id', 'path', 'name', 'original_name', 'document_type', 'mime_type', 'file_size'], 'required'],
            [['json_data', 'created_time', 'modified_time'], 'safe'],
            [['active'], 'boolean'],
            [['object_name'], 'string', 'max' => 100],
            [['object_name'], 'trim'],
            [['path', 'name', 'original_name', 'document_type', 'mime_type'], 'string', 'max' => 200],
            [['path', 'name', 'original_name', 'document_type', 'mime_type'], 'trim'],
            [['type_id'], 'exist', 'skipOnError' => true, 'targetClass' => AttachmentType::class, 'targetAttribute' => ['type_id' => 'id']],

            ['uploadedFile', 'required'],
            [
                'uploadedFile',
                'unique',
                'targetAttribute' => ['type_id', 'object_name', 'object_id'],
                'message' => Yii::t('app', 'This combination of {attributes} has already been taken.'),
                'when' => fn () => !empty($this->type_id)
            ],
            [
                'uploadedFile',
                function (string $attribute) {
                    if ($this->type_id) {
                        $this->fileExtensions = $this->type->fileExtensions;
                        $this->mimeTypes = $this->type->mimeTypes;
                    }

                    $validator = new FileValidator([
                        'skipOnEmpty' => false,
                        'extensions' => $this->fileExtensions,
                        'mimeTypes' => $this->mimeTypes,
                        'minFiles' => 1,
                        'maxFiles' => 1,
                    ]);

                    $validator->validateAttribute($this, $attribute);
                }
            ]
        ];

        if ($allowed_models = ArrayHelper::getValue(Yii::$app->params, 'attachments.allowed_models', [])) {
            $rules[] = ['object_name', 'in', 'range' => $allowed_models];
        }

        return $rules;
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'type_id' => Yii::t('app', 'Type'),
            'object_name' => Yii::t('app', 'Object Name'),
            'object_id' => Yii::t('app', 'Object ID'),
            'path' => Yii::t('app', 'Path'),
            'name' => Yii::t('app', 'Name'),
            'original_name' => Yii::t('app', 'Original Name'),
            'document_type' => Yii::t('app', 'Document Type'),
            'mime_type' => Yii::t('app', 'MIME Type'),
            'file_size' => Yii::t('app', 'File Size'),
            'json_data' => Yii::t('app', 'JSON Data'),
            'active' => Yii::t('app', 'Active'),
            'created_by' => Yii::t('app', 'Created By'),
            'created_time' => Yii::t('app', 'Created Time'),
            'modified_by' => Yii::t('app', 'Modified By'),
            'modified_time' => Yii::t('app', 'Modified Time'),
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getType(): ActiveQuery
    {
        return $this->hasOne(AttachmentType::class, ['id' => 'type_id']);
    }

    /**
     * Returns array of allowed file extensions by default (if type isn't selected)
     *
     * @return array
     * @see $fileExtensions
     * @see AttachmentType::getFileExtensions()
     */
    public function getFileExtensions(): array
    {
        if (empty($this->_fileExtensions)) {
            $this->_fileExtensions = ArrayHelper::getValue(Yii::$app->params, 'attachments.allowed_file_extensions', []);
        }

        return $this->_fileExtensions;
    }

    /**
     * Sets specific file extensions
     *
     * @param array $fileExtensions
     * @return static
     * @see $fileExtensions
     */
    public function setFileExtensions(array $fileExtensions): Attachment
    {
        $this->_fileExtensions = $fileExtensions;

        return $this;
    }

    /**
     * Returns array of allowed MIME-types by default (if type isn't selected)
     *
     * @return array
     * @see $mimeTypes
     */
    public function getMimeTypes(): array
    {
        if (empty($this->_mimeTypes)) {
            $mimeTypesMap = require Yii::getAlias(FileHelper::$mimeMagicFile);

            $this->_mimeTypes = [];

            foreach ($this->fileExtensions as $extension) {
                if (isset($mimeTypesMap[$extension])) {
                    $this->_mimeTypes[] = $mimeTypesMap[$extension];
                }
            }
        }

        return $this->_mimeTypes;
    }

    /**
     * Sets specific MIME-types
     *
     * @param array $mimeTypes
     * @return static
     * @see $mimeTypes
     */
    public function setMimeTypes(array $mimeTypes): Attachment
    {
        $this->_mimeTypes = $mimeTypes;

        return $this;
    }

    /**
     * @return bool
     */
    public function upload(): bool
    {
        $this->setFileInfo();

        if ($this->object && $this->validate()) {
            return FileHelper::createDirectory($this->path) &&
                $this->uploadedFile->saveAs($this->fullName, false) &&
                $this->save(false);
        }

        return false;
    }

    /**
     * @return void
     */
    public function setFileInfo(): void
    {
        $extension = $this->uploadedFile->extension;

        $this->path = $this->generateFilePath();
        $this->name = $this->generateFileName() . '.' . $extension;
        $this->original_name = $this->uploadedFile->baseName . '.' . $extension;
        $this->document_type = strtolower($extension);
        $this->mime_type = strtolower($this->uploadedFile->type);
        $this->file_size = $this->uploadedFile->size;
    }

    /**
     * @return string|null
     * @throws \Exception
     */
    public function generateFilePath(): ?string
    {
        if (!$this->object) {
            return false;
        }

        $client_id = null;
        $company_id = null;

        if ($this->object->hasAttribute('client_id') && $this->object->client_id) {
            $client_id = $this->object->client_id;
        } else if (
            $this->object->hasAttribute('company_id') &&
            !is_array($this->object->company_id) &&
            $this->object->company_id &&
            strpos($this->object->company_id, '{') === false
        ) {
            $client_id = $this->object->company->client_id;
            $company_id = $this->object->company_id;
        }

        if (class_exists('app\models\Member') &&
            !$company_id &&
            $this->object->hasAttribute('member_id')
            && $this->object->member_id) {
            $company_id = $this->object->member->company_id;
        }

        if (!$client_id) {
            if (
                class_exists('app\models\Company') &&
                $company_id &&
                !is_array($company_id) &&
                strpos($company_id, '{') === false
            ) {
                if ($company = call_user_func(['app\models\Company', 'findOne'], $company_id)) {
                    $client_id = $company->client_id;
                }
            }
        }

        $pieces = array_filter([
            ArrayHelper::getValue(Yii::$app->params, 'attachments.baseDir'),
            $client_id,
            date('Y'),
            $company_id,
            date('m'),
            date('d'),
            StringHelper::basename($this->object_name),
            $this->object_id,
        ]);

        return join('/', $pieces);
    }

    /**
     * @return string
     */
    public function generateFileName(): string
    {
        return uniqid(rand(), true);
    }

    /**
     * @return Model|null
     * @see $object
     */
    public function getObject(): ?Model
    {
        if (!$this->_object) {
            $this->_object = call_user_func([$this->object_name, 'findOne'], $this->object_id);
        }

        return $this->_object;
    }

    /**
     * @param Model $object
     * @see $object
     */
    public function setObject(Model $object)
    {
        $this->object_name = get_class($object);
        $this->object_id = $object->primaryKey;

        $this->_object = $object;
    }

    /**
     * @param bool $isApiRequest
     * @throws NotFoundHttpException
     * @return void
     */
    public function download(bool $isApiRequest = false): void
    {
        $filename = $this->fullName;

        if (!file_exists($filename)) {
            throw new NotFoundHttpException(
                Yii::t('app', 'The requested file "{filename}" does not exist.', [
                    'filename' => $filename
                ])
            );
        }

        if ($this->isImage() && !$isApiRequest) {
            $fp = fopen($filename, 'rb');
            header('Content-Type: image/' . $this->document_type);
            header('Content-Length: ' . filesize($filename));
            fpassthru($fp);
            exit;
        } elseif ($this->isPDF() && !$isApiRequest) {
            $fp = fopen($filename, 'rb');
            header('Content-Type: application/pdf');
            header('Cache-Control: public, must-revalidate');
            header('Pragma: public');
            header('Expires: 0');
            header('Content-Length: ' . filesize($filename));
            header('Content-Disposition: inline; filename="' . $this->original_name . '";');
            ob_clean();
            flush();
            fpassthru($fp);
            exit;
        } else {
            Yii::$app->response->sendFile($filename, $this->original_name);
        }
    }

    /**
     * Returns full name of the attachment (path + name)
     *
     * @return string
     * @see $fullName
     */
    public function getFullName(): string
    {
        return $this->path . '/' . $this->name;
    }

    /**
     * @return bool
     */
    public function isPDF(): bool
    {
        return strcasecmp('pdf', $this->document_type) === 0 &&
            $this->mime_type === 'application/pdf';
    }

    /**
     * @return bool
     */
    public function isImage(): bool
    {
        return preg_match('/^image\/.+$/', $this->mime_type) > 0;
    }
}
