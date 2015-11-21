<?php

namespace CtrlV\Jobs;

use Config;
use CtrlV\Libraries\CacheManager;
use CtrlV\Repositories\FileRepository;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;

class OptimizeFileJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue;
    use SerializesModels;

    private $relativePath;

    /**
     * Create a new job instance.
     *
     * @param string $relativePath
     *
     * @return \CtrlV\Jobs\OptimizeFileJob
     */
    public function __construct($relativePath)
    {
        $this->relativePath = $relativePath;
        parent::__construct();
    }

    /**
     * Execute the job.
     *
     * @param FileRepository $fileRepository
     * @param CacheManager $cacheManager
     *
     * @return void
     */
    public function handle(FileRepository $fileRepository, CacheManager $cacheManager)
    {
        $this->logger->debug("Optimizing file {$this->relativePath} attempt {$this->attempts()}");

        $this->optimizeImage();

        $fileRepository->copyToRemote($this->relativePath);
        $cacheManager->purge($this->relativePath);
    }

    private function optimizeImage()
    {
        $tmpFileName = 'ctrlv-optimize-' . md5($this->relativePath);
        $tempSourcePath = tempnam('/tmp', $tmpFileName . '-in');
        $tempDestPath = tempnam('/tmp', $tmpFileName . '-out');

        $cmd = null;

        $localDir = Config::get('app.data_dir');
        $filePath = $localDir . $this->relativePath;

        switch (exif_imagetype($filePath)) {

            case IMAGETYPE_JPEG:
                $cmd = "jpegtran -copy none -optimize -progressive {$tempSourcePath} > {$tempDestPath}";
                break;

            case IMAGETYPE_PNG:
                $cmd = "pngcrush -brute -l 9 {$tempSourcePath} {$tempDestPath}";
                break;

            default:
                return false;
        }

        if (empty($cmd)) {
            return false;
        }

        $cmd = "cp {$filePath} {$tempSourcePath} && $cmd && mv {$tempDestPath} {$filePath} && rm {$tempSourcePath}";

        passthru($cmd);

        unlink($tempSourcePath);
        unlink($tempDestPath);

        return true;
    }
}
