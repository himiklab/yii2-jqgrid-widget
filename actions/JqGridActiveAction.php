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
use yii\data\ActiveDataProvider;
use yii\data\Pagination;
use yii\data\Sort;
use yii\web\BadRequestHttpException;
use yii\base\InvalidConfigException;

/**
 * ActiveDataProvider based action for jqGrid widget
 *
 * For example:
 *
 * ```php
 * public function behaviors()
 * {
 *  return [
 *       'jqgrid' => [
 *           'class' => JqGridActiveAction::className(),
 *           'model' => Page::className(),
 *           'columns' => ['title', 'author', 'language']
 *       ],
 *  ];
 * }
 * ```
 *
 * @author HimikLab
 * @package himiklab\jqgrid\actions
 */
class JqGridActiveAction extends Action
{
    use JqGridActionTrait;

    /** @var \yii\db\ActiveRecord $model */
    public $model;

    /**
     * @var array $columns the columns being selected.
     * This is used to construct the SELECT clause in a SQL statement. If not set, it means selecting all columns.
     */
    public $columns = [];

    public function run()
    {
        if (!is_subclass_of($this->model, '\yii\db\ActiveRecord')) {
            throw new InvalidConfigException('The `model` param must be object or class extends \yii\db\ActiveRecord.');
        }
        if (is_string($this->model)) {
            $this->model = new $this->model;
        }
        if (!$getActionParam = Yii::$app->request->get('action')) {
            throw new BadRequestHttpException('GET param `action` isn\'t set.');
        }

        // add PK if it exist and not set to $this->columns
        $model = $this->model;
        $modelPK = $model::primaryKey();
        if (isset($modelPK[0]) && !empty($this->columns) && !array_search($modelPK[0], $this->columns)) {
            $this->columns[] = $modelPK[0];
        }

        switch ($getActionParam) {
            case 'request':
                header('Content-Type: application/json; charset=utf-8');
                echo $this->request($model, $this->getRequestData(), $this->columns);
                break;
            case 'edit':
                $this->edit($model, $this->getRequestData());
                break;
            case 'add':
                $this->add($model, $this->getRequestData());
                break;
            case 'del':
                $this->del($model, $this->getRequestData());
                break;
            default:
                throw new BadRequestHttpException('Unsupported GET `action` param');
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
        $query = $model::find();
        if (!empty($columns)) {
            $query->select = $columns;
        }

        // search
        if (isset($requestData['_search']) && $requestData['_search'] === 'true') {
            $this->processingSearch($model, $query, $requestData);
        }

        // pagination
        $pagination = new Pagination;
        $pagination->page = $requestData['page'] - 1; // ActiveDataProvider is zero-based, jqGrid not
        $pagination->pageSize = $requestData['rows'];

        // sorting
        if (isset($requestData['sidx']) && $requestData['sidx'] != ''
            && ($requestData['sord'] === 'asc' || $requestData['sord'] === 'desc')
        ) {
            $sort = $this->processingSort($requestData);
        } else {
            $sort = false;
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

        if (!empty($this->columns)) {
            $attributes = $this->columns;
        } else {
            $attributes = $model->attributes();
        }
        $i = 0;
        foreach ($dataProvider->getModels() as $record) {
            /** @var \yii\db\ActiveRecord $record */
            if ($record->primaryKey !== null) {
                $response['rows'][$i]['id'] = $record->primaryKey;
            }

            foreach ($attributes as $modelAttribute) {
                $response['rows'][$i]['cell'][$modelAttribute] = $record->$modelAttribute;
            }
            ++$i;
        }
        return Json::encode(
            $response,
            (YII_DEBUG ? JSON_PRETTY_PRINT : 0) | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK
        );
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
        $record = $model::findOne($requestData['id']);

        foreach ($record->attributes() as $modelAttribute) {
            if (isset($requestData[$modelAttribute])) {
                $record->$modelAttribute = $requestData[$modelAttribute];
            }
        }
        $record->save();
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

        foreach ($model->attributes() as $modelAttribute) {
            if (isset($requestData[$modelAttribute])) {
                $model->$modelAttribute = $requestData[$modelAttribute];
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
     * @param array $requestData
     * @return Sort
     */
    protected function processingSort($requestData)
    {
        $sort = new Sort;
        $sidxArray = explode(',', $requestData['sidx']);

        if (count($sidxArray) > 1) {
            // multi-column
            foreach ($sidxArray as $sidx) {
                if (preg_match('/(.+)\s(asc|desc)/', $sidx, $sidxMatch)) {
                    $sort->defaultOrder[$sidxMatch[1]] =
                        $sidxMatch[2] === 'asc' ? SORT_ASC : SORT_DESC;
                } else {
                    $sort->defaultOrder[trim($sidx)] =
                        $requestData['sord'] === 'asc' ? SORT_ASC : SORT_DESC;
                }
            }
        } else {
            //single-column
            $sort->defaultOrder[trim($requestData['sidx'])] =
                $requestData['sord'] === 'asc' ? SORT_ASC : SORT_DESC;
        }
        return $sort;
    }

    /**
     * @param \yii\db\ActiveRecord $model
     * @param \yii\db\ActiveQuery $query
     * @param array $requestData
     * @throws BadRequestHttpException
     */
    protected function processingSearch($model, $query, $requestData)
    {
        $searchData = [];

        // filter panel
        foreach ($model->attributes() as $modelAttribute) {
            if (array_key_exists($modelAttribute, $requestData)) {
                $searchData['rules'][] = [
                    'op' => 'cn',
                    'field' => $modelAttribute,
                    'data' => $requestData[$modelAttribute]
                ];
            }
        }

        // search panel
        if (isset($requestData['filters'])) {
            if ($requestData['filters'] != '') {
                // advanced searching
                $searchData = Json::decode($requestData['filters'], true);
            } else {
                // single searching
                $searchData['rules'][] = [
                    'op' => $requestData['searchOper'],
                    'field' => $requestData['searchField'],
                    'data' => $requestData['searchString']
                ];
            }
        }

        $this->addSearchOptionsRecursively($query, $searchData);
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
            if (isset($searchData['groups'])) {
                foreach ($searchData['groups'] as $group) {
                    $this->addSearchOptionsRecursively($query, $group);
                }
            }

            if ($searchData['groupOp'] === 'OR') {
                $groupCondition = 'orWhere';
            } elseif ($searchData['groupOp'] !== 'AND') {
                throw new BadRequestHttpException('Unsupported value in `groupOp` param');
            }
        }

        foreach ($searchData['rules'] as $rule) {
            if (!$this->model->hasAttribute($rule['field'])) {
                throw new BadRequestHttpException('Unknown attribute');
            }
            switch ($rule['op']) {
                case 'eq':
                    $query->$groupCondition([$rule['field'] => $rule['data']]);
                    break;
                case 'ne':
                    $query->$groupCondition(['<>', $rule['field'], $rule['data']]);
                    break;
                case 'bw':
                    $query->$groupCondition(['like', $rule['field'], "{$rule['data']}%", false]);
                    break;
                case 'bn':
                    $query->$groupCondition(['not like', $rule['field'], "{$rule['data']}%", false]);
                    break;
                case 'ew':
                    $query->$groupCondition(['like', $rule['field'], "%{$rule['data']}", false]);
                    break;
                case 'en':
                    $query->$groupCondition(['not like', $rule['field'], "%{$rule['data']}", false]);
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
