<?php

namespace CtrlV\Jobs;

use Exception;
use CtrlV\Models\Image;
use CtrlV\Models\ImageFile;
use CtrlV\Libraries\FileManager;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Intervention\Image\Constraint;

class MakeThumbnailJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue;
    use SerializesModels;

    private $imageFile;

    /**
     * Create a new job instance.
     *
     * @param ImageFile $imageFile
     */
    public function __construct(ImageFile $imageFile)
    {
        $this->imageFile = $imageFile;
        parent::__construct();
    }

    /**
     * Execute the job.
     *
     * @param FileManager $fileManager
     *
     * @throws Exception
     */
    public function handle(FileManager $fileManager)
    {
        $this->logger = $this->getJobLogger();

        $this->logger->debug(
            "Generating thumbnail for image {$this->imageFile->getId()} {$this->imageFile->getPath()}"
            ." attempt {$this->attempts()}",
            $this->imageFile->toArray()
        );

        // Get a fresh copy from the DB (checks if it's deleted)
        if (!$this->imageFile = $this->imageFile->fresh()) {
            throw new Exception("ImageFile no longer exists.");
        }

        // Get full size image
        $picture = $fileManager->getPictureForImageFile($this->imageFile);
        $filename = $this->imageFile->getFilename();

        // Generate 200x200 thumbnail
        $picture->fit(
            200,
            200,
            function (Constraint $constraint) {
                $constraint->upsize();
            }
        );

        if ($thumbnailImageFile = $fileManager->savePicture($picture, 'thumb', $filename, $this->imageFile)) {
            Image::where('imageFileID', $this->imageFile->getId())
                ->update(['thumbnailImageFileId' => $thumbnailImageFile->getId()]);
        }
    }
}
