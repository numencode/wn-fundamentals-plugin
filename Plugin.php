<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals;

use Detection\MobileDetect;
use NumenCode\Fundamentals\Bootstrap\BackendOverride;
use NumenCode\Fundamentals\Bootstrap\ConfigOverride;
use NumenCode\Fundamentals\Bootstrap\TranslateIntegration;
use NumenCode\Fundamentals\Extensions\TwigExtension;
use NumenCode\Fundamentals\FormWidgets\RelationRepeater;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function pluginDetails(): array
    {
        return [
            'name'        => 'numencode.fundamentals::lang.plugin.name',
            'description' => 'numencode.fundamentals::lang.plugin.description',
            'author'      => 'Blaz Orazem',
            'icon'        => 'icon-cogs',
            'homepage'    => 'https://www.numencode.com',
        ];
    }

    public function register(): void
    {
        require_once __DIR__ . '/helpers.php';

        // One user-agent detector per request, shared by detect() and device_type()
        $this->app->singleton(MobileDetect::class, fn () => new MobileDetect());
    }

    public function boot(): void
    {
        (new ConfigOverride())->init();
        (new BackendOverride())->init();
        (new TranslateIntegration())->init();
    }

    public function registerMarkupTags(): array
    {
        return [
            'filters'   => TwigExtension::filters(),
            'functions' => TwigExtension::functions(),
        ];
    }

    public function registerFormWidgets(): array
    {
        return [
            RelationRepeater::class => [
                'label' => 'numencode.fundamentals::lang.formwidget.relationrepeater',
                'code'  => 'relationrepeater',
            ],
        ];
    }
}
