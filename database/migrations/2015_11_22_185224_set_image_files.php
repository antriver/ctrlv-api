<?php

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
        DB::statement("
            update `images` `i`
            left join `image_files` `if` on `if`.directory = 'uncropped' and `if`.filename = `i`.uncroppedfilename
            set `i`.uncroppedImageFileId = `if`.imageFileId
            where uncroppedfilename != ''
        ");

        DB::statement("
            update `images` `i`
            left join `image_files` `if` on `if`.directory = 'img' and `if`.filename = `i`.filename
            set `i`.imageFileId = `if`.imageFileId
        ");

        DB::statement("
            update `images` `i`
            left join `image_files` `if` on `if`.directory = 'annotation' and `if`.filename = `i`.annotation
            set `i`.annotationImageFileId = `if`.imageFileId
            where annotation NOT LIKE '' AND annotation != 0
        ");

        DB::statement("
            update `images` `i`
            left join `image_files` `if` on `if`.directory = 'thumb' and `if`.filename = `i`.filename
            set `i`.thumbnailImageFileId = `if`.imageFileId
            where thumb = 1
        ");

        DB::statement("
            update `image_files` `if`
            left join `images` `i` on `i`.`thumbnailImageFileId` = `if`.`imageFileId`
            set `if`.originalImageFileId = `i`.imageFileId
            where `if`.`directory` = 'thumb'
        ");

        DB::statement("
            update `image_files` `if`
            left join `images` `i` on `i`.`imageFileId` = `if`.`imageFileId`
            set `if`.originalImageFileId = `i`.uncroppedImageFileId
            where `if`.`directory` = 'img' and i.`uncroppedImageFileId` is not null
        ");

        DB::statement('
            ALTER TABLE `images`
            DROP `filename`,
            DROP `uncroppedfilename`,
            DROP `annotation`,
            DROP `thumb`,
            DROP `w`,
            DROP `h`,
            DROP `filesize`'
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
