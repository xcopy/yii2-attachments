<?php

namespace xcopy\attachments\models;

use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord as BaseActiveRecord;

/**
 * Class ActiveRecord
 *
 * @package xcopy\attachments\models
 * @author Kairat Jenishev <kairat.jenishev@gmail.com>
 */
class ActiveRecord extends BaseActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public function behaviors(): array
    {
        $behaviors = [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_time',
                'updatedAtAttribute' => 'modified_time',
                'value' => date('Y-m-d H:i:s')
            ],
            'blameable' => [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'modified_by'
            ]
        ];

        return array_merge($behaviors, parent::behaviors());
    }
}
