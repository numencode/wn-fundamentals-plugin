<?php namespace NumenCode\Fundamentals;

use System\Classes\PluginBase;
use NumenCode\Fundamentals\Console\DataPullCommand;
use NumenCode\Fundamentals\Console\MediaPullCommand;
use NumenCode\Fundamentals\Bootstrap\ConfigOverride;
use NumenCode\Fundamentals\Console\DataBackupCommand;
use NumenCode\Fundamentals\Console\MediaCloudCommand;
use NumenCode\Fundamentals\Bootstrap\BackendOverride;
use NumenCode\Fundamentals\Extensions\TwigExtensions;
use NumenCode\Fundamentals\Console\ProjectPullCommand;
use NumenCode\Fundamentals\Console\ProjectDeployCommand;

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

        $this->registerConsoleCommand('numencode.data_backup', DataBackupCommand::class);
        $this->registerConsoleCommand('numencode.data_pull', DataPullCommand::class);
        $this->registerConsoleCommand('numencode.media_pull', MediaPullCommand::class);
        $this->registerConsoleCommand('numencode.media_cloud', MediaCloudCommand::class);
        $this->registerConsoleCommand('numencode.project_pull', ProjectPullCommand::class);
        $this->registerConsoleCommand('numencode.project_deploy', ProjectDeployCommand::class);
    }

    public function registerMarkupTags()
    {
        return [
            'filters'   => TwigExtensions::filters(),
            'functions' => TwigExtensions::functions(),
        ];
    }
}
