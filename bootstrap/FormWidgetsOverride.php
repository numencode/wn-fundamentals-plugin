<?php namespace NumenCode\Fundamentals\Bootstrap;

use Backend\Classes\WidgetManager;
use NumenCode\Fundamentals\FormWidgets\Repeater;

class FormWidgetsOverride
{
    public function init()
    {
        if (!app()->runningInBackend()) {
            return;
        }

        $this->extendRepeater();
    }

    protected function extendRepeater()
    {
        WidgetManager::instance()->registerFormWidget(Repeater::class, [
            'label' => 'Repeater',
            'code'  => 'repeater',
        ]);
    }
}
