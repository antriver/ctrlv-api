<?php

namespace CtrlV\Jobs;

use Illuminate\Bus\Queueable;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
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

    }

    /**
     * @return Logger
     */
    protected function getJobLogger()
    {
        $jobLogger = new Logger('Jobs');

        $lineFormatter = new LineFormatter(
            "[%datetime%] %message% %context% %extra%\n",
            null,
            true,
            true
        );

        $streamHandler = new StreamHandler("php://output");
        $streamHandler->setFormatter($lineFormatter);
        $jobLogger->pushHandler($streamHandler);

        $fileHandler = new RotatingFileHandler(storage_path().'/logs/jobs.log');
        $fileHandler->setFormatter($lineFormatter);
        $jobLogger->pushHandler($fileHandler);

        $jobLogger->debug(static::class);

        return $jobLogger;
    }
}
