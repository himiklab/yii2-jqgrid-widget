<?php
/**
 * @link https://github.com/himiklab/yii2-jqgrid-widget
 * @copyright Copyright (c) 2014-2018 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\jqgrid;

use Yii;
use yii\base\InvalidConfigException;
use yii\web\JsExpression;

/**
 * @author HimikLab
 * @package himiklab\jqgrid
 */
class JqGridHelper
{
    const DATEPICKER_CONFIG_STRING = 'dateFormat:"yy-mm-dd",changeMonth:true,changeYear:true';
    const JQGRID_SEARCHHIDDEN = true;

    /**
     * Function-helper to form jqGrid colModel.
     * @param array $columns
     * @param array $columnsIsVisible
     * @param array $columnsIsEditable
     * @return array
     * @throws InvalidConfigException
     */
    public static function jqgridColModel($columns, $columnsIsVisible = [], $columnsIsEditable = [])
    {
        $colModel = [];
        foreach ($columns as $columnKey => $columnValue) {
            if (\is_array($columnValue)) {
                if (!isset($columnValue['type'])) {
                    throw new InvalidConfigException('Unknown column type.');
                }

                switch ($columnValue['type']) {
                    case 'date':
                        $colModel[] = [
                            'name' => $columnKey,
                            'index' => $columnKey,
                            'formatter' => 'date',
                            'searchoptions' => ['searchhidden' => self::JQGRID_SEARCHHIDDEN, 'dataInit' =>
                                new JsExpression(
                                    'function(elem){jQuery(elem).datepicker({' . self::DATEPICKER_CONFIG_STRING . '});}'
                                ), 'sopt' => ['eq', 'ne', 'lt', 'le', 'gt', 'ge', 'nu', 'nn']],
                            'hidden' => !\in_array($columnKey, $columnsIsVisible),
                            'editable' => \in_array($columnKey, $columnsIsEditable),
                        ];
                        break;

                    case 'boolean':
                        $listData = [1 => Yii::t('yii', 'Yes'), 0 => Yii::t('yii', 'No')];
                        $colModel[] = [
                            'name' => $columnKey,
                            'formatter' => 'select',
                            'stype' => 'select',
                            'edittype' => 'select',
                            'searchoptions' => ['value' => self::optionsGenerate($listData),
                                'clearSearch' => false, 'searchhidden' => self::JQGRID_SEARCHHIDDEN,
                                'sopt' => ['eq', 'ne', 'nu', 'nn']],
                            'editoptions' => ['value' => $listData],
                            'hidden' => !\in_array($columnKey, $columnsIsVisible),
                            'editable' => \in_array($columnKey, $columnsIsEditable),
                        ];
                        break;

                    case 'integer':
                        $colModel[] = [
                            'name' => $columnKey,
                            'index' => $columnKey,
                            'formatter' => 'integer',
                            'searchoptions' => ['searchhidden' => self::JQGRID_SEARCHHIDDEN,
                                'sopt' => ['eq', 'ne', 'lt', 'le', 'gt', 'ge', 'nu', 'nn']],
                            'hidden' => !\in_array($columnKey, $columnsIsVisible),
                            'editable' => \in_array($columnKey, $columnsIsEditable),
                        ];
                        break;

                    case 'list':
                        if (!isset($columnValue['data'])) {
                            throw new InvalidConfigException('Data section is required for this type.');
                        }

                        $colModel[] = [
                            'name' => $columnKey,
                            'formatter' => 'select',
                            'stype' => 'select',
                            'edittype' => 'select',
                            'searchoptions' => ['value' => self::optionsGenerate($columnValue['data']),
                                'clearSearch' => false, 'searchhidden' => self::JQGRID_SEARCHHIDDEN,
                                'sopt' => ['eq', 'ne', 'nu', 'nn']],
                            'editoptions' => ['value' => $columnValue['data']],
                            'hidden' => !\in_array($columnKey, $columnsIsVisible),
                            'editable' => \in_array($columnKey, $columnsIsEditable),
                        ];
                        break;

                    case 'list_multiple':
                        if (!isset($columnValue['data'])) {
                            throw new InvalidConfigException('Data section is required for this type.');
                        }

                        $colModel[] = [
                            'name' => $columnKey,
                            'formatter' => new JsExpression(
                                "function (cellValue, options, rowdata, action) {
                                    if (!rowdata['{$columnKey}']) {
                                        return '';
                                    }

                                    var text = '';
                                    var values = options.colModel.editoptions.value;
                                    var data = rowdata['{$columnKey}'].toString().split('\\n');
                                    for (var key in data) {
                                        text += values[data[key]] + '\\n';
                                    }
                                    return text;
                                }"
                            ),
                            'stype' => 'select',
                            'edittype' => 'select',
                            'searchoptions' => ['value' => self::optionsGenerate($columnValue['data']),
                                'clearSearch' => false, 'searchhidden' => self::JQGRID_SEARCHHIDDEN,
                                'sopt' => ['eq', 'ne', 'nu', 'nn']],
                            'editoptions' => ['value' => $columnValue['data']],
                            'hidden' => !\in_array($columnKey, $columnsIsVisible),
                            'editable' => \in_array($columnKey, $columnsIsEditable),
                        ];
                        break;

                    case 'unique':
                        if (!isset($columnValue['data'])) {
                            throw new InvalidConfigException('Data section is required for this type.');
                        }

                        if (!isset($columnValue['data']['hidden'])) {
                            $columnValue['data']['hidden'] = !\in_array($columnKey, $columnsIsVisible);
                        }
                        $colModel[] = $columnValue['data'];
                        break;

                    default:
                        throw new InvalidConfigException('Unknown column type.');
                        break;
                }
            } else {
                $colModel[] = [
                    'name' => $columnValue,
                    'index' => $columnValue,
                    'searchoptions' => ['searchhidden' => self::JQGRID_SEARCHHIDDEN, 'sopt' =>
                        ['cn', 'nc', 'bw', 'bn', 'eq', 'ne', 'ew', 'en', 'nu', 'nn']],
                    'hidden' => !\in_array($columnValue, $columnsIsVisible),
                    'editable' => \in_array($columnValue, $columnsIsEditable),
                ];
            }
        }

        return $colModel;
    }

    /**
     * @param array $array
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
