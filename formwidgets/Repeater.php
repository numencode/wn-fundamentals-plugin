<?php namespace NumenCode\Fundamentals\FormWidgets;

use Backend\FormWidgets\Repeater as BaseRepeater;

class Repeater extends BaseRepeater
{
    protected $boundWidgets = [];

    public function guessViewPath(string $suffix = '', bool $isPublic = false): ?string
    {
        $class = BaseRepeater::class;

        return $this->guessViewPathFrom($class, $suffix, $isPublic);
    }

    public function guessConfigPath($suffix = '')
    {
        $class = BaseRepeater::class;

        return $this->guessConfigPathFrom($class, $suffix);
    }

    protected function processItems()
    {
        parent::processItems();

        $this->processItemsOnTheFly();
    }

    public function getSaveValue($value)
    {
        if (!is_array($value) || !$value) {
            return $value;
        }

        $order = array_keys($value);
        $newValue = $this->processSaveValue($value);

        foreach ($newValue as $key => $item) {
            if (isset($order[$key]) && isset($value[$order[$key]]['RLTranslate'])) {
                $newValue[$key]['RLTranslate'] = $value[$order[$key]]['RLTranslate'];
            }
        }

        return $newValue;
    }

    protected function processItemsOnTheFly()
    {
        $requestedAction = request()->header('X-WINTER-REQUEST-HANDLER');
        $alias = $this->alias . 'Form';

        if ($requestedAction && starts_with($requestedAction, $alias)) {
            if ($this->actionIsAlreadyBound($requestedAction)) {
                return true;
            }

            // Determine the index of the required action
            $index = intval(str_replace($alias, '', $requestedAction));

            // And bound the required widget on-the-fly
            $this->makeItemFormWidget($index);
        }
    }

    protected function actionIsAlreadyBound($action)
    {
        foreach ($this->boundWidgets as $widget) {
            if (starts_with($action, $widget)) {
                return true;
            }
        }

        return false;
    }

    protected function makeItemFormWidget($index = 0, $groupCode = null)
    {
        $configDefinition = $this->useGroups ? $this->getGroupFormFieldConfig($groupCode) : $this->form;

        $config = $this->makeConfig($configDefinition);
        $config->model = $this->prepareModel($index);
        $config->data = $this->getValueFromIndex($index);
        $config->alias = $this->alias . 'Form'.$index;
        $config->arrayName = $this->getFieldName().'['.$index.']';
        $config->isNested = true;

        if (self::$onAddItemCalled || $this->minItems > 0) {
            $config->enableDefaults = true;
        }

        $widget = $this->makeWidget('NumenCode\Fundamentals\Classes\RepeaterForm', $config);
        $widget->bindToController();

        $this->indexMeta[$index] = [
            'groupCode' => $groupCode,
        ];

        return $this->formWidgets[$index] = $widget;
    }

    protected function prepareModel($index)
    {
        $name = $this->formField->getName(false);
        $model = $this->model;

        if (in_array('NumenCode.Fundamentals.Behaviors.RelationableModel', $model->implement)) {
            if ($relation = $model->getRelationable($name)) {
                $relationModel = $this->model->$relation->get($index);
                $model = $relationModel ?: $this->model->$relation()->getModel();
            }
        }

        return $model;
    }
}
