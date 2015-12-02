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

        DB::statement("ALTER TABLE `albums` CHANGE `name` `title` VARCHAR(100)  CHARACTER SET utf8mb4  COLLATE utf8mb4_unicode_ci  NOT NULL  DEFAULT '';");

        DB::statement('delete albums from albums left join users on users.userId = albums.userId where users.userId IS NULL');

        Schema::table('albums', function (Blueprint $table) {
            $table->foreign('userId')->references('userId')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });


        // Delete image_tags that refer to dead albums
        DB::statement('delete image_tags from image_tags left join albums on albums.albumId = image_tags.tagID where albums.albumId IS NULL');

        // Delete image_tags that refer to dead images
        DB::statement('delete image_tags from image_tags left join images on images.imageId = image_tags.imageID where images.imageId IS NULL');

        // Add albumId field to images
        DB::statement('ALTER TABLE `images` ADD `albumId` INT(10)  UNSIGNED  NULL  DEFAULT NULL  AFTER `uncroppedImageFileId`');

        Schema::table('images', function (Blueprint $table) {
            $table->foreign('albumId')->references('albumId')->on('albums')->onDelete('set null')->onUpdate('cascade');
        });

        DB::statement("
            update `images` `i`
            left join `image_tags` `it` on `it`.imageID = i.imageId
            set `i`.albumId = `it`.tagID
            where `it`.imageID IS NOT NULL
        ");

        DB::statement("DROP TABLE image_tags");

        DB::statement("UPDATE albums SET password = NULL WHERE password = ''");
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
