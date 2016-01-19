<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAlbumThumb extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `albums` ADD `thumbnailImageFileId` INT(10)  UNSIGNED  NULL  DEFAULT NULL  AFTER `userId`;');
        DB::statement('ALTER TABLE `albums` ADD CONSTRAINT `album_thumb` FOREIGN KEY (`thumbnailImageFileId`) REFERENCES `image_files` (`imageFileId`) ON DELETE SET NULL ON UPDATE CASCADE;');
        DB::statement("ALTER TABLE `image_files` CHANGE `directory` `directory` ENUM('img','thumb','annotation','uncropped','albumthumb')  CHARACTER SET utf8mb4  COLLATE utf8mb4_unicode_ci  NOT NULL  DEFAULT 'img';");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
