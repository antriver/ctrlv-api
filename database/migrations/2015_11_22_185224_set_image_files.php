<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SetImageFiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("update `images` `i` left join `files` `if` on `if`.directory = 'img' and `if`.filename = `i`.filename set `i`.fileId = `if`.fileId");

        DB::statement("update `images` `i` left join `files` `if` on `if`.directory = 'annotation' and `if`.filename = `i`.annotation set `i`.annotationFileId = `if`.fileId where annotation NOT LIKE '' AND annotation != 0");

        DB::statement("update `images` `i` left join `files` `if` on `if`.directory = 'thumb' and `if`.filename = `i`.filename set `i`.thumbnailFileId = `if`.fileId where thumb = 1");

        DB::statement("update `images` `i` left join `files` `if` on `if`.directory = 'uncropped' and `if`.filename = `i`.uncroppedfilename set `i`.uncroppedFileId = `if`.fileId where uncroppedfilename != ''");

        DB::statement('ALTER TABLE `images` DROP `filename`, DROP `uncroppedfilename`, DROP `annotation`, DROP `thumb`, DROP `w`, DROP `h`, DROP `filesize`'); // no undo :(
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('update images set fileId = null, annotationFileId = null, thumbnailFileId = null, uncroppedFileId = null');
    }
}
