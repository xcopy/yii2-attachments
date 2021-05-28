<?php

namespace xcopy\attachments\models;

use Yii;
use yii\db\ArrayExpression;
use yii\helpers\FileHelper;

/**
 * Class AttachmentType
 *
 * @package xcopy\attachments\models
 * @author Kairat Jenishev <kairat.jenishev@gmail.com>
 * 
 * @property int $id
 * @property string $name
 * @property string $object_name
 * @property ArrayExpression|array|null $file_extensions
 * @property bool $active
 * @property int $created_by
 * @property string $created_time
 * @property int|null $modified_by
 * @property string|null $modified_time
 *
 * @property Attachment[] $attachments
 * @property string[] $fileExtensions
 * @property string[] $mimeTypes
 * @property string $fileExtensionNames
 */
class AttachmentType extends ActiveRecord
{
    /** @var array */
    public $file_extensions_array;

    /**
     * {@inheritDoc}
     */
    public function afterFind()
    {
        parent::afterFind();

        $this->file_extensions_array = $this->file_extensions->getValue();
    }

    /**
     * {@inheritDoc}
     */
    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $this->file_extensions = new ArrayExpression($this->file_extensions_array, 'string');

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['name', 'object_name'], 'required'],
            [['file_extensions'], 'safe'],
            [['file_extensions_array'], 'required'],
            [['active'], 'boolean'],
            [['created_by', 'modified_by'], 'default', 'value' => null],
            [['created_by', 'modified_by'], 'integer'],
            [['created_time', 'modified_time'], 'safe'],
            [['name', 'object_name'], 'string', 'max' => 100],
            [['name', 'object_name'], 'trim'],
            [['name', 'object_name'], 'unique', 'targetAttribute' => ['name', 'object_name'], 'message' => Yii::t('app', 'This combination of {attributes} has already been taken.')],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'object_name' => Yii::t('app', 'Object Name'),
            'file_extensions' => Yii::t('app', 'File Extensions'),
            'file_extensions_array' => Yii::t('app', 'File Extensions'),
            'active' => Yii::t('app', 'Active'),
            'created_by' => Yii::t('app', 'Created By'),
            'created_time' => Yii::t('app', 'Created Time'),
            'modified_by' => Yii::t('app', 'Modified By'),
            'modified_time' => Yii::t('app', 'Modified Time'),
        ];
    }

    /**
     * Returns human-readable name of the class
     *
     * @param bool $pluralize
     * @return string
     */
    public static function title(bool $pluralize = false): string
    {
        return 'Attachment Type' . ($pluralize ? 's' : '');
    }

    /**
     * Returns file extensions as comma separated list
     *
     * @return string
     * @see $fileExtensionNames
     */
    public function getFileExtensionNames(): string
    {
        $extensions = array_map('strtoupper', $this->file_extensions->getValue());

        sort($extensions);

        return join(', ', $extensions);
    }

    /**
     * Returns array of file extensions
     * (this one is "sugar" method, just for the method names to be the same)
     *
     * @return string[]
     * @see $fileExtensions
     * @see Attachment::getFileExtensions()
     */
    public function getFileExtensions(): array
    {
        return $this->file_extensions->getValue();
    }

    /**
     * Returns the MIME-types corresponding to the extensions
     *
     * @return array
     * @see $mimeTypes
     */
    public function getMimeTypes(): array
    {
        $mimeTypesMap = require Yii::getAlias(FileHelper::$mimeMagicFile);

        $mimeTypes = array_map(
            fn (string $extension) => $mimeTypesMap[$extension],
            $this->file_extensions->getValue()
        );

        return array_filter($mimeTypes);
    }
}
