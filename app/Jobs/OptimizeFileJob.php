<?php

namespace CtrlV\Jobs;

use Config;
use CtrlV\Libraries\CacheManager;
use CtrlV\Libraries\FileManager;
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
     * @param FileManager $fileRepository
     * @param CacheManager $cacheManager
     */
    public function handle(FileManager $fileRepository, CacheManager $cacheManager)
    {
        $this->logger->debug("Optimizing file {$this->relativePath} attempt {$this->attempts()}");

        $this->optimize();

        $fileRepository->copyToRemote($this->relativePath);
        $cacheManager->purge($this->relativePath);
    }

    private function optimize()
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

        if (file_exists($tempSourcePath)) {
            unlink($tempSourcePath);
        }
        if (file_exists($tempDestPath)) {
            unlink($tempDestPath);
        }

        return true;
    }
}
