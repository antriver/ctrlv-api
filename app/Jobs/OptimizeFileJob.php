<?php

namespace CtrlV\Jobs;

use Config;
use Exception;
use CtrlV\Libraries\CacheManager;
use CtrlV\Libraries\FileManager;
use CtrlV\Models\ImageFile;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Optimizes an image and copies it to the remote storage (S3).
 */
class OptimizeFileJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue;
    use SerializesModels;

    private $imageFile;

    /**
     * Create a new job instance.
     *
     * @param ImageFile $imageFile
     *
     * @return \CtrlV\Jobs\OptimizeFileJob
     */
    public function __construct(ImageFile $imageFile)
    {
        $this->imageFile = $imageFile;
        parent::__construct();
    }

    /**
     * Execute the job.
     *
     * @param FileManager  $fileRepository
     * @param CacheManager $cacheManager
     *
     * @throws Exception
     */
    public function handle(FileManager $fileRepository, CacheManager $cacheManager)
    {
        $this->logger = $this->getJobLogger();

        $this->logger->debug(
            "Optimizing file {$this->imageFile->getId()} {$this->imageFile->getPath()} attempt {$this->attempts()}",
            $this->imageFile->toArray()
        );

        // Get a fresh copy from the DB (checks if it's deleted)
        if (!$this->imageFile = $this->imageFile->fresh()) {
            throw new Exception("ImageFile no longer exists.");
        }

        $this->optimize();

        $fileRepository->copyToRemote($this->imageFile);
        $cacheManager->purge($this->imageFile->getPath());
    }

    private function optimize()
    {
        $path = $this->imageFile->getPath();

        $tmpFileName = 'ctrlv-optimize-'.$this->imageFile->getId();
        $tempSourcePath = tempnam('/tmp', $tmpFileName.'-in');
        $tempDestPath = tempnam('/tmp', $tmpFileName.'-out');

        $cmd = null;

        $localDataDirectory = Config::get('app.data_dir');
        $fullPath = $localDataDirectory.$path;

        if (!file_exists($fullPath)) {
            throw new Exception("Cannot find local file {$fullPath}");
        }

        $exifType = exif_imagetype($fullPath);
        switch ($exifType) {

            case IMAGETYPE_JPEG:
                $cmd = "jpegtran -copy none -optimize -progressive {$tempSourcePath} > {$tempDestPath}";
                break;

            case IMAGETYPE_PNG:
                $cmd = "pngcrush -brute -l 9 {$tempSourcePath} {$tempDestPath}";
                break;

            default:
                throw new Exception("Unsupported file type {$exifType}");
        }

        if (empty($cmd)) {
            return false;
        }

        $cmd = "cp {$fullPath} {$tempSourcePath} && $cmd && mv {$tempDestPath} {$fullPath} && rm {$tempSourcePath}";

        passthru($cmd);

        if (file_exists($tempSourcePath)) {
            unlink($tempSourcePath);
        }
        if (file_exists($tempDestPath)) {
            unlink($tempDestPath);
        }

        $optimizedSize = filesize($fullPath);

        $this->imageFile->optimized = true;
        $this->imageFile->optimizedSize = $optimizedSize;
        $this->imageFile->save();

        return true;
    }
}
