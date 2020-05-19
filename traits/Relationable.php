<?php namespace NumenCode\Fundamentals\Traits;

use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\Validation;
use October\Rain\Exception\ValidationException;
use October\Rain\Exception\ApplicationException;

trait Relationable
{
    protected $repeaterRelations = [];

    protected static $relationableDisabled = [];

    public static function bootRelationable()
    {
        static::saved(function ($model) {
            $model->saveRelationable();
        }, 10);
    }

    public function getAttribute($key)
    {
        if ($relation = $this->getRelationable($key)) {
            return $this->$relation->toArray();
        }

        return parent::getAttribute($key);
    }

    public function toArray()
    {
        $array = parent::toArray();

        if (!empty(static::$relationableDisabled[get_class($this)])) {
            return $array;
        }

        foreach ($this->relationable as $field => $relation) {
            $array[$field] = $this->$relation ? $this->$relation->toArray() : [];
        }

        return $array;
    }

    public function setAttribute($key, $value)
    {
        if ($relation = $this->getRelationable($key)) {
            $this->repeaterRelations[$relation] = is_array($value) ? $value : [];

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function getRelationable($key)
    {
        if (!isset($this->relationable) || !is_array($this->relationable)) {
            throw new ApplicationException('Missing $relationable property');
        }

        foreach ($this->relationable as $field => $relation) {
            if ($key == $field) {
                return $relation;
            }
        }

        return false;
    }

    public function afterValidate()
    {
        foreach ($this->relationable as $field => $relation) {

            if (!isset($this->repeaterRelations[$relation])) {
                continue;
            }

            // Model validation
            foreach ($this->repeaterRelations[$relation] as $data) {
                $model = $this->$relation()->getRelated()->fill($data);

                if (!in_array(Validation::class, class_uses($model))) {
                    continue;
                }

                if (!$model->validate()) {
                    throw new ValidationException($model->errors()->first()->toArray());
                }
            }

            // Custom validation methods
            $validationMethod = 'validate' . camel_case($field);

            if (method_exists($this, $validationMethod)) {
                if ($error = $this->$validationMethod($this->repeaterRelations[$relation])) {
                    throw new ValidationException([$field => $error]);
                }
            }
        }
    }

    public function saveRelationable()
    {
        foreach ($this->repeaterRelations as $relation => $data) {
            $order = 1;
            $key = $this->$relation()->getModel()->getKeyName();
            $qualifiedKey = $this->$relation()->getModel()->getQualifiedKeyName();
            $relatedIds = $this->$relation()->lists($key, $key);

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
        if (!$model = $this->$relation()->find($id)) {
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
        $model = $this->$relation()->getRelated()->fill($data);

        if (isset($data['RLTranslate'])) {
            $this->setTranslations($model, $data['RLTranslate']);
        }

        $this->$relation()->save($model);

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
        $this->$relation()->whereIn($key, $relatedIds)->get()->each(function ($model) {
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

    public static function disableRelationable()
    {
        static::$relationableDisabled[get_called_class()] = true;
    }

    public function __isset($key)
    {
        if ($relation = $this->getRelationable($key)) {
            return true;
        }

        return parent::__isset($key);
    }
}
