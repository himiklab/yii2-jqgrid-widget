<?php
/**
 * @link https://github.com/himiklab/yii2-jqgrid-widget
 * @copyright Copyright (c) 2014-2015 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\jqgrid;

use yii\base\InvalidParamException;
use yii\base\Widget;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;

/**
 * jqGrid widget for Yii2
 *
 * For example:
 *
 * ```php
 * echo JqGridWidget::widget([
 *   'gridSettings' => [
 *       'colNames' => ['Title', 'Author', 'Language'],
 *       'colModel' => [
 *           ['name' => 'title', 'index' => 'title', 'editable' => true],
 *           ['name' => 'author', 'index' => 'author', 'editable' => true],
 *           ['name' => 'language', 'index' => 'language', 'editable' => true]
 *       ],
 *       'rowNum' => 15,
 *       'autowidth' => true,
 *       'height' => 'auto',
 *   ],
 *   'pagerSettings' => [
 *       'edit' => ['reloadAfterSubmit' => true, 'modal' => true],
 *       'add' => ['reloadAfterSubmit' => true, 'modal' => true,
 *       'del' => true
 *   ],
 *   'enableFilterToolbar' => true
 * ]);
 * ```
 *
 * @see https://github.com/free-jqgrid/jqGrid
 * @author HimikLab
 * @package himiklab\jqgrid
 */
class JqGridWidget extends Widget
{
    const REQUEST_METHOD_POST = 'POST';
    const REQUEST_METHOD_GET = 'GET';

    /** @var string */
    public $requestUrl = 'jqgrid';

    /** @var bool */
    public $enablePager = true;

    /** @var bool */
    public $enableCellEdit = false;

    /** @var bool */
    public $enableColumnChooser = false;

    /** @var bool */
    public $enableXMLExport = false;

    /** @var bool */
    public $enableFilterToolbar = false;

    /** @var bool */
    public $enableHiddenColumnsOptimization = false;

    /** @var array */
    public $filterToolbarSettings = [];

    /** @var array */
    public $gridSettings = [];

    /** @var array */
    public $pagerSettings = [];

    /** @var array */
    public $hiddenColumnsOptimizationExclusion = [];

    /** @var self::REQUEST_METHOD_POST|self::REQUEST_METHOD_GET */
    public $requestMethod = self::REQUEST_METHOD_POST;

    protected $jsonSettings;

    public function init()
    {
        parent::init();
        $view = $this->getView();
        $widgetId = $this->id;

        if (isset($this->gridSettings['iconSet']) && $this->gridSettings['iconSet'] === 'fontAwesome') {
            $useFontAwesome = true;
        } else {
            $useFontAwesome = false;
        }

        $this->jsonSettings =
            (YII_DEBUG ? JSON_PRETTY_PRINT : 0) | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK;

        $script = "jQuery(\"#jqGrid-{$widgetId}\").jqGrid({$this->prepareGridSettings($this->gridSettings)})";
        if ($this->enablePager) {
            $script .= PHP_EOL .
                ".navGrid('#jqGrid-pager-{$widgetId}', {$this->preparePagerSettings($this->pagerSettings)})";
        }
        if ($this->enableFilterToolbar) {
            $script .= PHP_EOL .
                ".filterToolbar({$this->prepareToolbarSettings($this->filterToolbarSettings)})";
        }

        if ($this->enableColumnChooser) {
            $buttonOptions = [
                'caption' => '',
                'title' => new JsExpression('jQuery.jgrid.col.caption'),
                'buttonicon' => $useFontAwesome ? 'fa fa-lg fa-fw fa-calculator' : 'ui-icon-calculator',
                'onClickButton' => $this->enableHiddenColumnsOptimization ? new JsExpression(
                    "function() {
                        jQuery(this).jqGrid('columnChooser', {
                            done: function(perm) {
                                if(perm) {
                                    this.jqGrid('remapColumns', perm, true);
                                    this.trigger('reloadGrid');
                                }
                            }
                        });
                    }"
                ) : new JsExpression("function(){jQuery(this).jqGrid('columnChooser');}")
            ];
            $buttonOptions = Json::encode($buttonOptions, $this->jsonSettings);
            $script .= PHP_EOL . ".navButtonAdd('#jqGrid-pager-{$widgetId}', {$buttonOptions})";
        }

        if ($this->enableXMLExport) {
            $buttonOptions = [
                'caption' => '',
                'title' => 'Export to Excel XML',
                'buttonicon' => $useFontAwesome ? 'fa fa-file-excel-o' : 'ui-icon-document',
                'onClickButton' => new JsExpression(
                    "function(){jQuery.jgrid.XMLExport('{$widgetId}', 'ExcelXML.xml');}"
                )
            ];
            $buttonOptions = Json::encode($buttonOptions, $this->jsonSettings);
            $script .= PHP_EOL . ".navButtonAdd('#jqGrid-pager-{$widgetId}', {$buttonOptions})";
        }

