<?php

namespace CtrlV\Jobs;

use Config;
use CtrlV\Models\Album;
use Exception;
use CtrlV\Models\Image;
use CtrlV\Models\ImageFile;
use CtrlV\Libraries\FileManager;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Intervention\Image\Constraint;

class MakeAlbumThumbnailJob extends Job implements SelfHandling, ShouldQueue
{
    use DispatchesJobs;
    use InteractsWithQueue;
    use SerializesModels;

    private $album;

    /**
     * Create a new job instance.
     *
     * @param Album $album
     */
    public function __construct(Album $album)
    {
        $this->album = $album;
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
            "Generating thumbnail for album {$this->album->getId()}"
            ." attempt {$this->attempts()}",
            $this->album->toArray()
        );

        // Get a fresh copy from the DB (checks if it's deleted)
        if (!$this->album = $this->album->fresh()) {
            throw new Exception("Album no longer exists.");
        }

        // Get first 4 album pictures

        /** @var Image[] $images */
        $images = $this->album->images()->orderBy('imageId', 'DESC')->take(4)->get();

        $filenames = [];
        foreach ($images as $image) {
            $imageFile = $image->getImageFile();

            try {
                // This will make sure the file exists locally
                $picture = $fileManager->getPictureForImageFile($imageFile);
            } catch (Exception $e) {
                echo "Unable to download image file {$imageFile->getPath()} " . $e->getMessage() . PHP_EOL;
                continue;
            }


            if (@file_exists($imageFile->getAbsolutePath())) {
                // The [0] at the end is to cater for animated gifs
                // - use the first frame
                $filenames[] = '"'.$imageFile->getAbsolutePath().'[0]"';
            } else {
                echo "Unable to download image file {$imageFile->getPath()}" . PHP_EOL;
            }
        }

        if (empty($filenames)) {
            return false;
        }

        print_r($filenames);

        $thumbFilename = $fileManager->prepareLocalFile('albumthumb', 'jpg');
        $thumbAbsolutePath = Config::get('app.data_dir').'albumthumb/'.$thumbFilename;

        $cmd = "montage " . implode(' ', $filenames) . " -gravity center -resize \"50^\" -crop 50x50+0+0 -geometry 50x50 -tile 2x2 " . $thumbAbsolutePath;

        echo $cmd . PHP_EOL;

        passthru($cmd);

        if ($fileSize = filesize($thumbAbsolutePath)) {
            $imageFile = new ImageFile(
                [
                    'directory' => 'albumthumb',
                    'filename' => $thumbFilename,
                    'width' => 100,
                    'height' => 100,
                    'size' => $fileSize // File size in bytes
                ]
            );

            $imageFile->save();
            $this->dispatch(new OptimizeFileJob($imageFile));

            $this->album->thumbnailImageFileId = $imageFile->imageFileId;
            $this->album->save();

        } else {
            throw new Exception("Unable to create montage");
        }
    }
}
