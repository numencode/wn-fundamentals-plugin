<?php namespace NumenCode\Fundamentals\Console;

class DataPullCommand extends RemoteCommand
{
    protected $signature = 'data:pull
        {server : The name of the remote server}
        {--m|--noimport : Do not import data automatically}';

    protected $description = 'Create database dump on a remote server and import it on a local server.';

    public function handle()
    {
        if (!$this->sshConnect()) {
            return;
        }

        $connection = config('database.default');
        $dbUser = config("database.connections.{$connection}.username");
        $dbPass = config("database.connections.{$connection}.password");
        $dbName = config("database.connections.{$connection}.database");

        $remoteUser = $this->server['username'];
        $remoteHost = $this->server['host'];
        $remotePath = $this->server['path'];

        $remoteDbName = $this->server['database']['name'];
        $remoteDbUser = $this->server['database']['username'];
        $remoteDbPass = $this->server['database']['password'];
        $remoteDbTables = implode(' ', $this->server['database']['tables']);

        $this->line('');

        $this->question('Creating database dump file...');
        $this->sshRun(["mysqldump -u{$remoteDbUser} -p{$remoteDbPass} --no-create-info --replace {$remoteDbName} {$remoteDbTables} > database.sql"]);
        $this->info(PHP_EOL . 'Database dump file created.' . PHP_EOL);

        $this->question('Transferring database dump file from the remote server...');
        $this->info(shell_exec("scp {$remoteUser}@{$remoteHost}:{$remotePath}/database.sql database.sql"));
        $this->info('Database dump file transferred successfully.' . PHP_EOL);

        if (!$this->option('noimport')) {
            $this->question('Importing data...');
            $this->info(shell_exec("mysql -u{$dbUser} -p{$dbPass} {$dbName} < database.sql"));
            $this->info('Data imported successfully.' . PHP_EOL);
        }

        $this->question('Cleaning the database dump files...');
        $this->sshRun(['rm -f database.sql']);

        if (!$this->option('noimport')) {
            $this->info(shell_exec('rm -f database.sql'));
        }

        $this->info('Cleanup completed successfully.' . PHP_EOL);

        $this->alert('Database was successfully updated.');
    }
}
