<?php

namespace CtrlV\Console\Commands\Albums;

use CtrlV\Models\Album;
use Exception;
use CtrlV\Jobs\MakeAlbumThumbnailJob;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;

class MakeThumbnail extends Command
{
    use DispatchesJobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'albums:makethumbnail {albumId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a thumbnail for an album';

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws Exception
     */
    public function handle()
    {
        $album = Album::findOrFail($this->argument('albumId'));

        \Queue::connection('sync')->push(new MakeAlbumThumbnailJob($album));
    }
}
