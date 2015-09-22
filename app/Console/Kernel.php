<?php

namespace CtrlV\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \CtrlV\Console\Commands\Images\Delete::class,
        \CtrlV\Console\Commands\Images\DeleteExpired::class,
        \CtrlV\Console\Commands\Images\Prune::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('images:deleteexpired')->everyMinute();
        $schedule->command('images:prune')->hourly();
    }
}
