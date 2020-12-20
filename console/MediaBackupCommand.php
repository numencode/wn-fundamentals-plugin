<?php namespace NumenCode\Fundamentals\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use NumenCode\Fundamentals\Traits\ProgressBar;

class MediaBackupCommand extends Command
{
    use ProgressBar;

    protected $signature = 'media:backup {cloud : The name of the cloud storage}';

    protected $description = 'Backup (upload) media files to the cloud storage.';

    public function handle()
    {
        $cloudStorage = Storage::disk($this->argument('cloud'));

        $files = array_filter(Storage::allFiles(), function ($file) {
            return basename($file) != '.gitignore' && !stristr($file, '/thumb/');
        });

        $bar = 1;

        $this->line('');
        $this->question('Uploading ' . count($files) . ' files to the cloud storage...');

        foreach ($files as $file) {
            $this->progressBar($bar, count($files));
            $bar++;

            if ($cloudStorage->exists($file) && ($cloudStorage->size($file) == Storage::size($file))) {
                continue;
            }

            $cloudStorage->put($file, Storage::get($file));
        }

        $this->alert('All files successfully uploaded to the cloud storage.');
    }
}
