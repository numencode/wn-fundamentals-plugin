<?php namespace NumenCode\Fundamentals\Console;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class MediaPullCommand extends RemoteCommand
{
    protected $signature = 'media:pull
        {server : The name of the remote server}
        {cloud? : The name of the cloud (default: dropbox)}';

    protected $description = 'Push media from remote server to cloud and pull it on local server.';

    public function handle()
    {
        if (!$this->sshConnect()) {
            return;
        }

    }
}
