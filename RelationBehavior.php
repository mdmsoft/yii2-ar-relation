<?php

namespace mdm\relation;

use Yii;
use yii\helpers\VarDumper;

/**
 * Description of RelationBehavior
 *
 * @property \yii\db\ActiveRecord $owner Description
 * @author Misbahul D Munir (mdmunir) <misbahuldmunir@gmail.com>
 */
class RelationBehavior extends \yii\base\Behavior
{

    /**
     * 
     * @param string|\yii\db\ActiveQuery $relation
     * @param array $options
     * @return boolean|\yii\db\ActiveRecord[]
     */
    public function saveRelation($relation, $options = [])
    {
        $model = $this->owner;
        if (!($relation instanceof \yii\db\ActiveQuery)) {
            $relation = $model->getRelation($relation);
        }

        /* @var $class \yii\db\ActiveRecord */
        $class = $relation->modelClass;
        $indexBy = false;
        if (!empty($options['indexBy'])) {
            $indexBy = $options['indexBy'];
            $oldIndexBy = $relation->indexBy;
            $relation->indexBy($indexBy);
        }
        $children = $relation->all();

        $formName = (new $class)->formName();
        $postDetails = Yii::$app->request->post($formName, []);

        $modelDetails = [];
        /* @var $detail \yii\db\ActiveRecord */
        $error = false;
        foreach ($postDetails as $index => $data) {
            $detail = new $class();
            $data = array_merge(isset($options['extra']) ? $options['extra'] : [], $data);
            $detail->load($data, '');
            foreach ($relation->link as $from => $to) {
                $detail->$from = $model->$to;
            }

            if ($indexBy === false) {
                foreach ($children as $i => $child) {
                    if ($child->getPrimaryKey() === $detail->getPrimaryKey()) {
                        $detail = $child;
                        $detail->load($data, '');
                        unset($children[$i]);
                        break;
                    }
                }
            } elseif (isset($children[$index])) {
                $detail = $children[$index];
                $detail->load($data, '');
                unset($children[$index]);
            }
            if (isset($options['beforeValidate'])) {
                call_user_func($options['beforeValidate'], $detail, $index);
            }
            $error = !$detail->validate() || $error;
            $modelDetails[$index] = $detail;
        }
        if (!$error) {
            // delete current children before inserting new
            $linkFilter = [];
            $columns = array_flip($class::primaryKey());
            foreach ($relation->link as $from => $to) {
                $linkFilter[$from] = $model->$to;
                if (isset($columns[$from])) {
                    unset($columns[$from]);
                }
            }
            $values = [];
            if (!empty($columns)) {
                $columns = array_keys($columns);
                Yii::trace('primary columns = ', __CLASS__);
                foreach ($children as $child) {
                    $value = [];
                    foreach ($columns as $column) {
                        $value[$column] = $child[$column];
                    }
                    $values[] = $value;
                }
                if (!empty($values)) {
                    $class::deleteAll(['and', $linkFilter, ['in', $columns, $values]]);
                }
            } else {
                $class::deleteAll($linkFilter);
            }

            foreach ($modelDetails as $index => $detail) {
                if (isset($options['beforeSave'])) {
                    call_user_func($options['beforeSave'], $detail, $index);
                }
                $detail->save(false);
                if (isset($options['afterSave'])) {
                    call_user_func($options['afterSave'], $detail, $index);
                }
            }
        }
        if($indexBy !== false){
            $relation->indexBy($oldIndexBy);
        }
        return [!$error,$modelDetails];
    }
}