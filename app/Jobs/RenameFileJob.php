<?php

namespace CtrlV\Jobs;

use Log;
use CtrlV\Jobs\Job;
use CtrlV\Repositories\FileRepository;
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
     * @param string @oldRelativePath
     * @param string @newRelativePath
     * @return void
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
     * @return void
     */
    public function handle(FileRepository $fileRepository)
    {
        $this->logger->debug("Renaming file {$this->oldRelativePath} to {$this->newRelativePath} attempt {$this->attempts()}");

        print_r($fileRepository->renameFile($this->oldRelativePath, $this->newRelativePath, true));
    }

}
