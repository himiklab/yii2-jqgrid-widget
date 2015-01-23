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

        $this->js = [
            'plugins/ui.multiselect.js',
            YII_DEBUG ? 'dist/grid.js-0.1.0.js' : 'dist/grid.js-0.1.0.min.js'
        ];
        $this->registerLanguageAsset();
    }

    protected function registerLanguageAsset()
    {
        $language = Yii::$app->language;
        if (!file_exists(Yii::getAlias($this->sourcePath . "/dist/i18n/grid.locale-{$language}.min.js"))) {
            $language = substr($language, 0, 2);
            if (!file_exists(Yii::getAlias($this->sourcePath . "/dist/i18n/grid.locale-{$language}.min.js"))) {
                return;
            }
        }
        $this->js[] = "/dist/i18n/grid.locale-{$language}.min.js";
    }
}
