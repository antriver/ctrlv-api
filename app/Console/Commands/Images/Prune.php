<?php

namespace CtrlV\Console\Commands\Images;

use App;
use DateTime;
use Log;
use Illuminate\Console\Command;
use CtrlV\Models\ImageModel;

class Prune extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete local image files not modified in the last 7 days';

    /**
     * @var Monolog
     */
    private $log;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cmd = <<<EOT
# Delete jpg and png files that havent been modified in the last 7 days
find /var/www/ctrlv/img/ -type f -mtime +7 -regex '.*\.\(jpg\|png\)' -exec rm {} \; -print

# Clear temporary crud
find /var/www/ctrlv/tmp/ -maxdepth 1 -type f -mtime +1 -not -name ".*" -exec rm {} \; -print
EOT;

        print_r(shell_exec($cmd));
    }
}
