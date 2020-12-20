<?php namespace NumenCode\Fundamentals\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DataBackupCommand extends Command
{
    protected $signature = 'data:backup
        {cloud? : The name of the cloud storage (default: dropbox)}';

    protected $description = 'Create database backup and upload it to the cloud storage.';

    const BACKUP_DIRECTORY = 'database';

    public function handle()
    {
        $connection = config('database.default');
        $dbUser = config("database.connections.{$connection}.username");
        $dbPass = config("database.connections.{$connection}.password");
        $dbName = config("database.connections.{$connection}.database");

        $this->line('');

        $this->question('Creating database dump file...');

        $backupName = Carbon::now()->format('Y-m-d_H-i-s') . '.sql.gz';

        $this->info(shell_exec("mysqldump -u{$dbUser} -p{$dbPass} {$dbName} | gzip > {$backupName}"));
        $this->info('Database dump file created.' . PHP_EOL);

        if ($this->argument('cloud')) {
            $cloudStorage = Storage::disk($this->argument('cloud'));

            if (!$cloudStorage->exists(static::BACKUP_DIRECTORY)) {
                $cloudStorage->makeDirectory(static::BACKUP_DIRECTORY);
            }

            $this->question('Uploading database backup to the cloud storage...');

            $cloudStorage->put(static::BACKUP_DIRECTORY . '/' . $backupName, $backupName);

            $this->question('Deleting the database dump file...');
            $this->info(shell_exec("rm -f {$backupName}"));
        }

        $this->alert('Database backup was successfully created.');
    }
}
