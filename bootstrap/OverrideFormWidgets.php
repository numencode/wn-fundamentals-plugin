<?php namespace NumenCode\Fundamentals\Bootstrap;

use App;
use Backend\Classes\WidgetManager;
use NumenCode\Fundamentals\FormWidgets\Repeater;

class OverrideFormWidgets
{
    public function init()
    {
        if (!App::runningInBackend()) {
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
