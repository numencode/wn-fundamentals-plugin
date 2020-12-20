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

        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbName = config('database.connections.mysql.database');

        $remoteUser = $this->server['username'];
        $remoteHost = $this->server['host'];
        $remotePath = $this->server['path'];

        $remoteDbName = $this->server['database']['name'];
        $remoteDbUser = $this->server['database']['username'];
        $remoteDbPass = $this->server['database']['password'];
        $remoteDbTables = implode(' ', $this->server['database']['tables']);

        $this->info('- PROCESS STARTED -' . PHP_EOL);

        $this->info('Creating database dump file...');
        $this->sshRun(["mysqldump -u{$remoteDbUser} -p{$remoteDbPass} --no-create-info --replace {$remoteDbName} {$remoteDbTables} > database.sql"]);
        $this->info('Database dump file created.' . PHP_EOL);

        $this->info('Transferring database from the remote server...');
        $this->info(shell_exec("scp {$remoteUser}@{$remoteHost}:{$remotePath}/database.sql database.sql"));
        $this->info('Database transferred successfully.' . PHP_EOL);

        if (!$this->option('noimport')) {
            $this->info('Importing data...');
            $this->info(shell_exec("mysql -u{$dbUser} -p{$dbPass} {$dbName} < database.sql"));
            $this->info('Data imported successfully.' . PHP_EOL);
        }

        $this->info('Cleaning the database dump files...');
        $this->sshRun(['rm -f database.sql']);

        if (!$this->option('noimport')) {
            $this->info(shell_exec('rm -f database.sql'));
        }

        $this->info('Cleanup completed successfully.' . PHP_EOL);

        $this->info('- PROCESS COMPLETED -');
    }
}
