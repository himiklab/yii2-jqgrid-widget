<?php
/**
 * @link https://github.com/himiklab/yii2-jqgrid-widget
 * @copyright Copyright (c) 2014 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\jqgrid;

use yii\base\InvalidParamException;
use yii\base\Widget;
use yii\helpers\Json;
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
 * @see http://www.trirand.com/jqgridwiki/doku.php
 * @author HimikLab
 * @package himiklab\jqgrid
 */
class JqGridWidget extends Widget
{
    const REQUEST_METHOD_POST = 'POST';
    const REQUEST_METHOD_GET = 'GET';

    /** @var string $requestUrl */
    public $requestUrl = 'jqgrid';

    /** @var bool $enablePager */
    public $enablePager = true;

    /** @var bool $enableFilterToolbar */
    public $enableFilterToolbar = false;

    /** @var bool $enableCellEdit */
    public $enableCellEdit = false;

    /** @var bool $enableColumnChooser */
    public $enableColumnChooser = false;

    /** @var bool $enableXMLExport */
    public $enableXMLExport = false;

    /** @var array $gridSettings */
    public $gridSettings = [];

    /** @var array $pagerSettings */
    public $pagerSettings = [];

    /** @var array $filterToolbarSettings */
    public $filterToolbarSettings = [];

    /** @var self::REQUEST_METHOD_POST|self::REQUEST_METHOD_GET $requestMethod */
    public $requestMethod = self::REQUEST_METHOD_POST;

    protected $jsonSettings;

    public function init()
    {
        parent::init();
        $view = $this->getView();
        $widgetId = $this->id;

        $this->jsonSettings =
            (YII_DEBUG ? JSON_PRETTY_PRINT : 0) | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK;

        $script = "jQuery(\"#jqGrid-{$widgetId}\").jqGrid({$this->processingGridSettings($this->gridSettings)})";
        if ($this->enablePager) {
            $script .= PHP_EOL .
                ".navGrid('#jqGrid-pager-{$widgetId}', {$this->processingPagerSettings($this->pagerSettings)})";
        }
        if ($this->enableFilterToolbar) {
            $script .= PHP_EOL .
                ".filterToolbar({$this->processingFilterToolbarSettings($this->filterToolbarSettings)})";
        }

        if ($this->enableColumnChooser) {
            $buttonOptions = [
                'caption' => '',
                'title' => new JsExpression('jQuery.jgrid.col.caption'),
                'buttonicon' => 'ui-icon-calculator',
                'onClickButton' => new JsExpression('function(){jQuery(this).jqGrid(\'columnChooser\');}')
            ];
            $buttonOptions = Json::encode($buttonOptions, $this->jsonSettings);
            $script .= PHP_EOL . ".navButtonAdd('#jqGrid-pager-{$widgetId}', {$buttonOptions})";
        }

        if ($this->enableXMLExport) {
            $buttonOptions = [
                'caption' => '',
                'title' => 'Export to Excel XML',
                'buttonicon' => 'ui-icon-document',
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

    protected function processingGridSettings($gridUserSettings)
    {
        $widgetId = $this->id;

        $gridSettings['url'] = $this->requestUrl . '?action=request';
        $gridSettings['datatype'] = 'json';
        $gridSettings['mtype'] = $this->requestMethod === self::REQUEST_METHOD_POST ? 'POST' : 'GET';
        if ($this->enablePager) {
            $gridSettings['pager'] = "#jqGrid-pager-{$widgetId}";
        }
        if ($this->enableCellEdit) {
            $gridSettings['cellEdit'] = true;
            $gridSettings['cellurl'] = $this->requestUrl . '?action=edit';
        }
        $gridSettings = array_merge($gridSettings, $gridUserSettings);

        return Json::encode($gridSettings, $this->jsonSettings);
    }

    protected function processingPagerSettings($pagerUserSettings)
    {
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
                    $editSettings['url'] = $this->requestUrl . '?action=edit';
                    $pagerOptions['edit'] = array_merge($editSettings, $optionSettings);
                    break;
                case 'add':
                    $addSettings['url'] = $this->requestUrl . '?action=add';
                    $pagerOptions['add'] = array_merge($addSettings, $optionSettings);
                    break;
                case 'del':
                    $delSettings['url'] = $this->requestUrl . '?action=del';
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

    protected function processingFilterToolbarSettings($filterToolbarSettings)
    {
        return Json::encode($filterToolbarSettings, $this->jsonSettings);
    }
}
