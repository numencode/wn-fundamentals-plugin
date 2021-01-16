<?php namespace NumenCode\Fundamentals\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use NumenCode\Fundamentals\Traits\ProgressBar;

class MediaBackupCommand extends Command
{
    use ProgressBar;

    protected $signature = 'media:backup
        {cloud : The name of the cloud storage}
        {folder? : The name of the folder on the cloud storage (default: storage)}';

    protected $description = 'Backup (upload) media files to the cloud storage.';

    public function handle()
    {
        $cloudStorage = Storage::disk($this->argument('cloud'));
        $cloudStorageFolder = ($this->argument('folder') ?: 'storage') . '/';

        $files = array_filter(Storage::allFiles(), function ($file) {
            return basename($file) != '.gitignore' && !stristr($file, '/thumb/');
        });

        $this->line('');
        $this->question('Uploading ' . count($files) . ' files to the cloud storage...');

        $bar = 1;

        foreach ($files as $file) {
            $this->progressBar($bar, count($files));
            $bar++;

            $storageFile = $cloudStorageFolder . $file;

            if ($cloudStorage->exists($storageFile) && ($cloudStorage->size($storageFile) == Storage::size($file))) {
                continue;
            }

            $cloudStorage->put($storageFile, Storage::get($file));
        }

        $this->alert('All files successfully uploaded to the cloud storage.');
    }
}
