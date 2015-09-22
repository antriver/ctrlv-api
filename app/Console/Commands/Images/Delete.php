<?php

namespace CtrlV\Console\Commands\Images;

use App;
use DateTime;
use Log;
use Illuminate\Console\Command;
use CtrlV\Models\ImageModel;

class Delete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:delete {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete an image';

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
        $image = ImageModel::findOrFail($this->argument('id'));
        var_dump($image->delete());
    }
}
