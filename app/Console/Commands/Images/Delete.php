<?php

namespace CtrlV\Console\Commands\Images;

use Illuminate\Console\Command;
use CtrlV\Models\Image;

class Delete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:delete {imageId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete an image';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $image = Image::findOrFail($this->argument('imageId'));
        var_dump($image->delete());
    }
}
