<?php

namespace CtrlV\Console\Commands\Images;

use CtrlV\Jobs\DeleteFileJob;
use Exception;
use CtrlV\Jobs\MakeThumbnailJob;
use Illuminate\Console\Command;
use CtrlV\Models\Image;
use Illuminate\Foundation\Bus\DispatchesJobs;

class MakeThumbnail extends Command
{
    use DispatchesJobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:makethumbnail {imageId} {--regenerate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a thumbnail for an image';

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws Exception
     */
    public function handle()
    {
        $image = Image::findOrFail($this->argument('imageId'));

        if (!empty($image->thumbnailImageFileId)) {
            if (!$this->option('regenerate')) {
                throw new Exception("Image already has a thumbnail and --regenerate was not set");
            }

            $thumbnail = $image->getThumbnailImageFile();
            \Queue::connection('sync')->push(new DeleteFileJob($thumbnail->getPath()));
            $thumbnail->delete(false);
        }

        \Queue::connection('sync')->push(new MakeThumbnailJob($image->getImageFile()));
    }
}
