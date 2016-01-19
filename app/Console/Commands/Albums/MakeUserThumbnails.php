<?php

namespace CtrlV\Console\Commands\Albums;

use CtrlV\Models\Album;
use CtrlV\Models\User;
use Exception;
use CtrlV\Jobs\MakeAlbumThumbnailJob;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;

class MakeUserThumbnails extends Command
{
    use DispatchesJobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'albums:makeuserthumbnails {userId}  {--regenerate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a thumbnail for all of a user\'s albums';

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws Exception
     */
    public function handle()
    {
        $user = User::findOrFail($this->argument('userId'));

        if ($this->option('regenerate')) {
            $albums = $user->albums;
        } else {
            $albums = $user->albums()->whereNull('thumbnailImageFileId')->get();
        }

        foreach ($albums as $album) {
            \Queue::connection('sync')->push(new MakeAlbumThumbnailJob($album));
        }
    }
}
