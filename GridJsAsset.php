<?php
/**
 * @link https://github.com/himiklab/yii2-jqgrid-widget
 * @copyright Copyright (c) 2014 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\jqgrid;

use Yii;
use yii\web\AssetBundle;

class GridJsAsset extends AssetBundle
{
    public $sourcePath = '@bower/grid.js';

    public $css = [
        'plugins/ui.multiselect.css',
        'dist/grid.js-0.1.0.min.css'
    ];

    public function init()
    {
        parent::init();

        $jsLangSuffix = $this->getLanguageSuffix();
        $this->js = [
            'plugins/ui.multiselect.js',
            YII_DEBUG ? 'dist/grid.js-0.1.0.js' : 'dist/grid.js-0.1.0.min.js',
            "dist/i18n/grid.locale-{$jsLangSuffix}.min.js"
        ];
    }

    protected function getLanguageSuffix()
    {
        $currentAppLanguage = Yii::$app->language;
        $langsExceptions = ['pt-BR', 'zh-CN', 'zh-TW'];

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
