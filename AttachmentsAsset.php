<?php

namespace xcopy\attachments;

use yii\web\AssetBundle;

/**
 * Class AttachmentsAsset
 *
 * @package xcopy\attachments
 * @author Kairat Jenishev <kairat.jenishev@gmail.com>
 */
class AttachmentsAsset extends AssetBundle
{
    /**
     * {@inheritDoc}
     */
    public $sourcePath = __DIR__.'/assets';

    /**
     * {@inheritDoc}
     */
    public $js = ['attachments.js'];

    /**
     * {@inheritDoc}
     */
    public $css = ['attachments.css'];

    /**
     * {@inheritDoc}
     */
    public $depends = [
        'yii\web\JqueryAsset',
        'yii\web\YiiAsset',
    ];
}
