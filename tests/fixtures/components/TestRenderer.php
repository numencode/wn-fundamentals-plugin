<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Tests\Fixtures\Components;

use Cms\Classes\ComponentBase;
use NumenCode\Fundamentals\Traits\ComponentRenderer;

/**
 * Component for the ComponentRenderer tests. Its own partials: default, plain (and _hidden).
 */
class TestRenderer extends ComponentBase
{
    use ComponentRenderer;

    public bool $initialized = false;

    public string $greeting = '';

    public function componentDetails()
    {
        return ['name' => 'Test renderer', 'description' => 'ComponentRenderer test component'];
    }

    public function defineProperties()
    {
        return [
            'layout' => ['title' => 'Layout', 'type' => 'dropdown', 'default' => '@default'],
        ];
    }

    /**
     * Components using the trait can have their own init().
     */
    public function init()
    {
        $this->initialized = true;
    }

    public function onRun()
    {
        $this->greeting = 'Hello';
    }
}
