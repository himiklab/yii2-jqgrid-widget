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
        'dist/grid.js-4.7.0.min.css',
        'plugins/ui.multiselect.css'
    ];

    public function init()
    {
        parent::init();

        $jsLangSuffix = $this->getLanguageSuffix();
        if ($jsLangSuffix === 'uk') {
            $jsLangSuffix = 'ua';
        }

        $this->js = [
            'dist/grid.js-4.7.0.min.js',
            'plugins/ui.multiselect.js',
            "dist/i18n/grid.locale-{$jsLangSuffix}.min.js"
        ];
    }

    protected function getLanguageSuffix()
    {
        $currentAppLanguage = Yii::$app->language;
        $langsExceptions = ['pt-BR'];

        if (strpos($currentAppLanguage, '-') === false) {
            return $currentAppLanguage;
        }

        if (in_array($currentAppLanguage, $langsExceptions)) {
            return strtolower($currentAppLanguage);
        } else {
            return substr($currentAppLanguage, 0, strpos($currentAppLanguage, '-'));
        }
    }
}
