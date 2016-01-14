<?php

namespace CtrlV\Jobs;

use CtrlV\Libraries\FileManager;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;

class MoveRemoteFileJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue;
    use SerializesModels;

    private $oldPath;
    private $newPath;

    /**
     * Create a new job instance.
     *
     * @param string $oldPath
     * @param string $newPath
     */
    public function __construct($oldPath, $newPath)
    {
        $this->oldPath = $oldPath;
        $this->newPath = $newPath;
        parent::__construct();
    }

    /**
     * Execute the job.
     *
     * @param FileManager $fileRepository
     */
    public function handle(FileManager $fileRepository)
    {
        $this->logger = $this->getJobLogger();

        $this->logger->debug(
            "Moving remote file {$this->oldPath} to {$this->newPath} attempt {$this->attempts()}"
        );

        $fileRepository->moveRemoteFile($this->oldPath, $this->newPath);
    }
}