        $view->registerJs($script, $view::POS_READY);
        WidgetAsset::register($view);
    }

    public function run()
    {
        $widgetId = $this->id;

        echo "<table id='jqGrid-{$widgetId}'></table>" . PHP_EOL;
        if ($this->enablePager) {
            echo "<div id='jqGrid-pager-{$widgetId}'></div>" . PHP_EOL;
        }
    }

    protected function prepareGridSettings($gridUserSettings)
    {
        $widgetId = $this->id;

        $gridSettings['url'] = Url::to([$this->requestUrl, 'action' => 'request']);
        $gridSettings['datatype'] = 'json';
        $gridSettings['iconSet'] = 'jQueryUI'; // OlegKi's version only

        if ($this->enableHiddenColumnsOptimization) {
            $gridSettings['serializeGridData'] = new JsExpression(
                "function(postData) {
                    var colModel = jQuery('#jqGrid-{$widgetId}').jqGrid('getGridParam', 'colModel');
                    var visibleColumns = [];
                    for (var i = 0; i < colModel.length; ++i) {
                        var colName = colModel[i].name;
                        if (!colModel[i].hidden && colName != 'cb' && colName != 'subgrid' && colName != 'rn') {
                            visibleColumns.push(colName);
                        }
                    }
                    visibleColumns = visibleColumns.concat(" .
                Json::encode($this->hiddenColumnsOptimizationExclusion, $this->jsonSettings) . ");
                    return jQuery.extend({}, postData, {visibleColumns: visibleColumns});
                }"
            );
        }
        $gridSettings['mtype'] = $this->requestMethod === self::REQUEST_METHOD_POST ? 'POST' : 'GET';
        if ($this->enablePager) {
            $gridSettings['pager'] = "#jqGrid-pager-{$widgetId}";
        }
        if ($this->enableCellEdit) {
            $gridSettings['cellEdit'] = true;
            $gridSettings['cellurl'] = Url::to([$this->requestUrl, 'action' => 'edit']);
        }
        $gridSettings = array_merge($gridSettings, $gridUserSettings);

        return Json::encode($gridSettings, $this->jsonSettings);
    }

    protected function preparePagerSettings($pagerUserSettings)
    {
        if ($pagerUserSettings === false) {
            return '{}';
        }

        $pagerOptions = [
            'edit' => false,
            'add' => false,
            'del' => false,
            'search' => false,
            'view' => false
        ];
        foreach ($pagerUserSettings as $optionName => $optionSettings) {
            if ($optionSettings === false) {
                continue;
            } elseif ($optionSettings === true) {
                $optionSettings = [];
            }

            switch ($optionName) {
                case 'edit':
                    $editSettings['url'] = Url::to([$this->requestUrl, 'action' => 'edit']);
                    $editSettings['afterSubmit'] = new JsExpression('
                    function(response){
                        return [response.responseText == "", response.responseText, null];
                    }');
                    $pagerOptions['edit'] = array_merge($editSettings, $optionSettings);
                    break;
                case 'add':
                    $addSettings['url'] = Url::to([$this->requestUrl, 'action' => 'add']);
                    $addSettings['afterSubmit'] = new JsExpression('
                    function(response){
                        return [response.responseText == "", response.responseText, null];
                    }');
                    $pagerOptions['add'] = array_merge($addSettings, $optionSettings);
                    break;
                case 'del':
                    $delSettings['url'] = Url::to([$this->requestUrl, 'action' => 'del']);
                    $delSettings['afterSubmit'] = new JsExpression('
                    function(response){
                        return [response.responseText == "", response.responseText, null];
                    }');
                    $pagerOptions['del'] = array_merge($delSettings, $optionSettings);
                    break;
                case 'search':
                    $pagerOptions['search'] = $optionSettings;
                    break;
                case 'view':
                    $pagerOptions['view'] = $optionSettings;
                    break;
                default:
                    throw new InvalidParamException("Invalid param `$optionName` in pager settings");
            }
        }

        $resultOptions = [];
        $resultSettings = [];
        foreach ($pagerOptions as $optionName => $optionSettings) {
            if ($optionSettings === false) {
                $resultOptions[$optionName] = false;
                $resultSettings[] = '{}';
            } else {
                $resultOptions[$optionName] = true;
                $resultSettings[] = Json::encode($optionSettings, $this->jsonSettings);
            }
        }
        $resultOptions = Json::encode($resultOptions, $this->jsonSettings);

        array_unshift($resultSettings, $resultOptions);
        return implode(',' . PHP_EOL, $resultSettings);
    }

    protected function prepareToolbarSettings($filterToolbarSettings)
    {
        if (empty($filterToolbarSettings)) {
            return null;
        }

        return Json::encode($filterToolbarSettings, $this->jsonSettings);
    }

    /**
     * @param array|\yii\db\ActiveRecord $array
     * @return string
     */
    public static function optionsGenerate($array)
    {
        $result = ':';
        foreach ($array as $key => $option) {
            $result .= ";{$key}:{$option}";
        }
        return $result;
    }
}
