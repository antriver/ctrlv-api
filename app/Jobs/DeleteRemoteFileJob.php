<?php

namespace CtrlV\Jobs;

use CtrlV\Libraries\FileManager;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeleteRemoteFileJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue;
    use SerializesModels;

    private $path;

    /**
     * Create a new job instance.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
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
            "Deleting remote file {$this->path} attempt {$this->attempts()}"
        );

        $fileRepository->deleteRemoteFile($this->path);
    }
}
