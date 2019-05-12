<?php
/**
 * @link https://github.com/himiklab/yii2-jqgrid-widget
 * @copyright Copyright (c) 2014-2019 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\jqgrid;

use yii\web\AssetBundle;

class WidgetAsset extends AssetBundle
{
    public $sourcePath = '@vendor/himiklab/yii2-jqgrid-widget/src/assets';

    public $depends = [
        'yii\jui\JuiAsset',
        'himiklab\jqgrid\JqGridAsset'
    ];

    public $js = [
        'js/widget.js'
    ];
}
