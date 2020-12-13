<?php namespace NumenCode\Fundamentals\Console;

class ProjectPullCommand extends RemoteCommand
{
    protected $signature = 'project:pull
        {server : The name of the remote server}
        {--p|--pull : Execute git pull command before git push}
        {--m|--nomerge : Do not merge changes automatically}';

    protected $description = 'Pull changes into the project from a remote server.';

    protected $server = null;

    protected $connection = null;

    public function handle()
    {
        if (!$this->sshConnect()) {
            return;
        }

        if ($this->checkForChanges()) {
            $this->info('NO CHANGES ON A REMOTE SERVER.');

            return;
        }

        $this->info('COMMITTING THE CHANGES:');
        $this->sshRunAndPrint([
            'git add --all',
            'git commit -m "Server changes"',
        ]);

        if ($this->option('pull')) {
            $this->info('PULLING NEW CHANGES:');
            $this->sshRunAndPrint([
                'git pull',
            ]);
        }

        $this->info('PUSHING THE CHANGES:');
        $this->sshRunAndPrint([
            'git push origin ' . $this->server['branch'],
        ]);

        if (!$this->option('nomerge')) {
            $this->info('MERGING THE CHANGES:');
            $this->info(shell_exec('git fetch'));
            $this->info(shell_exec('git merge origin/' . $this->server['branch']));
        }

        $this->info('ALL DONE!');
    }
}
