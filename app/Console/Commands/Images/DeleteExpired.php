<?php

namespace CtrlV\Console\Commands\Images;

use Illuminate\Console\Command;
use CtrlV\Models\Image;

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /** @var Image[] $images */
        $images = Image::whereNotNull('expiresAt')
            ->where('expiresAt', '<=', date('Y-m-d H:i:s'))->get();

        foreach ($images as $image) {
            echo "{$image->imageId}\tExpired at {$image->expiresAt}".PHP_EOL;
            $image->delete();
        }
    }
}
