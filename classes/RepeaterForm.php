<?php namespace NumenCode\Fundamentals\Classes;

use Backend\Widgets\Form;

class RepeaterForm extends Form
{
    protected $widgetsWrapped = false;

    public function guessViewPath($suffix = '', $isPublic = false)
    {
        $class = Form::class;

        return $this->guessViewPathFrom($class, $suffix, $isPublic);
    }

    public function guessConfigPath($suffix = '')
    {
        $class = Form::class;

        return $this->guessConfigPathFrom($class, $suffix);
    }

    public function addFields(array $fields, $addToArea = null)
    {
        parent::addFields($fields, $addToArea);

        foreach ($this->allFields as $name => $field) {
            if (!$field instanceof RepeaterFormField) {
                $this->allFields[$name] = new RepeaterFormField($field);
            }
        }
    }

    public function getSaveData()
    {
        if (!$this->widgetsWrapped) {
            $this->wrapWidgets();
            $this->widgetsWrapped = true;
        }

        return parent::getSaveData();
    }

    protected function wrapWidgets()
    {
        $wrapped = [];

        foreach ($this->formWidgets as $key => $widget) {
            $wrapped[$key] = new RepeaterFormWidget($widget);
        }

        $this->formWidgets = $wrapped;
    }
}
