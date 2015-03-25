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
    public $sourcePath = '@bower/free-jqgrid';

    public $css = [
        'plugins/ui.multiselect.css',
        'css/ui.jqgrid.css'
    ];

    public function init()
    {
        parent::init();

        $this->js = [
            'plugins/ui.multiselect.js',
            YII_DEBUG ? 'js/jquery.jqGrid.src.js' : 'js/jquery.jqGrid.min.js'
        ];
        $this->registerLanguageAsset();
    }

    protected function registerLanguageAsset()
    {
        $language = Yii::$app->language;
        if (!file_exists(Yii::getAlias($this->sourcePath . "/js/i18n/grid.locale-{$language}.js"))) {
            $language = substr($language, 0, 2);
            if (!file_exists(Yii::getAlias($this->sourcePath . "/js/i18n/grid.locale-{$language}.js"))) {
                return;
            }
        }
        $this->js[] = "/js/i18n/grid.locale-{$language}.js";
    }
}
