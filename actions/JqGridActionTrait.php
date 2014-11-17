<?php
/**
 * @link https://github.com/himiklab/yii2-jqgrid-widget
 * @copyright Copyright (c) 2014 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\jqgrid\actions;

use Yii;
use yii\data\Sort;
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
            return Yii::$app->request->post();
        } elseif (Yii::$app->request->method === 'GET') {
            $requestData = Yii::$app->request->get();
            unset($requestData['action']); // delete service GET param
            return $requestData;
        } else {
            throw new BadRequestHttpException('Unsupported request method.');
        }
    }

    /**
     * @param array $requestData
     * @return bool|Sort
     */
    protected function getSort($requestData)
    {
        if (!isset($requestData['sidx']) || $requestData['sidx'] == ''
            || ($requestData['sord'] !== 'asc' && $requestData['sord'] !== 'desc')
        ) {
            return false;
        }

        $attributes = [];
        $defaultOrder = [];
        $sidxArray = explode(',', $requestData['sidx']);

        if (count($sidxArray) > 1) {
            // multi-column
            foreach ($sidxArray as $sidx) {
                if (preg_match('/(.+)\s(asc|desc)/', $sidx, $sidxMatch)) {
                    $attributes[] = $sidxMatch[1];
                    $defaultOrder[$sidxMatch[1]] = ($sidxMatch[2] === 'asc' ? SORT_ASC : SORT_DESC);
                } else {
                    $attributes[] = trim($sidx);
                    $defaultOrder[trim($sidx)] = ($requestData['sord'] === 'asc' ? SORT_ASC : SORT_DESC);
                }
            }
        } else {
            // single-column
            $attributes[0] = trim($requestData['sidx']);
            $defaultOrder[$attributes[0]] = ($requestData['sord'] === 'asc' ? SORT_ASC : SORT_DESC);
        }

        return new Sort([
            'attributes' => $attributes,
            'defaultOrder' => $defaultOrder
        ]);
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
}
