<?php namespace NumenCode\Fundamentals\Console;

use Illuminate\Support\Facades\Storage;
use NumenCode\Fundamentals\Traits\ProgressBar;

class MediaPullCommand extends RemoteCommand
{
    use ProgressBar;

    protected $signature = 'media:pull
        {server : The name of the remote server}
        {cloud? : The name of the cloud storage (default: dropbox)}
        {--x|--sudo : Force super user (sudo)}';

    protected $description = 'Download media from cloud to the local storage.';

    protected $sudo;

    public function handle()
    {
        if (!$this->sshConnect()) {
            return;
        }

        if ($this->option('sudo')) {
            $this->sudo = 'sudo ';
        }

        $cloud = $this->argument('cloud') ?: 'dropbox';

        $result = $this->sshRunAndPrint([$this->sudo . 'php artisan media:cloud ' . $cloud]);

        if (!str_contains($result, 'files successfully uploaded')) {
            $this->error('An error occurred while uploading files to the cloud storage.');

            return false;
        }

        $localStorage = Storage::disk('local');
        $cloudStorage = Storage::disk($this->argument('cloud') ?: 'dropbox');
        $files = $cloudStorage->allFiles();
        $bar = 1;

        $this->info('Downloading ' . count($files) . ' files from the cloud storage...');

        foreach ($files as $file) {
            $this->progressBar($bar, count($files));
            $bar++;

            if ($localStorage->exists($file) && ($localStorage->size($file) == $cloudStorage->size($file))) {
                continue;
            }

            $localStorage->put($file, $localStorage->get($file));
        }

        $this->info('All files successfully downloaded to the local storage.');
    }
}
