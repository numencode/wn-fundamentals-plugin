<?php namespace NumenCode\Fundamentals;

use System\Classes\PluginBase;
use Winter\Translate\Classes\EventRegistry;
use NumenCode\Fundamentals\Bootstrap\ConfigOverride;
use NumenCode\Fundamentals\Extensions\TwigExtension;
use NumenCode\Fundamentals\Bootstrap\BackendOverride;
use NumenCode\Fundamentals\Bootstrap\OverrideFormWidgets;
use NumenCode\Fundamentals\Extensions\EventRegistryExtension;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'numencode.fundamentals::lang.plugin.name',
            'description' => 'numencode.fundamentals::lang.plugin.description',
            'author'      => 'Blaz Orazem',
            'icon'        => 'oc-icon-cogs',
            'homepage'    => 'https://github.com/numencode/fundamentals-plugin',
        ];
    }

    public function boot()
    {
        (new ConfigOverride())->init();
        (new BackendOverride())->init();
    }

    public function register()
    {
        $this->registerHelpers();
        $this->registerTranslatable();
    }

    protected function registerHelpers()
    {
        require_once __DIR__ . '/helpers.php';
    }

    protected function registerTranslatable()
    {
        // Override event registry to enable repeater translations
        if (plugin_exists('Winter.Translate')) {
            app()->singleton(EventRegistry::class, function () {
                return EventRegistryExtension::instance();
            });
        }
    }

    public function registerMarkupTags()
    {
        return [
            'filters'   => TwigExtension::filters(),
            'functions' => TwigExtension::functions(),
        ];
    }

    public function registerFormWidgets()
    {
        (new OverrideFormWidgets())->init();

        return [
            'NumenCode\Fundamentals\FormWidgets\TranslatableHelper' => [
                'label' => 'numencode.fundamentals::lang.form.translatable',
                'code'  => 'translatable',
            ],
        ];
    }
}
