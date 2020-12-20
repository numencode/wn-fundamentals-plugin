<?php namespace NumenCode\Fundamentals\Console;

class MediaPullCommand extends RemoteCommand
{
    protected $signature = 'media:pull {server : The name of the remote server}';

    protected $description = 'Push media from remote server to cloud and pull it on local server.';

    public function handle()
    {
        if (!$this->sshConnect()) {
            return;
        }

        $this->sshRunAndPrint(['sudo php artisan media:cloud dropbox']);
    }
}
