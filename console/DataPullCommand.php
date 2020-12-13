<?php namespace NumenCode\Fundamentals\Console;

class DataPullCommand extends RemoteCommand
{
    protected $signature = 'data:pull {server : The name of the remote server}';

    protected $description = 'Create MySQL dump on a remote server and restore it on local server.';

    protected $server = null;

    protected $connection = null;

    public function handle()
    {
        if (!$this->sshConnect()) {
            return;
        }

        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbName = config('database.connections.mysql.database');

        $remoteDbName = $this->server['database']['name'];
        $remoteDbUser = $this->server['database']['username'];
        $remoteDbPass = $this->server['database']['password'];
        $remoteDbTables = implode(' ', $this->server['database']['tables']);

        $this->info('CREATING MySQL DUMP:');
        $this->sshRunAndPrint([
            "mysqldump -u{$remoteDbUser} -p{$remoteDbPass} --no-create-info --replace {$remoteDbName} {$remoteDbTables} > database.sql",
        ]);

        $this->info('COMMITTING THE CHANGES:');
        $this->sshRunAndPrint([
            'git add database.sql',
            'git commit -m "Database dump"',
            'git push origin ' . $this->server['branch'],
        ]);

        $this->info(shell_exec('git fetch'));
        $this->info(shell_exec('git merge origin/' . $this->server['branch']));

        $this->info(shell_exec("mysql -u{$dbUser} -p{$dbPass} {$dbName} < database.sql"));

        $this->info('ALL DONE!');
    }
}
