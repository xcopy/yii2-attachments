<?php

namespace xcopy\attachments\widgets\attachmentForm;

use Yii;
use yii\base\Widget;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use xcopy\attachments\models\{
    Attachment,
    AttachmentType
};

/**
 * Class AttachmentForm
 *
 * @package xcopy\attachments\widgets\attachmentForm
 * @author Kairat Jenishev <kairat.jenishev@gmail.com>
 */
class AttachmentForm extends Widget
{
    /** @var Attachment */
    public $model;

    /** @var string|null */
    public $color = null;

    /** @var string|array */
    public $action;

    /** @var array */
    public $types;

    /** @var ActiveDataProvider|null */
    private $dataProvider;

    /** @var bool */
    public $showDataProvider = true;

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        parent::init();

        $this->action ??= Url::toRoute(['/attachments/create']);

        $params = [
            'AND',
            ['active' => true],
            ['object_name' => $this->model->object_name],
            ['object_id' => $this->model->object_id],
        ];

        $query = Attachment::find()->andWhere($params);

        $type_id = (clone $query)
            ->select('type_id')
            ->column();

        $typeParams = [
            'AND',
            ['active' => true],
            ['object_name' => $this->model->object_name]
        ];
        // exclude already attached types
        $typeFilters = ['not in', 'id', $type_id];

        $types = AttachmentType::find()
            ->andWhere($typeParams)
            ->andFilterWhere($typeFilters)
            ->all();

        $this->types = ArrayHelper::map($types, 'id', 'name');

        if ($this->showDataProvider) {
            $this->dataProvider = new ActiveDataProvider([
                'query' => $query->with('type'),
                'pagination' => ['pageSize' => 10]
            ]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function run(): string
    {
        return $this->render('index', [
            'model' => $this->model,
            'color' => $this->color,
            'action' => $this->action,
            'types' => $this->types,
            'dataProvider' => $this->dataProvider,
        ]);
    }
}
