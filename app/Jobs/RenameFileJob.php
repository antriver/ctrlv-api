<?php

namespace CtrlV\Jobs;

use CtrlV\Libraries\FileManager;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;

class RenameFileJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue;
    use SerializesModels;

    private $oldRelativePath;
    private $newRelativePath;

    /**
     * Create a new job instance.
     *
     * @param string $oldRelativePath
     * @param string $newRelativePath
     */
    public function __construct($oldRelativePath, $newRelativePath)
    {
        $this->oldRelativePath = $oldRelativePath;
        $this->newRelativePath = $newRelativePath;
        parent::__construct();
    }

    /**
     * Execute the job.
     *
     * @param FileManager $fileRepository
     */
    public function handle(FileManager $fileRepository)
    {
        $this->logger->debug(
            "Renaming file {$this->oldRelativePath} to {$this->newRelativePath} attempt {$this->attempts()}"
        );

        $fileRepository->renameFile($this->oldRelativePath, $this->newRelativePath, true);
    }
}
