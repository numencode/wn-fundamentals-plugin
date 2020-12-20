<?php namespace NumenCode\Fundamentals\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MediaCloudCommand extends Command
{
    protected $signature = 'media:cloud {cloud? : The name of the cloud (default: dropbox)}';

    protected $description = 'TBD';

    public function handle()
    {
        $cloudStorage = Storage::disk($this->argument('cloud') ?: 'dropbox');

        $cloudStorage->put('media/Features/110212.jpg', Storage::get('media/Features/110212.jpg'));
    }
}
