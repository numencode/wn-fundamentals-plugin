<?php namespace NumenCode\Fundamentals;

use System\Classes\PluginBase;
use NumenCode\Fundamentals\Bootstrap\ConfigOverride;
use NumenCode\Fundamentals\Bootstrap\BackendOverride;
use NumenCode\Fundamentals\Extensions\TwigExtensions;

class Plugin extends PluginBase
{
    public function boot()
    {
        (new ConfigOverride())->init();
        (new BackendOverride())->init();
    }

    public function register()
    {
        require_once __DIR__ . '/helpers.php';
    }

    public function registerMarkupTags()
    {
        return [
            'filters'   => TwigExtensions::filters(),
            'functions' => TwigExtensions::functions(),
        ];
    }
}
