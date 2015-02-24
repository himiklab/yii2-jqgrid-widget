<?php
/**
 * @link https://github.com/himiklab/yii2-jqgrid-widget
 * @copyright Copyright (c) 2014 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\jqgrid\actions;

use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\helpers\ArrayHelper;

/**
 * Action for grid.js widget based on ActiveDataProvider.
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
 *           'scope' => function ($query) {
 *               $query->select('title', 'author', 'language');
 *           },
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

    /** @var string|\yii\db\ActiveRecord $model */
    public $model;

    /**
     * @var array $columns the columns being selected.
     * This is used to construct the SELECT clause in a SQL statement. If not set, it means selecting all columns.
     */
    public $columns = [];

    /** @var callable */
    public $scope;

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
                echo $this->requestAction($this->getRequestData());
                break;
            case 'edit':
                $this->editAction($this->getRequestData());
                break;
            case 'add':
                $this->addAction($this->getRequestData());
                break;
            case 'del':
                $this->delAction($this->getRequestData());
                break;
            default:
                throw new BadRequestHttpException('Unsupported GET `action` param.');
        }
    }

    /**
     * @param array $requestData
     * @return string JSON answer
     * @throws BadRequestHttpException
     */
    protected function requestAction($requestData)
    {
        $model = $this->model;
        $query = $model::find();

        if (is_callable($this->scope)) {
            call_user_func($this->scope, $query);
        }

        // search
        if (isset($requestData['_search']) && $requestData['_search'] === 'true') {
            $this->prepareSearch($query, $requestData);
        }

        $dataProvider = new ActiveDataProvider(
            [
                'query' => $query,
                'pagination' => $this->getPagination($requestData),
                'sort' => $this->getSort($requestData)
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
                $response['rows'][$i]['cell'][$modelAttribute] = ArrayHelper::getValue($record, $modelAttribute);
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
     * @throws BadRequestHttpException
     */
    protected function editAction($requestData)
    {
        /** @var \yii\db\ActiveRecord $model */
        $model = $this->model;

        if (!isset($requestData['id'])) {
            throw new BadRequestHttpException('Id param isn\'t set.');
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
     * @param array $requestData
     * @throws BadRequestHttpException
     */
    protected function addAction($requestData)
    {
        /** @var \yii\db\ActiveRecord $model */
        $model = $this->model;

        if (!isset($requestData['id'])) {
            throw new BadRequestHttpException('Id param isn\'t set.');
        }

        foreach ($model->attributes() as $modelAttribute) {
            if (isset($requestData[$modelAttribute])) {
                $model->$modelAttribute = $requestData[$modelAttribute];
            }
        }
        $model->save();
    }

    /**
     * @param array $requestData
     * @throws BadRequestHttpException
     * @throws \Exception
     */
    protected function delAction($requestData)
    {
        /** @var \yii\db\ActiveRecord $model */
        $model = $this->model;

        if (!isset($requestData['id'])) {
            throw new BadRequestHttpException('Id param isn\'t set.');
        }

        foreach (explode(',', $requestData['id']) as $id) {
            $model::findOne($id)->delete();
        }
    }

    /**
     * @param \yii\db\ActiveQuery $query
     * @param array $requestData
     * @throws BadRequestHttpException
     */
    protected function prepareSearch($query, $requestData)
    {
        /** @var \yii\db\ActiveRecord $model */
        $model = $this->model;
        $searchData = [];

        // filter panel
        foreach ($model->attributes() as $modelAttribute) {
            if (array_key_exists($modelAttribute, $requestData)) {
                $searchData['rules'][] = [
                    'op' => 'bw',
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

        /** @var \yii\db\ActiveRecord $model */
        $model = $this->model;
        foreach ($searchData['rules'] as $rule) {
            if (!$model->isAttributeSafe($rule['field'])) {
                throw new BadRequestHttpException('Unsafe attribute.');
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
