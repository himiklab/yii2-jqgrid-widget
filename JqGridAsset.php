<?php
/**
 * @link https://github.com/himiklab/yii2-jqgrid-widget
 * @copyright Copyright (c) 2014 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\jqgrid;

use Yii;
use yii\web\AssetBundle;

class JqGridAsset extends AssetBundle
{
    public $sourcePath = '@bower/jqgrid';

    public $css = [
        'css/ui.jqgrid.css'
    ];

    public $depends = [
        'yii\jui\JuiAsset',
    ];

    public function init()
    {
        parent::init();
        $jsLangSuffix = $this->getLanguageSuffix();
        if ($jsLangSuffix === 'uk') {
            $jsLangSuffix = 'ua';
        }

        $this->js = [
            'js/minified/jquery.jqGrid.min.js',
            "js/i18n/grid.locale-{$jsLangSuffix}.js"
        ];
    }

    protected function getLanguageSuffix()
    {
        $currentAppLanguage = Yii::$app->language;
        $langsExceptions = ['pt_BR', 'sr_LATIN'];

        if (strpos($currentAppLanguage, '_') === false) {
            return $currentAppLanguage;
        }

        if (in_array($currentAppLanguage, $langsExceptions)) {
            return str_replace('_', '-', $currentAppLanguage);
        } else {
            return substr($currentAppLanguage, 0, strpos($currentAppLanguage, '_'));
        }
    }
}
