<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PopulateImageFiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("INSERT INTO files (`directory`, `filename`, `optimized`, `copied`,`width`,`height`,`size`,`createdAt`)
SELECT 'img', filename, null, null, w, h, filesize, createdAt FROM images");

        DB::statement("INSERT INTO files (`directory`, `filename`)
SELECT 'annotation', annotation FROM images where annotation NOT LIKE '' AND annotation != 0");

        DB::statement("INSERT INTO files (`directory`, `filename`)
SELECT 'thumb', filename FROM images where thumb = 1");

        DB::statement("INSERT INTO files (`directory`, `filename`)
SELECT 'uncropped', uncroppedfilename FROM images where uncroppedfilename != ''");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("delete from files");
    }
}
