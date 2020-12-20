<?php namespace NumenCode\Fundamentals\Console;

class ProjectPullCommand extends RemoteCommand
{
    protected $signature = 'project:pull
        {server : The name of the remote server}
        {--p|--pull : Execute git pull command before git push}
        {--m|--nomerge : Do not merge changes automatically}';

    protected $description = 'Pull changes into the project from a remote server.';

    public function handle()
    {
        if (!$this->sshConnect()) {
            return;
        }

        $this->line('');

        if ($this->checkForChanges()) {
            $this->alert('No changes on a remote server.');

            return;
        }

        $this->question('Committing the changes:');
        $this->sshRunAndPrint([
            'git add --all',
            'git commit -m "Server changes"',
        ]);

        if ($this->option('pull')) {
            $this->question('Pulling new changes:');
            $this->sshRunAndPrint([
                'git pull',
            ]);
        }

        $this->question('Pushing the changes:');
        $this->sshRunAndPrint([
            'git push origin ' . $this->server['branch'],
        ]);

        if (!$this->option('nomerge')) {
            $this->question('Merging the changes:');
            $this->info(shell_exec('git fetch'));
            $this->info(shell_exec('git merge origin/' . $this->server['branch']));
        }

        $this->alert('Changes were successfully pulled into the project.');
    }
}
