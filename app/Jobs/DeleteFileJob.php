<?php

namespace CtrlV\Jobs;

use CtrlV\Repositories\FileRepository;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeleteFileJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue;
    use SerializesModels;

    private $relativePath;

    /**
     * Create a new job instance.
     *
     * @param $relativePath
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
     */
    public function handle(FileRepository $fileRepository)
    {
        $this->logger->debug("Deleting file {$this->relativePath} attempt {$this->attempts()}");

        $fileRepository->deleteFile($this->relativePath, true);
    }
}
