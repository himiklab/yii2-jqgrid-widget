<?php
/**
 * @link https://github.com/himiklab/yii2-jqgrid-widget
 * @copyright Copyright (c) 2014 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\jqgrid;

use Yii;
use yii\base\Action;
use yii\helpers\Json;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;
use yii\data\Sort;
use yii\web\Response;
use yii\web\BadRequestHttpException;

/**
 * Action for jqGrid widget
 *
 * For example:
 *
 * ```php
 * public function behaviors()
 * {
 *  return [
 *       'jqgrid' => [
 *           'class' => JqGridAction::className(),
 *           'model' => Page::className(),
 *           'columns' => ['title', 'author', 'language']
 *       ],
 *  ];
 * }
 * ```
 *
 * @author HimikLab
 * @package himiklab\jqgrid
 */
class JqGridAction extends Action
{
    /** @var \yii\db\ActiveRecord $model */
    public $model;

    /**
     * @var array $columns the columns being selected.
     * This is used to construct the SELECT clause in a SQL statement. If not set, it means selecting all columns.
     */
    public $columns = [];

    public function run()
    {
        if (!$getActionParam = Yii::$app->request->get('action')) {
            throw new BadRequestHttpException('GET param `action` isn`t set');
        }

        /** @var \yii\db\ActiveRecord $model */
        $model = $this->model;

        // add PK if it exist and not set to $this->columns
        $modelPK = $model::primaryKey();
        if (isset($modelPK[0]) && !empty($this->columns) && !array_search($modelPK[0], $this->columns)) {
            $this->columns[] = $modelPK[0];
        }

        switch ($getActionParam) {
            case 'request':
                header('Content-Type: application/json; charset=utf-8');
                echo $this->request(
                    $model,
                    Yii::$app->request->post(),
                    $this->columns
                );
                break;
            case 'edit':
                $this->edit($model, Yii::$app->request->post());
                break;
            case 'add':
                $this->add($model, Yii::$app->request->post());
                break;
            case 'del':
                $this->del($model, Yii::$app->request->post());
                break;
            default:
                throw new BadRequestHttpException();
        }
    }

