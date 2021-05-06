<?php namespace NumenCode\Fundamentals\Behaviors;

use Winter\Storm\Extension\ExtensionBase;
use Winter\Storm\Database\Traits\Sortable;
use Winter\Storm\Database\Traits\Validation;
use Winter\Storm\Exception\ValidationException;

class RelationableModel extends ExtensionBase
{
    protected $model;

    protected $relationable = [];

    protected $repeaterRelations = [];

    public function __construct($model)
    {
        $this->model = $model;

        if (isset($this->model->relationable) && is_array($this->model->relationable)) {
            $this->relationable = $this->model->relationable;
        } elseif (isset($this->model->getDynamicProperties()['relationable'])) {
            $this->relationable = $this->model->getDynamicProperties()['relationable'];
        }

        foreach ($this->relationable as $title => $attribute) {
            $this->model->addDynamicMethod('get' . ucfirst(camel_case($title)) . 'Attribute', function () use ($title) {
                if ($relation = $this->getRelationable($title)) {
                    return $this->model->$relation->toArray();
                }
            });

            $this->model->addDynamicMethod('set' . ucfirst(camel_case($title)) . 'Attribute', function ($value) use ($title) {
                if ($relation = $this->getRelationable($title)) {
                    $this->repeaterRelations[$relation] = is_array($value) ? $value : [];
                }
            });
        }

        $this->model->bindEvent('model.afterValidate', function () use ($model) {
            foreach ($this->relationable as $field => $relation) {
                if (!isset($this->repeaterRelations[$relation])) {
                    continue;
                }

                // Model validation
                foreach ($this->repeaterRelations[$relation] as $data) {
                    $model = $this->model->$relation()->getRelated()->fill($data);

                    if (!in_array(Validation::class, class_uses($model))) {
                        continue;
                    }

                    if (!$model->validate()) {
                        throw new ValidationException($model->errors()->first()->toArray());
                    }
                }

                // Custom validation methods
                $validationMethod = 'validate' . ucfirst(camel_case($field));

                if (method_exists($this, $validationMethod)) {
                    if ($error = $this->$validationMethod($this->repeaterRelations[$relation])) {
                        throw new ValidationException([$field => $error]);
                    }
                }
            }
        });

        $this->model->bindEvent('model.afterSave', function () {
            $this->saveRelationable();
        });
    }

    public function toArray()
    {
        $array = [];

        foreach ($this->relationable as $field => $relation) {
            $array[$field] = $this->model->$relation ? $this->model->$relation->toArray() : [];
        }

        return $array;
    }

    public function getRelationable($key)
    {
        foreach ($this->relationable as $field => $relation) {
            if ($key == $field) {
                return $relation;
            }
        }

        return false;
    }

    public function saveRelationable()
    {
        foreach ($this->repeaterRelations as $relation => $data) {
            $order = 1;
            $key = $this->model->$relation()->getModel()->getKeyName();
            $qualifiedKey = $this->model->$relation()->getModel()->getQualifiedKeyName();
            $relatedIds = $this->model->$relation()->lists($key, $key);

            foreach ($data as $row) {
                $id = !empty($row[$key]) ? $row[$key] : null;

                if (isset($relatedIds[$id])) {
                    $instance = $this->updateRelationable($id, $relation, $row);

                    unset($relatedIds[$id]);
                } else {
                    $instance = $this->createRelationable($relation, $row);
                }

                $this->setRelationableOrder($instance, $order++);
            }

            if (!empty($relatedIds)) {
                $this->deleteRelationable($qualifiedKey, $relatedIds, $relation);
            }
        }

        $this->repeaterRelations = [];
    }

    protected function updateRelationable($id, $relation, $data)
    {
        if (!$model = $this->model->$relation()->find($id)) {
            return;
        }

        $model->fill($data);

        if (isset($data['RLTranslate'])) {
            $this->setTranslations($model, $data['RLTranslate']);
        }

        $model->save();

        return $model;
    }

    protected function createRelationable($relation, $data)
    {
        $model = $this->model->$relation()->getRelated()->fill($data);

        if (isset($data['RLTranslate'])) {
            $this->setTranslations($model, $data['RLTranslate']);
        }

        $this->model->$relation()->save($model);

        return $model;
    }

    protected function setTranslations($model, $translations)
    {
        $translatableAttributes = $model->getTranslatableAttributes();

        foreach ($translations as $locale => $data) {
            foreach ($data as $attribute => $value) {
                if (in_array($attribute, $translatableAttributes)) {
                    $model->setAttributeTranslated($attribute, $value, $locale);
                }
            }
        }
    }

    protected function deleteRelationable($key, $relatedIds, $relation)
    {
        $this->model->$relation()->whereIn($key, $relatedIds)->get()->each(function ($model) {
            $model->delete();
        });
    }

    protected function setRelationableOrder($instance, $order)
    {
        if ($instance && in_array(Sortable::class, class_uses($instance))) {
            $sortOrder = $instance->getSortOrderColumn();

            $instance->$sortOrder = $order;
            $instance->save();
        }
    }
}
