<?php
/**
 * @link https://github.com/himiklab/yii2-jqgrid-widget
 * @copyright Copyright (c) 2014-2015 HimikLab
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
            if (preg_match('/(.+)\[\]$/', $name, $nameParts)) {
                $vars[$nameParts[1]][] = $value;
            } else {
                $vars[$name] = $value;
            }
        }
        return $vars;
    }
}