    /**
     * @param \yii\db\ActiveRecord $model
     * @param array $requestData
     * @param array $columns
     * @return string
     * @throws BadRequestHttpException
     */
    protected function request($model, $requestData, $columns)
    {
        if (is_string($model)) {
            $model = new $model;
        }

        /** @var \yii\db\ActiveQuery $query */
        $query = $model::find();
        if (!empty($columns)) {
            $query->select = $columns;
        }

        // search
        if (isset($requestData['_search']) && $requestData['_search'] === 'true') {
            $searchData = [];

            // filter panel
            foreach ($model->attributes() as $column) {
                if (array_key_exists($column, $requestData)) {
                    $searchData['rules'][] = [
                        'op' => 'cn',
                        'field' => $column,
                        'data' => $requestData[$column]
                    ];
                }
            }

            // search panel
            if (isset($requestData['filters'])) {
                if ($requestData['filters'] != '') {
                    $searchData = Json::decode($requestData['filters'], true);
                } else {
                    $searchData['rules'][] = [
                        'op' => $requestData['searchOper'],
                        'field' => $requestData['searchField'],
                        'data' => $requestData['searchString']
                    ];
                }
            }

            $this->addSearchOptionsRecursively($query, $searchData);
        }

        // pagination
        $pagination = new Pagination;
        $pagination->page = $requestData['page'] - 1; // ActiveDataProvider is zero-based, jqGrid not
        $pagination->pageSize = $requestData['rows'];

        // sorting
        $sort = false;
        if (isset($requestData['sidx']) && $requestData['sidx'] != ''
            && ($requestData['sord'] === 'asc' || $requestData['sord'] === 'desc')
        ) {
            $sort = new Sort;
            $sort->defaultOrder = [
                $requestData['sidx'] => $requestData['sord'] === 'asc' ? SORT_ASC : SORT_DESC
            ];
        }

        $dataProvider = new ActiveDataProvider(
            [
                'query' => $query,
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
        foreach ($dataProvider->getModels() as $record) {
            /** @var \yii\db\ActiveRecord $record */
            $response['rows'][$i]['id'] = $record->primaryKey;
            foreach ($record->attributes() as $column) {
                $columnValue = $record->$column;
                if (!$record->isPrimaryKey([$column]) && $columnValue !== null) {
                    $response['rows'][$i]['cell'][$column] = $columnValue;
                }
            }
            ++$i;
        }
        return Json::encode($response, YII_DEBUG ? JSON_PRETTY_PRINT : 0);
    }

    /**
     * @param \yii\db\ActiveRecord $model
     * @param array $requestData
     * @throws BadRequestHttpException
     */
    protected function edit($model, $requestData)
    {
        if (!isset($requestData['id'])) {
            throw new BadRequestHttpException('Id param isn`t set');
        }

        /** @var \yii\db\ActiveRecord $query */
        $query = $model::findOne($requestData['id']);

        foreach ($query->attributes() as $column) {
            if (isset($requestData[$column])) {
                $query->$column = $requestData[$column];
            }
        }
        $query->save();
    }

    /**
     * @param \yii\db\ActiveRecord $model
     * @param array $requestData
     * @throws BadRequestHttpException
     */
    protected function add($model, $requestData)
    {
        if (!isset($requestData['id'])) {
            throw new BadRequestHttpException('Id param isn`t set');
        }
        if (is_string($model)) {
            $model = new $model;
        }

        foreach ($model->attributes() as $column) {
            if (isset($requestData[$column])) {
                $model->$column = $requestData[$column];
            }
        }
        $model->save();
    }

    /**
     * @param \yii\db\ActiveRecord $model
     * @param array $requestData
     * @throws BadRequestHttpException
     * @throws \Exception
     */
    protected function del($model, $requestData)
    {
        if (!isset($requestData['id'])) {
            throw new BadRequestHttpException('Id param isn`t set');
        }

        foreach (explode(',', $requestData['id']) as $id) {
            $model::findOne($id)->delete();
        }
    }

    /**
     * @param \yii\db\ActiveQuery $query
     * @param array $searchData
     * @throws BadRequestHttpException
     */
    protected function addSearchOptionsRecursively($query, $searchData)
    {
        $groupCondition = 'andWhere';
        if (isset($searchData['groupOp'])) {
            foreach ($searchData['groups'] as $group) {
                $this->addSearchOptionsRecursively($query, $group);
            }

            if ($searchData['groupOp'] === 'OR') {
                $groupCondition = 'orWhere';
            } elseif ($searchData['groupOp'] !== 'AND') {
                throw new BadRequestHttpException('Unsupported value in `groupOp` param');
            }
        }

        foreach ($searchData['rules'] as $rule) {
            switch ($rule['op']) {
                case 'eq':
                    $query->$groupCondition([$rule['field'] => $rule['data']]);
                    break;
                case 'ne':
                    $query->$groupCondition(['<>', $rule['field'], $rule['data']]);
                    break;
                case 'cn':
                    $query->$groupCondition(['like', $rule['field'], $rule['data']]);
                    break;
                case 'nc':
                    $query->$groupCondition(['not like', $rule['field'], $rule['data']]);
                    break;
                case 'nu':
                    $query->$groupCondition([$rule['field'] => null]);
                    break;
                case 'nn':
                    $query->$groupCondition(['<>', $rule['field'], null]);
                    break;
                case 'in':
                    $rule['data'] = explode(',', $rule['data']);
                    array_walk($rule['data'], 'trim');
                    $query->$groupCondition(['in', $rule['field'], $rule['data']]);
                    break;
                case 'ni':
                    $rule['data'] = explode(',', $rule['data']);
                    array_walk($rule['data'], 'trim');
                    $query->$groupCondition(['not in', $rule['field'], $rule['data']]);
                    break;
                default:
                    throw new BadRequestHttpException('Unsupported value in `op` or `searchOper` param');
            }
        }
    }
}
