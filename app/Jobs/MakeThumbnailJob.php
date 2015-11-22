<?php

namespace CtrlV\Jobs;

use CtrlV\Models\Image;
use CtrlV\Libraries\FileManager;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;

class MakeThumbnailJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue;
    use SerializesModels;

    private $image;

    /**
     * Create a new job instance.
     *
     * @param Image $image
     */
    public function __construct(Image $image)
    {
        $this->image = $image;
        parent::__construct();
    }

    /**
     * Execute the job.
     *
     * @param FileManager $fileRepository
     *
     * @throws \Exception
     */
    public function handle(FileManager $fileRepository)
    {
        $this->logger->debug(
            "Generating thumbnail for image img/{$this->image->filename} attempt {$this->attempts()}"
        );

        // Get full size image
        $picture = $fileRepository->getPicture('img/' . $this->image->filename);

        $picture->fit(200);

        if ($fileRepository->savePicture($picture, 'thumb', $this->image->filename)) {
            $this->image->thumb = true;
            $this->image->save();
        }
    }
}
