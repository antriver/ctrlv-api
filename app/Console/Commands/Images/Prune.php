<?php

namespace CtrlV\Console\Commands\Images;

use Illuminate\Console\Command;

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
    protected $description = 'Delete local files not modified in the last 7 days';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        die("Update me");
        // TODO
        /*
        $directory = Config::get('app.data_dir');
        $cmd = <<<EOT
# Delete jpg and png files that haven't been modified in the last 7 days
find {$directory} -type f -mtime +7 -regex '.*\.\(jpg\|png\)' -exec rm {} \; -print

# Clear temporary crud
find /var/www/ctrlv/tmp/ -maxdepth 1 -type f -mtime +1 -not -name ".*" -exec rm {} \; -print
EOT;

        print_r(shell_exec($cmd));*/
    }
}
