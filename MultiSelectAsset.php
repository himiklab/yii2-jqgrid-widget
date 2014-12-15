<?php
/**
 * @link https://github.com/himiklab/yii2-jqgrid-widget
 * @copyright Copyright (c) 2014 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\jqgrid;

use yii\web\AssetBundle;

class MultiSelectAsset extends AssetBundle
{
    public $sourcePath = '@vendor/himiklab/yii2-jqgrid-widget/assets';

    public $depends = [
        'yii\jui\JuiAsset',
    ];

    public $css = [
        'css/ui.multiselect.css'
    ];

    public $js = [
        'js/ui.multiselect.js'
    ];
}
