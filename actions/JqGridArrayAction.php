<?php
/**
 * @link https://github.com/himiklab/yii2-jqgrid-widget
 * @copyright Copyright (c) 2014 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\jqgrid\actions;

use Yii;
use yii\base\Action;
use yii\helpers\Json;
use yii\data\ArrayDataProvider;
use yii\web\BadRequestHttpException;
use yii\base\InvalidConfigException;

/**
 * Action for grid.js widget based on ArrayDataProvider.
 *
 * For example:
 *
 * ```php
 * public function behaviors()
 * {
 *  $query = new \yii\db\Query;
 *  return [
 *       'jqgrid' => [
 *           'class' => JqGridArrayAction::className(),
 *           'models' => $query->from('page')->select('title, author, language')->all(),
 *       ],
 *  ];
 * }
 * ```
 *
 * @author HimikLab
 * @package himiklab\jqgrid\actions
 */
class JqGridArrayAction extends Action
{
    use JqGridActionTrait;

    /** @var array $models ArrayDataProvider's models property */
    public $models;

    public function run()
    {
        if (!$getActionParam = Yii::$app->request->get('action')) {
            throw new BadRequestHttpException('GET param `action` isn\'t set.');
        }

        switch ($getActionParam) {
            case 'request':
                header('Content-Type: application/json; charset=utf-8');
                echo $this->requestAction($this->getRequestData());
                break;
            default:
                throw new BadRequestHttpException('Unsupported GET `action` param.');
        }
    }

    /**
     * @param array $requestData
     * @return string JSON answer
     * @throws InvalidConfigException
     */
    protected function requestAction($requestData)
    {
        $dataProvider = new ArrayDataProvider(
            [
                'allModels' => $this->models,
                'pagination' => $this->getPagination($requestData),
                'sort' => $this->getSort($requestData)
            ]
        );
        $recordsTotalCount = $dataProvider->totalCount;

        $response = [];
        $response['page'] = $requestData['page'];
        $response['total'] = ($requestData['rows'] != 0 ? ceil($recordsTotalCount / $requestData['rows']) : 0);
        $response['records'] = $recordsTotalCount;

        $i = 0;
        foreach ($dataProvider->getModels() as $id => $record) {
            if (!is_array($record)) {
                throw new InvalidConfigException('The `models` param isn\'t valid array.');
            }
            $response['rows'][$i]['id'] = $id;
            foreach ($record as $key => $value) {
                $response['rows'][$i]['cell'][$key] = $value;
            }
            ++$i;
        }

        return Json::encode(
            $response,
            (YII_DEBUG ? JSON_PRETTY_PRINT : 0) | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK
        );
    }
}
