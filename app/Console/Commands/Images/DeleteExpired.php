<?php

namespace CtrlV\Console\Commands\Images;

use App;
use DateTime;
use Log;
use Illuminate\Console\Command;
use CtrlV\Models\ImageModel;

class DeleteExpired extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:deleteexpired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete images past their expiration date';

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
        $images = ImageModel::whereNotNull('expires_at')
            ->where('expires_at', '<=', date('Y-m-d H:i:s'))->get();

        foreach ($images as $image) {
            echo "{$image->imageID}\tExpired at {$image->expires_at}" . PHP_EOL;
            $image->delete();
        }
    }
}
