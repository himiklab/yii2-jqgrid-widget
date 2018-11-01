<?php
/**
 * @link https://github.com/himiklab/yii2-jqgrid-widget
 * @copyright Copyright (c) 2014-2018 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\jqgrid;

use yii\base\Widget;
use yii\helpers\Json;

/**
 * Very simple jqGrid widget version for Yii2
 * More functional version is JqGridWidget.
 *
 * @see https://github.com/free-jqgrid/jqGrid
 * @author HimikLab
 * @package himiklab\jqgrid
 */
class JqGridSimpleWidget extends Widget
{
    /** @var array */
    public $gridSettings = [];

    /** @var array */
    public $navGridSettings;

    /** @var string */
    public $otherGridMethods;

    public function init()
    {
        parent::init();
        $view = $this->getView();
        $widgetId = $this->id;
        $jsonSettings =
            (YII_DEBUG ? JSON_PRETTY_PRINT : 0) | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK;

        $script = "jQuery('#jqGrid-{$widgetId}').jqGrid(" . Json::encode($this->gridSettings, $jsonSettings) . ')';
        if (!empty($this->navGridSettings)) {
            $script .= PHP_EOL .
                ".navGrid('#jqGrid-pager-{$widgetId}', " . Json::encode($this->navGridSettings, $jsonSettings) . ')';
        }
        if (!empty($this->otherGridMethods)) {
            $script .= $this->otherGridMethods;
        }

        $view->registerJs($script, $view::POS_READY);
        WidgetAsset::register($view);
    }

    public function run()
    {
        $widgetId = $this->id;

        echo "<table id='jqGrid-{$widgetId}'></table>" . PHP_EOL;
        echo "<div id='jqGrid-pager-{$widgetId}'></div>" . PHP_EOL;
    }
}
