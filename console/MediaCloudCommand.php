<?php namespace NumenCode\Fundamentals\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use NumenCode\Fundamentals\Traits\ProgressBar;

class MediaCloudCommand extends Command
{
    use ProgressBar;

    protected $signature = 'media:cloud
        {cloud? : The name of the cloud storage (default: dropbox)}';

    protected $description = 'Upload media files to the cloud storage.';

    public function handle()
    {
        $cloudStorage = Storage::disk($this->argument('cloud') ?: 'dropbox');

        $files = array_filter(Storage::allFiles(), function ($file) {
            return basename($file) != '.gitignore' && !stristr($file, '/thumb/');
        });

        $bar = 1;

        $this->line('');

        $this->info('Uploading ' . count($files) . ' files to the cloud storage...');

        foreach ($files as $file) {
            $this->progressBar($bar, count($files));
            $bar++;

            if ($cloudStorage->exists($file) && ($cloudStorage->size($file) == Storage::size($file))) {
                continue;
            }

            $cloudStorage->put($file, Storage::get($file));
        }

        $this->info('All files successfully uploaded to the cloud storage.');
    }
}
