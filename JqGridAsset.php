<?php
/**
 * @link https://github.com/himiklab/yii2-jqgrid-widget
 * @copyright Copyright (c) 2014-2015 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\jqgrid;

use Yii;
use yii\web\AssetBundle;

class JqGridAsset extends AssetBundle
{
    public $sourcePath = '@bower/free-jqgrid';

    public function init()
    {
        parent::init();

        $this->css = [
            YII_DEBUG ? 'plugins/ui.multiselect.css' : 'plugins/ui.multiselect.min.css',
            YII_DEBUG ? 'css/ui.jqgrid.css' : 'css/ui.jqgrid.min.css',
        ];

        $this->js = [
            YII_DEBUG ? 'plugins/ui.multiselect.js' : 'plugins/ui.multiselect.min.js',
            YII_DEBUG ? 'js/jquery.jqgrid.src.js' : 'js/jquery.jqgrid.min.js'
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
        $this->js[] = "js/i18n/grid.locale-{$language}.js";
    }
}
