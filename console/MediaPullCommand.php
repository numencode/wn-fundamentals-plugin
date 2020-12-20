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

    protected $description = 'Download media files from the cloud storage to the local storage.';

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

        $result = $this->sshRunAndPrint([$this->sudo . 'php artisan media:backup ' . $cloud]);

        if (!str_contains($result, 'files successfully uploaded')) {
            $this->error('An error occurred while uploading files to the cloud storage.');

            return false;
        }

        $localStorage = Storage::disk('local');
        $cloudStorage = Storage::disk($cloud);
        $files = $cloudStorage->allFiles();
        $bar = 1;

        $this->line('');

        $this->question('Downloading ' . count($files) . ' files from the cloud storage...');

        foreach ($files as $file) {
            $this->progressBar($bar, count($files));
            $bar++;

            if ($localStorage->exists($file)) {
                if ($localStorage->size($file) == $cloudStorage->size($file)) {
                    continue;
                }
            }

            $localStorage->put($file, $cloudStorage->get($file));
        }

        $this->line('');

        $this->alert('All files successfully downloaded to the local storage.');
    }
}
