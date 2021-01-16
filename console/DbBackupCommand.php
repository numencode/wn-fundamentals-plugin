<?php namespace NumenCode\Fundamentals\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DbBackupCommand extends Command
{
    protected $signature = 'db:backup
        {cloud? : The name of the cloud storage to upload the dump file}
        {folder? : The name of the folder on the cloud storage (default: database)}
        {--d|--nodelete : Do not delete the dump file after it\'s uploaded to the cloud storage}';

    protected $description = 'Create database dump and optionally upload it to the cloud storage.';

    public function handle()
    {
        $cloudStorageFolder = ($this->argument('folder') ?: 'database') . DIRECTORY_SEPARATOR;

        $connection = config('database.default');
        $dbUser = config("database.connections.{$connection}.username");
        $dbPass = config("database.connections.{$connection}.password");
        $dbName = config("database.connections.{$connection}.database");

        $dumpFileName = Carbon::now()->format('Y-m-d_H-i-s') . '.sql.gz';

        $this->line('');
        $this->question('Creating database dump file...');
        $this->info(shell_exec("mysqldump -u{$dbUser} -p{$dbPass} {$dbName} | gzip > {$dumpFileName}"));
        $this->info('Database dump file successfully created.');
        $this->line('');

        if ($this->argument('cloud')) {
            $cloudStorage = Storage::disk($this->argument('cloud'));

            $this->question('Uploading database dump file to the cloud storage...');
            $cloudStorage->put($cloudStorageFolder . $dumpFileName, file_get_contents($dumpFileName));
            $this->info('Database dump file successfully uploaded.');
            $this->line('');

            if (!$this->option('nodelete')) {
                $this->question('Deleting the database dump file...');
                $this->info(shell_exec("rm -f {$dumpFileName}"));
                $this->info('Database dump file successfully deleted.');
                $this->line('');
            }
        }

        $this->alert('Database backup was successfully created.');
    }
}
