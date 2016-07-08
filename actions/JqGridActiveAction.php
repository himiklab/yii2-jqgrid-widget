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
use yii\data\ActiveDataProvider;
use yii\data\Sort;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Action for jqGrid widget based on ActiveDataProvider.
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
    const COMPOSITE_KEY_DELIMITER = '%';

    use JqGridActionTrait;

    /** @var string|\yii\db\ActiveRecord $model */
    public $model;

    /**
     * @var array|callable $columns the columns being selected.
     * This is used to construct the SELECT clause in a SQL statement. If not set, it means selecting all columns.
     */
    public $columns = [];

    /** @var callable */
    public $scope;

    /** @var array */
    public $queryAliases = [];

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

        if (is_callable($this->columns)) {
            $this->columns = call_user_func($this->columns);
        }

        // add PK if it exist and not set to $this->columns
        $model = $this->model;
        $modelPK = $model::primaryKey();
        if (isset($modelPK[0]) && !empty($this->columns) && !in_array($modelPK[0], $this->columns)) {
            $this->columns[] = $modelPK[0];
        }

        if (empty($this->columns)) {
            $this->columns = $model->attributes();
        }

        $requestData = $this->getRequestData();
        if (isset($requestData['visibleColumns'])) {
            $this->columns = array_filter($this->columns, function ($column) use ($modelPK, $requestData) {
                return in_array($column, $requestData['visibleColumns'])
                || (isset($modelPK[0]) && $column == $modelPK[0]);
            });
        }

        switch ($getActionParam) {
            case 'request':
                Yii::$app->response->format = Response::FORMAT_JSON;
                return $this->requestAction($requestData);
            case 'edit':
                $this->editAction($requestData);
                break;
            case 'add':
                $this->addAction($requestData);
                break;
            case 'del':
                $this->delAction($requestData);
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
                'sort' => $this->getSort($requestData, $query)
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
            if ($record->primaryKey !== null) {
                if (is_array($record->primaryKey)) {
                    $response['rows'][$i]['id'] = implode(self::COMPOSITE_KEY_DELIMITER, $record->primaryKey);
                } else {
                    $response['rows'][$i]['id'] = $record->primaryKey;
                }
            }

            foreach ($this->columns as $modelAttribute) {
                $response['rows'][$i]['cell'][$modelAttribute] = $this->getValue($record, $modelAttribute);
            }
            ++$i;
        }

        return $response;
    }

    /**
     * @param array $requestData
     * @throws BadRequestHttpException
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    protected function editAction($requestData)
    {
        $model = $this->model;
        if (!isset($requestData['id'])) {
            throw new BadRequestHttpException('Id param isn\'t set.');
        }

        $modelPK = $model::primaryKey();
        if (count($modelPK) > 1) {
            $idParts = explode(self::COMPOSITE_KEY_DELIMITER, $requestData['id']);
            $recordCondition = array_combine($modelPK, $idParts);
        } else {
            $recordCondition = $requestData['id'];
        }

        /** @var \yii\db\ActiveRecord $record */
        if (($record = $model::findOne($recordCondition)) === null) {
            return;
        }

        $relationColumns = [];
        $recordAttributes = [];
        foreach ($this->columns as $column) {
            if (isset($requestData[$column])) {
                if ((strpos($column, '.')) === false) {
                    // no relation
                    $record->$column = $requestData[$column];
                    $recordAttributes[] = $column;
                } else {
                    // with relation
                    preg_match('/(.+)\.([^\.]+)/', $column, $matches);
                    $relationColumns[$matches[1]][] = [
                        'column' => $matches[2],
                        'value' => $requestData[$column]
                    ];
                }
            }
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (count($relationColumns)) {
                foreach ($relationColumns as $relationName => $columns) {
                    $relation = $record;
                    $relationAttributes = [];
                    foreach (explode('.', $relationName) as $relationPart) {
                        $relation = $relation->$relationPart;
                        if ($relation === null) {
                            throw new BadRequestHttpException("Related model {$relationName} does not exist.");
                        }
                    }
                    if (is_array($relation)) {
                        throw new BadRequestHttpException('hasMany relation type isn\'t supported.');
                    }

                    foreach ($columns as $column) {
                        $relation->$column['column'] = $column['value'];
                        $relationAttributes = [$column['column']];
                    }
                    if (!$relation->save(true, $relationAttributes)) {
                        $transaction->rollBack();
                        $this->renderModelErrors($relation);
                        return;
                    }
                }
            }

            if (!$record->save(true, $recordAttributes)) {
                $transaction->rollBack();
                $this->renderModelErrors($record);
                return;
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        $transaction->commit();
    }

    /**
     * @param array $requestData
     * @throws BadRequestHttpException
     */
    protected function addAction($requestData)
    {
        $model = $this->model;

        if (!isset($requestData['id'])) {
            throw new BadRequestHttpException('Id param isn\'t set.');
        }
        if ($requestData['id'] === '_empty') {
            unset($requestData['id']);
        }

        foreach ($this->columns as $column) {
            if (isset($requestData[$column])) {
                $model->$column = $requestData[$column];
            }
        }
        if (!$model->save() && $model->hasErrors()) {
            $this->renderModelErrors($model);
        }
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

        $modelPK = $model::primaryKey();
        $deleteIds = explode(',', $requestData['id']);
        if (count($modelPK) > 1) {
            foreach ($deleteIds as &$currentCompositeId) {
                $idParts = explode(self::COMPOSITE_KEY_DELIMITER, $currentCompositeId);
                $currentCompositeId = array_combine($modelPK, $idParts);
            }
            unset($currentCompositeId);
        }

        foreach ($deleteIds as $currentId) {
            if (($currentModel = $model::findOne($currentId)) !== null) {
                /** @var \yii\db\ActiveRecord $currentModel */
                if (!$currentModel->delete()) {
                    $this->renderModelErrors($currentModel);
                    return;
                }
            } else {
                continue;
            }
        }
    }

    /**
     * @param \yii\db\ActiveQuery $query
     * @param array $requestData
     * @throws BadRequestHttpException
     */
    protected function prepareSearch($query, $requestData)
    {
        $searchData = [];

        // filter panel
        foreach ($this->columns as $modelAttribute) {
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
        $model = $this->model;

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
            if (!$this->prepareRelationField($query, $rule['field'])
                && !$model->isAttributeSafe($rule['field'])
            ) {
                throw new BadRequestHttpException('Unsafe attribute.');
            }

            if (isset($this->queryAliases[$rule['field']])) {
                $rule['field'] = $this->queryAliases[$rule['field']];
            }

            if ((strpos($rule['field'], '.')) === false) {
                $rule['field'] = $model::tableName() . '.' . $rule['field'];
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
                    $query->$groupCondition(['is not', $rule['field'], null]);
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
                case 'lt':
                    $query->$groupCondition(['<', $rule['field'], $rule['data']]);
                    break;
                case 'le':
                    $query->$groupCondition(['<=', $rule['field'], $rule['data']]);
                    break;
                case 'gt':
                    $query->$groupCondition(['>', $rule['field'], $rule['data']]);
                    break;
                case 'ge':
                    $query->$groupCondition(['>=', $rule['field'], $rule['data']]);
                    break;
                default:
                    throw new BadRequestHttpException('Unsupported value in `op` or `searchOper` param');
            }
        }
    }

    /**
     * @param array $requestData
     * @param \yii\db\ActiveQuery $query
     * @return bool|Sort
     */
    protected function getSort($requestData, $query)
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
                    $this->prepareRelationField($query, $sidxMatch[1]);

                    $sidxMatch[1] = trim($sidxMatch[1]);
                    $attributes[] = $sidxMatch[1];
                    $defaultOrder[$sidxMatch[1]] = ($sidxMatch[2] === 'asc' ? SORT_ASC : SORT_DESC);
                } else {
                    $sidx = trim($sidx);
                    $this->prepareRelationField($query, $sidx);

                    $attributes[] = $sidx;
                    $defaultOrder[$sidx] = ($requestData['sord'] === 'asc' ? SORT_ASC : SORT_DESC);
                }
            }
        } else {
            // single-column
            $attributes[0] = trim($requestData['sidx']);
            $this->prepareRelationField($query, $attributes[0]);

            $defaultOrder[$attributes[0]] = ($requestData['sord'] === 'asc' ? SORT_ASC : SORT_DESC);
        }

        return new Sort([
            'attributes' => $attributes,
            'defaultOrder' => $defaultOrder
        ]);
    }

    /**
     * @param \yii\db\ActiveQuery $query
     * @param string $field
     * @return bool
     * @throws BadRequestHttpException
     */
    protected function prepareRelationField($query, &$field)
    {
        if ((strpos($field, '.')) === false) {
            return false;
        }
        $model = $this->model;

        $fullRelation = '';
        $fieldElements = explode('.', $field);
        $fieldElementsCount = count($fieldElements);

        for ($i = 1; $i < $fieldElementsCount; ++$i) {
            $relationName = $fieldElements[$i - 1];
            $relationMethod = 'get' . ucfirst($relationName);

            if (!method_exists($model, $relationMethod)) {
                throw new BadRequestHttpException('Relation isn\'t exist.');
            }

            /** @var \yii\db\ActiveQuery $relationQuery */
            $relationQuery = $model->$relationMethod();
            /** @var \yii\db\ActiveRecord $relationModel */
            $model = new $relationQuery->modelClass;
            $fullRelation .= ('.' . $relationName);
        }
        $query->joinWith(trim($fullRelation, '.'));

        $attribute = $fieldElements[$fieldElementsCount - 1];
        if (!$model->isAttributeSafe($attribute)) {
            throw new BadRequestHttpException('Unsafe relation attribute.');
        }

        $field = $model::tableName() . '.' . $attribute;
        return true;
    }

    /**
     * @param \yii\db\ActiveRecord|array $record
     * @param string $attribute
     * @param string $separator
     * @return array|null|string
     */
    protected function getValue($record, $attribute, $separator = "\n")
    {
        if (($pointPosition = strrpos($attribute, '.')) !== false) {
            $record = $this->getValue($record, substr($attribute, 0, $pointPosition));
            $attribute = substr($attribute, $pointPosition + 1);
        }

        if ($record === null) {
            return null;
        } elseif (is_array($record)) {
            $result = null;
            foreach ($record as $currentRecord) {
                $currentValue = $currentRecord->$attribute;
                if (is_object($currentValue)) {
                    $result[] = $currentValue;
                } elseif (is_array($currentValue)) {
                    if ($result === null) {
                        $result = $currentValue;
                    } else {
                        $result = array_merge($currentValue, $result);
                    }
                } else {
                    $result .= ($currentRecord->$attribute . $separator);
                }
            }
            if (is_string($result)) {
                return trim($result, $separator);
            } else {
                return $result;
            }
        } else {
            return $record->$attribute;
        }
    }

    /**
     * @param \yii\db\ActiveRecord $model
     * @return string
     */
    protected function renderModelErrors($model)
    {
        $errors = '';
        foreach ($model->errors as $error) {
            $errors .= (implode(' ', $error) . ' ');
        }
        echo $errors;
    }
}
