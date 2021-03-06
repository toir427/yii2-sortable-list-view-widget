<?php
/**
 * @link https://github.com/handysolver/yii2-sortable-list-view-widget
 * @copyright Copyright (c) 2014 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace handysolver\sortablelist;

use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;

/**
 * Behavior for sortable Yii2 ListView widget.
 *
 * For example:
 *
 * ```php
 * public function behaviors()
 * {
 *    return [
 *       'sort' => [
 *           'class' => SortableListBehavior::className(),
 *           'sortableAttribute' => 'sortOrder',
 *           'scope' => function ($query) {
 *              $query->andWhere(['group_id' => $this->group_id]);
 *           },
 *       ],
 *   ];
 * }
 * ```
 *
 * @author HimikLab
 * @package handysolver\sortablelist
 */
class SortableListBehavior extends Behavior
{
    /** @var string database field name for row sorting */
    public $sortableAttribute = 'sortOrder';

    /** @var callable */
    public $scope;

    public function events()
    {
        return [ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert'];
    }

    public function listSort($items)
    {
        /** @var ActiveRecord $model */
        $model = $this->owner;
        if (!$model->hasAttribute($this->sortableAttribute)) {
            throw new InvalidConfigException("Model does not have sortable attribute `{$this->sortableAttribute}`.");
        }

        $newOrder = [];
        $models = [];
        foreach ($items as $old => $new) {
            $models[$new] = $model::findOne($new);
            $newOrder[$old] = $models[$new]->{$this->sortableAttribute};
        }
        $model::getDb()->transaction(function () use ($models, $newOrder) {
            foreach ($newOrder as $modelId => $orderValue) {
                /** @var ActiveRecord[] $models */
                $models[$modelId]->updateAttributes([$this->sortableAttribute => $orderValue]);
            }
        });
    }

    public function beforeInsert()
    {
        /** @var ActiveRecord $model */
        $model = $this->owner;
        if (!$model->hasAttribute($this->sortableAttribute)) {
            throw new InvalidConfigException("Invalid sortable attribute `{$this->sortableAttribute}`.");
        }

        $query = $model::find();
        if (is_callable($this->scope)) {
            call_user_func($this->scope, $query);
        }

        $maxOrder = $query->max('{{' . trim($model::tableName(), '{}') . '}}.[[' . $this->sortableAttribute . ']]');
        $model->{$this->sortableAttribute} = $maxOrder + 1;
    }
}
