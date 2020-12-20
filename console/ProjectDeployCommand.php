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

        $this->line('');

        $success = $this->option('fast') ? $this->fastDeploy() : $this->deploy();

        $this->handleOwnership();

        if (!$success) {
            $this->error('Project deployment FAILED. Check error logs to see what went wrong.' . PHP_EOL);
        } else {
            $this->alert('Project was successfully deployed.');
        }
    }

    protected function deploy()
    {
        $this->question('Putting the application into maintenance mode:');
        $this->sshRunAndPrint([$this->sudo . 'php artisan down']);

        sleep(1);

        $this->question('Flushing the application cache:');
        $this->sshRunAndPrint($this->clearCommands());

        $success = $this->fastDeploy();

        $this->question('Rebuilding the application cache:');
        $this->sshRunAndPrint($this->clearCommands());

        $this->question('Bringing the application out of the maintenance mode:');
        $this->sshRunAndPrint([$this->sudo . 'php artisan up']);

        return $success;
    }

    protected function fastDeploy()
    {
        if (!empty($this->server['permissions']['root_user'])) {
            $this->question('Handling file ownership.');
            $this->sshRunAndPrint([$this->sudo . 'chown ' . $this->server['permissions']['root_user'] . ' -R .']);
            $this->line('');
        }

        if (array_get($this->server, 'master_branch', 'master') === false) {
            return $this->pullDeploy();
        } else {
            return $this->mergeDeploy();
        }
    }

    public function pullDeploy()
    {
        $this->question('Deploying the project (pull mode):');

        $result = $this->sshRunAndPrint(['git pull']);

        if (str_contains($result, 'CONFLICT')) {
            $this->error('Conflicts detected. Reverting changes...');
            $this->sshRunAndPrint(['git reset --hard']);

            return false;
        }

        $this->afterDeploy($result);

        return true;
    }

    public function mergeDeploy()
    {
        $this->question('Deploying the project (merge mode):');

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

        $this->handleOwnership();
    }

    protected function handleOwnership()
    {
        if (empty($this->server['permissions']['www_user']) || empty($this->server['permissions']['www_folders'])) {
            return;
        }

        $folders = explode(',', $this->server['permissions']['www_folders']);

        $this->question('Handling file ownership.');
        $this->line('');

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
