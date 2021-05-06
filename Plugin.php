<?php namespace NumenCode\Fundamentals;

use ReflectionProperty;
use System\Classes\PluginBase;
use Winter\Translate\Classes\EventRegistry;
use NumenCode\Fundamentals\Console\DbPullCommand;
use NumenCode\Fundamentals\Console\DbBackupCommand;
use NumenCode\Fundamentals\Bootstrap\ConfigOverride;
use NumenCode\Fundamentals\Console\MediaPullCommand;
use NumenCode\Fundamentals\Extensions\TwigExtension;
use NumenCode\Fundamentals\Bootstrap\BackendOverride;
use NumenCode\Fundamentals\Console\MediaBackupCommand;
use NumenCode\Fundamentals\Console\ProjectPullCommand;
use NumenCode\Fundamentals\Console\ProjectBackupCommand;
use NumenCode\Fundamentals\Console\ProjectCommitCommand;
use NumenCode\Fundamentals\Console\ProjectDeployCommand;
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
        $this->registerConsoleCommands();
        $this->registerTranslatable();
    }

    protected function registerHelpers()
    {
        require_once __DIR__ . '/helpers.php';
    }

    protected function registerConsoleCommands()
    {
        $this->registerConsoleCommand('numencode.db_pull', DbPullCommand::class);
        $this->registerConsoleCommand('numencode.db_backup', DbBackupCommand::class);
        $this->registerConsoleCommand('numencode.media_pull', MediaPullCommand::class);
        $this->registerConsoleCommand('numencode.media_backup', MediaBackupCommand::class);
        $this->registerConsoleCommand('numencode.project_pull', ProjectPullCommand::class);
        $this->registerConsoleCommand('numencode.project_backup', ProjectBackupCommand::class);
        $this->registerConsoleCommand('numencode.project_commit', ProjectCommitCommand::class);
        $this->registerConsoleCommand('numencode.project_deploy', ProjectDeployCommand::class);
    }

    protected function registerTranslatable()
    {
        if (plugin_exists('Winter.Translate')) {
            $reflection = new ReflectionProperty(EventRegistry::class, 'instance');
            $reflection->setAccessible(true);
            $reflection->setValue(null, EventRegistryExtension::instance());
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
