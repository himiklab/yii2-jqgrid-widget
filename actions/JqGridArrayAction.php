<?php
/**
 * @link https://github.com/himiklab/yii2-jqgrid-widget
 * @copyright Copyright (c) 2014-2015 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\jqgrid\actions;

use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\data\ArrayDataProvider;
use yii\data\Sort;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Action for jqGrid widget based on ArrayDataProvider.
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

    /** @var array|callable $models ArrayDataProvider's models property */
    public $models;

    public function run()
    {
        if (!$getActionParam = Yii::$app->request->get('action')) {
            throw new BadRequestHttpException('GET param `action` isn\'t set.');
        }

        switch ($getActionParam) {
            case 'request':
                Yii::$app->response->format = Response::FORMAT_JSON;
                return $this->requestAction($this->getRequestData());
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
        if (is_callable($this->models)) {
            $this->models = call_user_func($this->models);
        }
        
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

        return $response;
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
}
