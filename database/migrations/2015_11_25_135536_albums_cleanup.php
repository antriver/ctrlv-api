<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlbumsCleanup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('RENAME TABLE `tags` TO `albums`');
        DB::statement('ALTER TABLE `albums` CHANGE `tagID` `albumId` INT(10)  UNSIGNED  NOT NULL  AUTO_INCREMENT;');
        DB::statement('ALTER TABLE `albums` CHANGE `userID` `userId` INT(10)  UNSIGNED  NOT NULL AFTER `albumId`');
        DB::statement('ALTER TABLE `albums` DROP `images`');
        DB::statement('ALTER TABLE `albums` DROP `lastAdded`');
        DB::statement('ALTER TABLE `albums` CHANGE `password` `password` VARCHAR(100)  CHARACTER SET utf8mb4  COLLATE utf8mb4_unicode_ci  NULL  DEFAULT NULL;');
        DB::statement('ALTER TABLE `albums` CHANGE `privacy` `privacy` TINYINT(1)  NOT NULL;');

        DB::statement('ALTER TABLE `albums` CHANGE `date` `createdAt` DATETIME NOT NULL AFTER `password`;');
        DB::statement('ALTER TABLE `albums` ADD `updatedAt` DATETIME  NULL  DEFAULT NULL  AFTER `createdAt`;');

        DB::statement('delete albums from albums left join users on users.userId = albums.userId where users.userId IS NULL');

        Schema::table('albums', function (Blueprint $table) {
            $table->foreign('userId')->references('userId')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });

        DB::statement('RENAME TABLE `image_tags` TO `album_images`');
        DB::statement('ALTER TABLE `album_images` CHANGE `imageID` `imageId` INT(10)  UNSIGNED  NOT NULL  AFTER `tagID`;');
        DB::statement('ALTER TABLE `album_images` CHANGE `tagID` `albumId` INT(10)  UNSIGNED  NOT NULL');
        DB::statement('ALTER TABLE `album_images` DROP `date`');

        DB::statement('delete album_images from album_images left join albums on albums.albumId = album_images.albumId where albums.albumId IS NULL');
        DB::statement('delete album_images from album_images left join images on images.imageId = album_images.imageId where images.imageId IS NULL');

        Schema::table('album_images', function (Blueprint $table) {
            $table->foreign('albumId')->references('albumId')->on('albums')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('imageId')->references('imageId')->on('images')->onDelete('cascade')->onUpdate('cascade');
        });
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
