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
use yii\data\Pagination;
use yii\data\Sort;
use yii\web\BadRequestHttpException;
use yii\base\InvalidValueException;

/**
 * ArrayDataProvider based action for jqGrid widget
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
 * @package himiklab\jqgrid
 */
class JqGridArrayAction extends Action
{
    /** @var array $models ArrayDataProvider's models property */
    public $models;

    public function run()
    {
        if (!$getActionParam = Yii::$app->request->get('action')) {
            throw new BadRequestHttpException('GET param `action` isn`t set');
        }

        switch ($getActionParam) {
            case 'request':
                header('Content-Type: application/json; charset=utf-8');
                echo $this->request(
                    $this->models,
                    Yii::$app->request->post()
                );
                break;
            default:
                throw new BadRequestHttpException('Unsupported GET `action` param');
        }
    }

    /**
     * @param array $models
     * @param array $requestData
     * @return string
     * @throws InvalidValueException
     */
    protected function request($models, $requestData)
    {
        // pagination
        $pagination = new Pagination;
        $pagination->page = $requestData['page'] - 1; // ArrayDataProvider is zero-based, jqGrid not
        $pagination->pageSize = $requestData['rows'];

        // sorting
        if (isset($requestData['sidx']) && $requestData['sidx'] != ''
            && ($requestData['sord'] === 'asc' || $requestData['sord'] === 'desc')
        ) {
            $sort = $this->processingSort($requestData);
        } else {
            $sort = false;
        }

        $dataProvider = new ArrayDataProvider(
            [
                'allModels' => $models,
                'pagination' => $pagination,
                'sort' => $sort
            ]
        );
        $recordsTotalCount = $dataProvider->totalCount;

        $response = [];
        $response['page'] = $requestData['page'];
        $response['total'] =
            $requestData['rows'] != 0 ? ceil($recordsTotalCount / $requestData['rows']) : 0;
        $response['records'] = $recordsTotalCount;

        $i = 0;
        foreach ($dataProvider->getModels() as $id => $record) {
            if (!is_array($record)) {
                throw new InvalidValueException('`models` param isn\'t valid array');
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

    /**
     * @param array $requestData
     * @return Sort
     */
    protected function processingSort($requestData)
    {
        $attributes = [];
        $defaultOrder = [];

        $sidxArray = explode(',', $requestData['sidx']);
        if (count($sidxArray) > 1) {
            // multi-column
            foreach ($sidxArray as $sidx) {
                if (preg_match('/(.+)\s(asc|desc)/', $sidx, $sidxMatch)) {
                    $attributes[] = $sidxMatch[1];
                    $defaultOrder[$sidxMatch[1]] =
                        $sidxMatch[2] === 'asc' ? SORT_ASC : SORT_DESC;
                } else {
                    $attributes[] = trim($sidx);
                    $defaultOrder[trim($sidx)] =
                        $requestData['sord'] === 'asc' ? SORT_ASC : SORT_DESC;
                }
            }
        } else {
            //single-column
            $attributes[] = trim($requestData['sidx']);
            $defaultOrder[trim($requestData['sidx'])] =
                $requestData['sord'] === 'asc' ? SORT_ASC : SORT_DESC;
        }
        return new Sort([
            'attributes' => $attributes,
            'defaultOrder' => $defaultOrder
        ]);
    }
}
