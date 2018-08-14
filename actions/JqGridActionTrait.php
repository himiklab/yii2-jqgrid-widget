<?php
/**
 * @link https://github.com/himiklab/yii2-jqgrid-widget
 * @copyright Copyright (c) 2014-2017 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\jqgrid\actions;

use Yii;
use yii\data\Pagination;
use yii\web\BadRequestHttpException;

/**
 * @author HimikLab
 * @package himiklab\jqgrid\actions
 */
trait JqGridActionTrait
{
    /**
     * Returns an array of all request parameters.
     *
     * @return array
     * @throws BadRequestHttpException
     */
    protected function getRequestData()
    {
        if (Yii::$app->request->method === 'POST') {
            return $this->getRealPOSTData();
        }
        if (Yii::$app->request->method === 'GET') {
            $requestData = Yii::$app->request->get();
            unset($requestData['action']); // delete service GET param
            return $requestData;
        }

        throw new BadRequestHttpException('Unsupported request method.');
    }

    /**
     * @param array $requestData
     * @return Pagination
     */
    protected function getPagination($requestData)
    {
        return new Pagination([
            'page' => $requestData['page'] - 1, // Yii`s DataProviders is zero-based, jqGrid not
            'pageSize' => $requestData['rows']
        ]);
    }

    /**
     * @return array
     */
    protected function getRealPOSTData()
    {
        $pairs = explode('&', file_get_contents('php://input'));
        $vars = [];
        foreach ($pairs as $pair) {
            $pairParts = explode('=', $pair);
            $name = urldecode($pairParts[0]);

            $value = urldecode($pairParts[1]);
            if ($value === 'null') {
                $value = null;
            }
            if (preg_match('/(.+)\[\]$/', $name, $nameParts)) {
                $vars[$nameParts[1]][] = $value;
            } else {
                $vars[$name] = $value;
            }
        }

        return $vars;
    }

    /**
     * @param \yii\db\ActiveRecord|array $record
     * @param string $attribute
     * @param string $separator
     * @return array|null|string
     */
    protected function getValueFromAr($record, $attribute, $separator = "\n")
    {
        if (($pointPosition = \strrpos($attribute, '.')) !== false) {
            $record = $this->getValueFromAr($record, \substr($attribute, 0, $pointPosition));
            $attribute = \substr($attribute, $pointPosition + 1);
        }

        if ($record === null) {
            return null;
        }
        if (\is_array($record)) {
            $result = null;
            foreach ($record as $currentRecord) {
                $currentValue = $currentRecord->$attribute;
                if (\is_object($currentValue)) {
                    $result[] = $currentValue;
                } elseif (\is_array($currentValue)) {
                    if ($result === null) {
                        $result = $currentValue;
                    } else {
                        $result = \array_merge($currentValue, $result);
                    }
                } elseif ($currentValue === null) {
                    return null;
                } else {
                    $result .= ($currentRecord->$attribute . $separator);
                }
            }
            if (\is_string($result)) {
                return \trim($result, $separator);
            }

            return $result;
        }

        return $record->$attribute;
    }
}
