<?php namespace NumenCode\Fundamentals\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ProjectBackupCommand extends Command
{
    protected $signature = 'project:backup
        {cloud? : The name of the cloud storage where backup will be uploaded}
        {--d|--nodelete : Do not delete the backup file after it is uploaded to the cloud storage}';

    protected $description = 'Create all project files backup and optionally upload it to the cloud storage.';

    const BACKUP_DIRECTORY = 'files';

    public function handle()
    {
        $backupName = Carbon::now()->format('Y-m-d_H-i-s') . '.tar.gz';

        $this->line('');
        $this->question('Creating project backup package...');
        $this->info(shell_exec('tar -pczf ' . $backupName . ' --exclude "vendor" .'));
        $this->info('Project backup package successfully created.');
        $this->line('');

        if ($this->argument('cloud')) {
            $cloudStorage = Storage::disk($this->argument('cloud') ?: 'dropbox');

            $this->question('Uploading project backup package to the cloud storage...');
            $cloudStorage->put(static::BACKUP_DIRECTORY . '/' . $backupName, file_get_contents($backupName));
            $this->info('Project backup package successfully uploaded.');
            $this->line('');

            if (!$this->option('nodelete')) {
                $this->question('Deleting the project backup package...');
                $this->info(shell_exec("rm -f {$backupName}"));
                $this->info('Project backup package successfully deleted.');
                $this->line('');
            }
        }

        $this->alert('Project backup was successfully created.');
    }
}
