<?php

namespace CtrlV\Jobs;

use Illuminate\Bus\Queueable;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

abstract class Job
{
    /*
    |--------------------------------------------------------------------------
    | Queueable Jobs
    |--------------------------------------------------------------------------
    |
    | This job base class provides a central location to place any logic that
    | is shared across all of your jobs. The trait included with the class
    | provides access to the "queueOn" and "delay" queue helper methods.
    |
    */

    use Queueable;

    protected $logger = null;

    protected function __construct()
    {
        $this->logger = $this->getJobLogger();
    }

    /**
     * @return Logger
     */
    private function getJobLogger()
    {
        $jobLogger = new Logger('Jobs');
        $fileHandler = new RotatingFileHandler(storage_path() . '/logs/jobs.log');
        $lineFormatter = new LineFormatter(
            "[%datetime%] %message% %context% %extra%\n",
            null,
            true,
            true
        );
        $fileHandler->setFormatter($lineFormatter);
        $jobLogger->pushHandler($fileHandler);
        return $jobLogger;
    }
}
