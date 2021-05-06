<?php namespace NumenCode\Fundamentals\Extensions;

use Winter\Translate\Classes\EventRegistry;

class EventRegistryExtension extends EventRegistry
{
    public function registerModelTranslation($widget)
    {
        $nestedStatus = $widget->isNested;

        $widget->isNested = false;

        parent::registerModelTranslation($widget);

        $widget->isNested = $nestedStatus;
    }
}
