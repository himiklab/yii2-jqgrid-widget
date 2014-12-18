<?php
/**
 * @link https://github.com/himiklab/yii2-jqgrid-widget
 * @copyright Copyright (c) 2014 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\jqgrid;

use Yii;
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

    public function init()
    {
        parent::init();
        $jsLangSuffix = $this->getLanguageSuffix();

        $this->js = [
            'js/ui.multiselect.js',
            "js/locale/ui-multiselect-{$jsLangSuffix}.js"
        ];
    }

    protected function getLanguageSuffix()
    {
        $currentAppLanguage = Yii::$app->language;
        $langsExceptions = ['pt-br'];

        if (strpos($currentAppLanguage, '-') === false) {
            return $currentAppLanguage;
        }

        if (in_array($currentAppLanguage, $langsExceptions)) {
            return $currentAppLanguage;
        } else {
            return substr($currentAppLanguage, 0, strpos($currentAppLanguage, '-'));
        }
    }
}
