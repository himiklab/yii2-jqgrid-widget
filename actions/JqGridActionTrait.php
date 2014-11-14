<?php
/**
 * @link https://github.com/himiklab/yii2-jqgrid-widget
 * @copyright Copyright (c) 2014 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\jqgrid\actions;

use Yii;
use yii\web\BadRequestHttpException;

/**
 * @author HimikLab
 * @package himiklab\jqgrid\actions
 */
trait JqGridActionTrait
{
    /**
     * @return array
     * @throws BadRequestHttpException
     */
    protected function getRequestData()
    {
        if (Yii::$app->request->method === 'POST') {
            return Yii::$app->request->post();
        } elseif (Yii::$app->request->method === 'GET') {
            $requestData = Yii::$app->request->get();
            unset($requestData['action']);
            return $requestData;
        } else {
            throw new BadRequestHttpException('Unsupported request method');
        }
    }
}
