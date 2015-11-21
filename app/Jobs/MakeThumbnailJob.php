<?php

namespace CtrlV\Jobs;

use CtrlV\Models\ImageModel;
use CtrlV\Repositories\FileRepository;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;

class MakeThumbnailJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue;
    use SerializesModels;

    private $imageModel;

    /**
     * Create a new job instance.
     *
     * @param ImageModel $imageModel
     */
    public function __construct(ImageModel $imageModel)
    {
        $this->imageModel = $imageModel;
        parent::__construct();
    }

    /**
     * Execute the job.
     *
     * @param FileRepository $fileRepository
     *
     * @throws \Exception
     * @return void
     */
    public function handle(FileRepository $fileRepository)
    {
        $this->logger->debug(
            "Generating thumbnail for image img/{$this->imageModel->filename} attempt {$this->attempts()}"
        );

        // Get full size image
        $image = $fileRepository->getImage('img/' . $this->imageModel->filename);

        $image->fit(200);

        if ($fileRepository->saveImage($image, 'thumb', $this->imageModel->filename)) {
            $this->imageModel->thumb = true;
            $this->imageModel->save();
        }
    }
}
