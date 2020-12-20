<?php namespace NumenCode\Fundamentals\Console;

class DbPullCommand extends RemoteCommand
{
    protected $signature = 'db:pull
        {server : The name of the remote server}
        {--m|--noimport : Do not import data automatically}';

    protected $description = 'Create database dump on a remote server and import it on a local environment.';

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
        $this->line('');
        $this->info('Database dump file created.');
        $this->line('');

        $this->question('Transferring database dump file from the remote server...');
        $this->info(shell_exec("scp {$remoteUser}@{$remoteHost}:{$remotePath}/database.sql database.sql"));
        $this->info('Database dump file transferred successfully.');
        $this->line('');

        if (!$this->option('noimport')) {
            $this->question('Importing data...');
            $this->info(shell_exec("mysql -u{$dbUser} -p{$dbPass} {$dbName} < database.sql"));
            $this->info('Data imported successfully.');
            $this->line('');
        }

        $this->question('Cleaning the database dump files...');
        $this->sshRun(['rm -f database.sql']);

        if (!$this->option('noimport')) {
            $this->info(shell_exec('rm -f database.sql'));
        }

        $this->info('Cleanup completed successfully.');
        $this->line('');

        $this->alert('Database was successfully updated.');
    }
}
