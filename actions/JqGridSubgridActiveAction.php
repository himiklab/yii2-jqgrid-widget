<?php
/**
 * @link https://github.com/himiklab/yii2-jqgrid-widget
 * @copyright Copyright (c) 2014-2018 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\jqgrid\actions;

use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\web\Response;

/**
 * Action for subgrid jqGrid widget based on ActiveDataProvider.
 *
 * For example:
 *
 * ```php
 * public function behaviors()
 * {
 *  return [
 *       'jqgrid-subgrid' => [
 *           'class' => JqGridActiveAction::className(),
 *           'model' => Comment::className(),
 *           'modelRelationId' => 'page_id',
 *       ],
 *  ];
 * }
 * ```
 *
 * @author HimikLab
 * @package himiklab\jqgrid\actions
 */
class JqGridSubgridActiveAction extends Action
{
    use JqGridActionTrait;

    /** @var string|ActiveRecord $model */
    public $model;

    /** @var string|integer */
    public $modelRelationId;

    /**
     * @var array|callable $columns the columns being selected.
     * This is used to construct the SELECT clause in a SQL statement. If not set, it means selecting all columns.
     */
    public $columns = [];

    /** @var callable */
    public $scope;

    public function run()
    {
        if (!\is_subclass_of($this->model, ActiveRecord::className())) {
            throw new InvalidConfigException('The `model` param must be object or class extends \yii\db\ActiveRecord.');
        }
        if (!$this->modelRelationId) {
            throw new InvalidConfigException('The `modelRelationId` param must be set.');
        }
        if (\is_string($this->model)) {
            $this->model = new $this->model;
        }

        if (\is_callable($this->columns)) {
            $this->columns = \call_user_func($this->columns);
        }

        $model = $this->model;
        if (empty($this->columns)) {
            $this->columns = $model->attributes();
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        return $this->requestAction($this->getRequestData());
    }

    /**
     * @return array JSON answer
     */
    protected function requestAction($requestData)
    {
        $model = $this->model;
        $query = $model::find();

        if (\is_callable($this->scope)) {
            \call_user_func($this->scope, $query);
        }
        $query->andWhere([$this->modelRelationId => $requestData['id']]);
        $dataProvider = new ActiveDataProvider(['query' => $query]);

        $response = [];
        $i = 0;
        foreach ($dataProvider->getModels() as $record) {
            /** @var ActiveRecord $record */
            foreach ($this->columns as $modelAttribute) {
                $response['rows'][$i][$modelAttribute] = $this->getValueFromAr($record, $modelAttribute);
            }
            ++$i;
        }

        return $response;
    }
}
