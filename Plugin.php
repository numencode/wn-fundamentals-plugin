<?php namespace NumenCode\Fundamentals;

use System\Classes\PluginBase;
use NumenCode\Fundamentals\Console\DbPullCommand;
use NumenCode\Fundamentals\Console\DbBackupCommand;
use NumenCode\Fundamentals\Console\MediaPullCommand;
use NumenCode\Fundamentals\Bootstrap\ConfigOverride;
use NumenCode\Fundamentals\Bootstrap\BackendOverride;
use NumenCode\Fundamentals\Extensions\TwigExtensions;
use NumenCode\Fundamentals\Console\MediaBackupCommand;
use NumenCode\Fundamentals\Console\ProjectPullCommand;
use NumenCode\Fundamentals\Console\ProjectBackupCommand;
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

        $this->registerConsoleCommand('numencode.db_pull', DbPullCommand::class);
        $this->registerConsoleCommand('numencode.db_backup', DbBackupCommand::class);
        $this->registerConsoleCommand('numencode.media_pull', MediaPullCommand::class);
        $this->registerConsoleCommand('numencode.media_backup', MediaBackupCommand::class);
        $this->registerConsoleCommand('numencode.project_pull', ProjectPullCommand::class);
        $this->registerConsoleCommand('numencode.project_backup', ProjectBackupCommand::class);
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
