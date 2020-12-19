<?php namespace NumenCode\Fundamentals\Console;

class ProjectDeployCommand extends RemoteCommand
{
    protected $signature = 'project:deploy
        {server : The name of the remote server}
        {--f|--fast : Fast deploy (without clearing the cache)}
        {--c|--composer : Force Composer install}
        {--m|--migrate : Run migrations}
        {--x|--sudo : Force super user (sudo)}';

    protected $description = 'Deploy project to a remote server.';

    protected $sudo;

    public function handle()
    {
        if (!$this->sshConnect()) {
            return;
        }

        if (!$this->checkForChanges(true)) {
            return;
        }

        if ($this->option('sudo')) {
            $this->sudo = 'sudo ';
        }

        $success = $this->option('fast') ? $this->fastDeploy() : $this->deploy();

        $this->takeOwnership();

        if (!$success) {
            $this->error('PROJECT DEPLOY FAILED');
        } else {
            $this->info('PROJECT DEPLOY SUCCESSFUL');
        }
    }

    protected function deploy()
    {
        $this->info('PUTTING THE APPLICATION INTO MAINTENANCE MODE:');
        $this->sshRunAndPrint([$this->sudo . 'php artisan down']);

        sleep(1);

        $this->info('FLUSHING THE APPLICATION CACHE:');
        $this->sshRunAndPrint($this->clearCommands());

        $success = $this->fastDeploy();

        $this->info('REBUILDING THE APPLICATION CACHE:');
        $this->sshRunAndPrint($this->clearCommands());

        $this->info('BRINGING THE APPLICATION OUT OF MAINTENANCE MODE:');
        $this->sshRunAndPrint([$this->sudo . 'php artisan up']);

        return $success;
    }

    protected function fastDeploy()
    {
        if (!empty($this->server['permissions']['root_user'])) {
            $this->info('TAKING OWNERSHIP:');
            $this->sshRunAndPrint([$this->sudo . 'chown ' . $this->server['permissions']['root_user'] . ' -R .']);
        }

        if (array_get($this->server, 'master_branch', 'master') === false) {
            return $this->pullDeploy();
        } else {
            return $this->mergeDeploy();
        }
    }

    public function pullDeploy()
    {
        $this->info('DEPLOYING THE PROJECT (PULL):');

        $result = $this->sshRunAndPrint(['git pull']);

        if (str_contains($result, 'CONFLICT')) {
            $this->error('Conflicts detected. Reverting...');
            $this->sshRunAndPrint(['git reset --hard']);

            return false;
        }

        $this->afterDeploy($result);

        return true;
    }

    public function mergeDeploy()
    {
        $this->info('DEPLOYING THE PROJECT (MERGE):');

        $result = $this->sshRunAndPrint([
            'git fetch',
            'git merge origin/' . array_get($this->server, 'master_branch', 'master'),
        ]);

        if (str_contains($result, 'CONFLICT')) {
            $this->error('Conflicts detected. Reverting...');
            $this->sshRunAndPrint(['git reset --hard']);

            return false;
        }

        $this->sshRunAndPrint(['git push origin ' . $this->server['branch']]);

        $this->afterDeploy($result);

        return true;
    }

    public function afterDeploy($result)
    {
        if ($this->option('composer') || str_contains($result, 'composer.lock')) {
            $this->sshRunAndPrint($this->composerCommands());
        }

        if ($this->option('migrate')) {
            $this->sshRunAndPrint($this->migrateCommands());
        }

        $this->takeOwnership();
    }

    protected function takeOwnership()
    {
        if (empty($this->server['permissions']['www_user']) || empty($this->server['permissions']['www_folders'])) {
            return;
        }

        $folders = explode(',', $this->server['permissions']['www_folders']);

        $this->info('DISTRIBUTING OWNERSHIP:');

        foreach ($folders as $folder) {
            $this->sshRunAndPrint([$this->sudo . 'sudo chown ' . $this->server['permissions']['www_user'] . ' ' . $folder . ' -R']);
        }
    }

    protected function clearCommands()
    {
        return [
            $this->sudo . 'php artisan route:clear',
            $this->sudo . 'php artisan config:clear',
            $this->sudo . 'php artisan cache:clear',
        ];
    }

    protected function migrateCommands()
    {
        return [
            $this->sudo . 'php artisan october:up',
        ];
    }

    protected function composerCommands()
    {
        return [
            'composer install --no-dev',
        ];
    }
}
